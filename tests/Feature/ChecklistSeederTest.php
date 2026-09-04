<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Enums\CheckStatus;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;
use Simtabi\Laranail\AiCompliance\Checklist\Classification;
use Simtabi\Laranail\AiCompliance\Checklist\ChecklistDefinitions;
use Simtabi\Laranail\AiCompliance\Database\Seeders\ChecklistSeeder;

uses(RefreshDatabase::class);

it('seeds the full checklist with everything at review', function (): void {
    app(ChecklistSeeder::class)->run();

    $expected = count(ChecklistDefinitions::all());

    expect(ChecklistItem::query()->count())->toBe($expected)
        ->and($expected)->toBeGreaterThanOrEqual(40)
        ->and(ChecklistItem::query()->where('status', CheckStatus::Review->value)->count())->toBe($expected);
});

it('is idempotent and refreshes definition text without touching status or evidence', function (): void {
    app(ChecklistSeeder::class)->run();

    ChecklistItem::query()->where('key', 'governance.provider_registry')->firstOrFail()->update([
        'status'       => CheckStatus::Ok,
        'evidence_ref' => 'verified manually',
    ]);

    app(ChecklistSeeder::class)->run();

    $item = ChecklistItem::query()->where('key', 'governance.provider_registry')->firstOrFail();

    expect(ChecklistItem::query()->count())->toBe(count(ChecklistDefinitions::all()))
        ->and($item->status)->toBe(CheckStatus::Ok)
        ->and($item->evidence_ref)->toBe('verified manually');
});

it('marks exactly the automatable items as auto', function (): void {
    app(ChecklistSeeder::class)->run();

    $auto = ChecklistItem::query()->where('evidence_type', 'auto')->pluck('key')->sort()->values()->all();

    expect($auto)->toBe([
        'consent.crawler_signals',
        'consent.granular_types',
        'governance.accountable_owner',
        'governance.policy_versioning',
        'governance.provider_registry',
        'logging.activity_log_alive',
        'privacy.retention_schedule',
        'transparency.first_contact_disclosure',
        'vendors.due_diligence',
    ]);
});

it('flips items to na with a reason when classification switches them off, and back', function (): void {
    app(ChecklistSeeder::class)->run();

    $classification = app(Classification::class);

    $classification->record(['consequential_decisions' => 'no'], 'tester');

    $decisions = ChecklistItem::query()->where('section', 'decisions')->get();

    expect($decisions)->not->toBeEmpty()
        ->and($decisions->every(fn (ChecklistItem $item): bool => $item->status === CheckStatus::NotApplicable))->toBeTrue()
        ->and($decisions->first()?->evidence_ref)->toContain('switched off by classification: consequential_decisions=no');

    // unrelated sections stay live
    expect(ChecklistItem::query()->where('key', 'governance.provider_registry')->firstOrFail()->status)
        ->toBe(CheckStatus::Review);

    // answering yes brings the section back to review
    $classification->record(['consequential_decisions' => 'yes'], 'tester');

    expect(ChecklistItem::query()->where('section', 'decisions')->get()
        ->every(fn (ChecklistItem $item): bool => $item->status === CheckStatus::Review))->toBeTrue();
});
