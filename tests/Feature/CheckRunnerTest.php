<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Simtabi\Laranail\AiCompliance\Checklist\Classification;
use Simtabi\Laranail\AiCompliance\Checks\Check;
use Simtabi\Laranail\AiCompliance\Checks\CheckResult;
use Simtabi\Laranail\AiCompliance\Checks\CheckRunner;
use Simtabi\Laranail\AiCompliance\Database\Seeders\ChecklistSeeder;
use Simtabi\Laranail\AiCompliance\Enums\CheckStatus;
use Simtabi\Laranail\AiCompliance\Events\CheckFailed;
use Simtabi\Laranail\AiCompliance\Events\ChecklistItemDegraded;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Simtabi\Laranail\AiCompliance\Notifications\ActivityLogSilentNotification;
use Simtabi\Laranail\AiCompliance\Notifications\CheckFailedNotification;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::fake([
        '*/robots.txt' => Http::response("User-agent: GPTBot\nDisallow: /\n"),
        '*/llms.txt' => Http::response('# acme'),
    ]);

    app(ChecklistSeeder::class)->run();
});

function itemStatus(string $key): CheckStatus
{
    return ChecklistItem::query()->where('key', $key)->firstOrFail()->status;
}

it('writes every auto check result to its checklist item', function (): void {
    app(PolicySync::class)->sync();
    Provider::factory()->dueDiligenceComplete()->create();
    ActivityEvent::factory()->create();

    $results = app(CheckRunner::class)->run();

    expect(count($results))->toBeGreaterThanOrEqual(9)
        ->and(itemStatus('transparency.first_contact_disclosure'))->toBe(CheckStatus::Ok)
        ->and(itemStatus('consent.crawler_signals'))->toBe(CheckStatus::Ok)
        ->and(itemStatus('governance.provider_registry'))->toBe(CheckStatus::Ok)
        ->and(itemStatus('vendors.due_diligence'))->toBe(CheckStatus::Ok)
        ->and(itemStatus('logging.activity_log_alive'))->toBe(CheckStatus::Ok)
        ->and(itemStatus('governance.accountable_owner'))->toBe(CheckStatus::Fail) // dpo name unset in tests
        ->and(itemStatus('consent.granular_types'))->toBe(CheckStatus::Ok)
        ->and(itemStatus('privacy.retention_schedule'))->toBe(CheckStatus::Review)
        ->and(itemStatus('governance.policy_versioning'))->toBe(CheckStatus::Review); // template placeholders unresolved

    $item = ChecklistItem::query()->where('key', 'governance.provider_registry')->firstOrFail();
    expect($item->verified_by)->toBe('check-runner')
        ->and($item->last_verified_at)->not->toBeNull()
        ->and($item->evidence_ref)->toContain('1 providers registered');
});

it('fails the registry check on an empty registry and fires CheckFailed', function (): void {
    Event::fake([CheckFailed::class]);

    app(CheckRunner::class)->run();

    expect(itemStatus('governance.provider_registry'))->toBe(CheckStatus::Fail);

    Event::assertDispatched(CheckFailed::class, fn (CheckFailed $event): bool => $event->item->key === 'governance.provider_registry');
});

it('degrades the alive check to fail and alerts when the log goes silent (spec acceptance 6)', function (): void {
    config()->set('laranail.ai-compliance.alerting.mail', 'alerts@acme.test');
    Notification::fake();

    // a log that was alive once, then went silent past the threshold
    ActivityEvent::factory()->create(['recorded_at' => now()->subHours(30)]);

    app(CheckRunner::class)->run();

    expect(itemStatus('logging.activity_log_alive'))->toBe(CheckStatus::Fail);

    Notification::assertSentOnDemand(ActivityLogSilentNotification::class);
});

it('sends the generic failure alert for other failing checks', function (): void {
    config()->set('laranail.ai-compliance.alerting.mail', 'alerts@acme.test');
    Notification::fake();

    app(CheckRunner::class)->run(); // empty registry fails

    Notification::assertSentOnDemand(CheckFailedNotification::class);
});

it('reports review with names when registry rows are incomplete', function (): void {
    Provider::factory()->create(['name' => 'halfdone']);

    app(CheckRunner::class)->run();

    $item = ChecklistItem::query()->where('key', 'governance.provider_registry')->firstOrFail();

    expect($item->status)->toBe(CheckStatus::Review)
        ->and($item->evidence_ref)->toContain('halfdone');
});

it('skips items switched off by classification', function (): void {
    // switch off the whole privacy section
    app(Classification::class)
        ->record(['processes_personal_data' => 'no'], 'tester');

    app(CheckRunner::class)->run();

    // the retention check maps to a privacy item that is now na; it must stay na
    expect(itemStatus('privacy.retention_schedule'))->toBe(CheckStatus::NotApplicable);
});

it('auto-degrades stale manual verifications to review', function (): void {
    Event::fake([ChecklistItemDegraded::class]);

    $item = ChecklistItem::query()->where('key', 'privacy.dpia')->first()
        ?? ChecklistItem::query()->where('evidence_type', 'manual')->firstOrFail();

    $item->update([
        'status' => CheckStatus::Ok,
        'last_verified_at' => now()->subMonths($item->staleness_months + 1),
        'verified_by' => 'auditor',
    ]);

    app(CheckRunner::class)->run();

    expect($item->fresh()?->status)->toBe(CheckStatus::Review)
        ->and($item->fresh()?->evidence_ref)->toContain('re-verify');

    Event::assertDispatched(ChecklistItemDegraded::class);
});

it('runs host-registered checks tagged in the container', function (): void {
    ChecklistItem::factory()->auto()->create(['key' => 'host.custom']);

    $custom = new class implements Check
    {
        public function key(): string
        {
            return 'host.custom';
        }

        public function run(): CheckResult
        {
            return CheckResult::ok('host check ran');
        }
    };

    app()->instance($custom::class, $custom);
    app()->tag([$custom::class], 'ai-compliance.checks');

    app(CheckRunner::class)->run();

    expect(itemStatus('host.custom'))->toBe(CheckStatus::Ok);
});
