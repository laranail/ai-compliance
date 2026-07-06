<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Checklist\Classification;
use Simtabi\Laranail\AiCompliance\Database\Seeders\ChecklistSeeder;
use Simtabi\Laranail\AiCompliance\Enums\CheckStatus;
use Simtabi\Laranail\AiCompliance\Features\FeatureGate;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;

uses(RefreshDatabase::class);

it('keeps the dpia applicable when either personal data or consequential decisions is yes', function (): void {
    app(ChecklistSeeder::class)->run();
    $classification = app(Classification::class);

    // consequential decisions without personal data: the dpia still applies
    $classification->record([
        'processes_personal_data' => 'no',
        'consequential_decisions' => 'yes',
    ], 'tester');

    expect(ChecklistItem::query()->where('key', 'privacy.dpia')->firstOrFail()->status)
        ->not->toBe(CheckStatus::NotApplicable);

    // both no: switched off, both reasons recorded
    $classification->record(['consequential_decisions' => 'no'], 'tester');
    $item = ChecklistItem::query()->where('key', 'privacy.dpia')->firstOrFail();

    expect($item->status)->toBe(CheckStatus::NotApplicable)
        ->and($item->evidence_ref)->toContain('processes_personal_data=no')
        ->and($item->evidence_ref)->toContain('consequential_decisions=no');

    // one flips back to yes: applicable again
    $classification->record(['processes_personal_data' => 'yes'], 'tester');

    expect(ChecklistItem::query()->where('key', 'privacy.dpia')->firstOrFail()->status)
        ->toBe(CheckStatus::Review);
});

it('leaves the dpia applicable while an any_of question is unanswered', function (): void {
    app(ChecklistSeeder::class)->run();

    // only one of the two questions answered, negatively: not enough to switch off
    app(Classification::class)->record(['processes_personal_data' => 'no'], 'tester');

    expect(ChecklistItem::query()->where('key', 'privacy.dpia')->firstOrFail()->status)
        ->not->toBe(CheckStatus::NotApplicable);
});

it('seeds the internal policies deactivated so they never serve publicly', function (): void {
    app(PolicySync::class)->sync();

    foreach (['acceptable-use', 'incident-response'] as $slug) {
        expect(PolicyDocument::query()->where('slug', $slug)->firstOrFail()->active)->toBeFalse()
            ->and(app(PolicyRepository::class)->find($slug))->toBeNull();

        $this->get('/ai-compliance/policies/' . $slug)->assertNotFound();
    }

    // the public transparency page is unaffected
    expect(PolicyDocument::query()->where('slug', 'transparency')->firstOrFail()->active)->toBeTrue();
});

it('lists and toggles feature kill switches from the console', function (): void {
    config()->set('laranail.ai-compliance.features', [
        'smart_summaries' => ['consents' => ['ai_personalization']],
    ]);

    $this->artisan('laranail::ai-compliance.feature')
        ->expectsOutputToContain('smart_summaries')
        ->assertSuccessful();

    $this->artisan('laranail::ai-compliance.feature', ['feature' => 'smart_summaries', '--disable' => true])
        ->assertSuccessful();

    expect(app(FeatureGate::class)->enabled('smart_summaries'))->toBeFalse();

    $this->artisan('laranail::ai-compliance.feature', ['feature' => 'smart_summaries', '--enable' => true])
        ->assertSuccessful();

    expect(app(FeatureGate::class)->enabled('smart_summaries'))->toBeTrue();

    $this->artisan('laranail::ai-compliance.feature', ['feature' => 'nope'])->assertFailed();
});

it('seeds demo data from install --demo', function (): void {
    $this->artisan('laranail::ai-compliance.install', ['--no-publish' => true, '--demo' => true])
        ->assertSuccessful();

    expect(ConsentRecord::query()->count())->toBe(8);
});
