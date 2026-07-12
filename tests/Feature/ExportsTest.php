<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Gate::define('ai-compliance:audit', fn (GenericUser $user): bool => (bool) ($user->audit ?? false));
    Gate::define('ai-compliance:manage', fn (GenericUser $user): bool => (bool) ($user->manage ?? false));
    Gate::define('ai-compliance:export', fn (GenericUser $user): bool => (bool) ($user->export ?? false));
});

function exporter(): GenericUser
{
    return new GenericUser(['id' => 9, 'audit' => true, 'export' => true]);
}

it('exports the consent log pseudonymized, matching the on-screen data (spec acceptance 5)', function (): void {
    $user = makeUser();
    $consent = app(ConsentManager::class);
    $consent->grant($user, 'ai_training', 'settings_page');
    $consent->withdraw($user, 'ai_training', 'settings_page');
    $consent->grant('g_' . str_repeat('x', 32), 'ai_chatbot');

    $response = $this->actingAs(exporter())
        ->get('/ai-compliance/admin/exports/consents?format=json')
        ->assertOk();

    $rows = $response->json('data');

    // every on-screen row is in the export, nothing more
    expect($rows)->toHaveCount(ConsentRecord::query()->count());

    $publicIds = ConsentRecord::query()->pluck('public_id')->sort()->values()->all();
    expect(collect($rows)->pluck('id')->sort()->values()->all())->toBe($publicIds);

    // pseudonymized: every subject is a stable pseudonym, never the raw
    // user reference or the raw guest key
    $subjects = collect($rows)->pluck('subject');
    expect($subjects->every(fn (?string $subject): bool => $subject === null || str_starts_with($subject, 'sub_')))->toBeTrue()
        ->and($subjects)->not->toContain('user#' . $user->id)
        ->and($subjects->filter(fn (?string $subject): bool => $subject !== null && str_contains($subject, 'g_')))->toBeEmpty();

    // the same subject lines up across rows
    $userRows = collect($rows)->where('consent_type', 'ai_training');
    expect($userRows->pluck('subject')->unique())->toHaveCount(1);

    // csv variant carries the same header + rows
    $csv = $this->actingAs(exporter())
        ->get('/ai-compliance/admin/exports/consents')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    expect($csv->getContent())->toContain('id,subject,consent_type,status,source,policy_version,recorded_at')
        ->and(substr_count((string) $csv->getContent(), "\n"))->toBe(count($rows) + 1);
});

it('scopes exports by type, status, and date', function (): void {
    $user = makeUser();
    $consent = app(ConsentManager::class);
    $consent->grant($user, 'ai_training');
    $consent->grant($user, 'ai_chatbot');

    $rows = $this->actingAs(exporter())
        ->get('/ai-compliance/admin/exports/consents?format=json&type=ai_training&status=granted')
        ->json('data');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['consent_type'])->toBe('ai_training');

    $none = $this->actingAs(exporter())
        ->get('/ai-compliance/admin/exports/consents?format=json&from=' . now()->addDay()->toDateString())
        ->json('data');

    expect($none)->toBeEmpty();
});

it('requires the dedicated export gate', function (): void {
    $auditorOnly = new GenericUser(['id' => 3, 'audit' => true, 'export' => false]);

    $this->actingAs($auditorOnly)
        ->get('/ai-compliance/admin/exports/consents')
        ->assertForbidden();
});

it('logs every export as an activity event', function (): void {
    $this->actingAs(exporter())->get('/ai-compliance/admin/exports/consents?format=json');

    $event = ActivityEvent::query()->where('event_type', ActivityType::Export->value)->firstOrFail();

    expect($event->context)->toMatchArray(['log' => 'consents', 'pseudonymized' => true]);
});

it('exports the activity log with guest keys pseudonymized inside context', function (): void {
    app(ConsentManager::class)->grant('g_' . str_repeat('y', 32), 'ai_chatbot');

    $rows = $this->actingAs(exporter())
        ->get('/ai-compliance/admin/exports/activity?format=json&type=consent_change')
        ->json('data');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['context'])->not->toContain(str_repeat('y', 32))
        ->and($rows[0]['context'])->toContain('sub_');
});

it('writes identified exports only from the console command', function (): void {
    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_training');

    $path = sys_get_temp_dir() . '/ai-compliance-test-export-' . bin2hex(random_bytes(4)) . '.json';

    $this->artisan('laranail::ai-compliance.export', [
        'log' => 'consents',
        '--format' => 'json',
        '--identified' => true,
        '--path' => $path,
    ])->assertSuccessful();

    $rows = json_decode((string) file_get_contents($path), true);

    expect($rows[0]['subject'])->toBe('user#' . $user->id);

    unlink($path);
});
