<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Activity\ActivityChain;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;

uses(RefreshDatabase::class);

it('leaves hash_prev empty when the chain tier is off', function (): void {
    app(ActivityRecorder::class)->record(ActivityType::SettingChange, context: ['n' => 1]);

    expect(ActivityEvent::query()->firstOrFail()->hash_prev)->toBeNull();
});

it('links every event and verifies an intact chain', function (): void {
    config()->set('laranail.ai-compliance.activity.hash_chain', true);

    $recorder = app(ActivityRecorder::class);

    foreach ([1, 2, 3, 4] as $n) {
        $recorder->record(ActivityType::SettingChange, context: ['n' => $n]);
    }

    expect(ActivityEvent::query()->whereNull('hash_prev')->count())->toBe(0);

    $result = app(ActivityChain::class)->verify();

    expect($result['valid'])->toBeTrue()
        ->and($result['checked'])->toBe(4)
        ->and($result['broken_at'])->toBeNull();

    $this->artisan('laranail::ai-compliance.verify-chain')
        ->expectsOutputToContain('chain intact across 4')
        ->assertSuccessful();
});

it('detects tampering with any historic event', function (): void {
    config()->set('laranail.ai-compliance.activity.hash_chain', true);

    $recorder = app(ActivityRecorder::class);
    $recorder->record(ActivityType::SettingChange, context: ['n' => 1]);
    $second = $recorder->record(ActivityType::SettingChange, context: ['n' => 2]);
    $recorder->record(ActivityType::SettingChange, context: ['n' => 3]);

    // alter a middle row behind eloquent's back
    DB::table((string) config('laranail.ai-compliance.tables.activity_events'))
        ->where('id', $second->id)
        ->update(['context' => json_encode(['n' => 'FORGED'])]);

    $result = app(ActivityChain::class)->verify();

    expect($result['valid'])->toBeFalse();

    $this->artisan('laranail::ai-compliance.verify-chain')
        ->expectsOutputToContain('BROKEN')
        ->assertFailed();
});

it('starts a fresh chain when enabled on an existing unchained log', function (): void {
    $recorder = app(ActivityRecorder::class);
    $recorder->record(ActivityType::SettingChange, context: ['old' => true]);

    config()->set('laranail.ai-compliance.activity.hash_chain', true);
    $recorder->record(ActivityType::SettingChange, context: ['new' => true]);
    $recorder->record(ActivityType::SettingChange, context: ['newer' => true]);

    $result = app(ActivityChain::class)->verify();

    expect($result['valid'])->toBeTrue()
        ->and($result['checked'])->toBe(2); // the unchained event is outside the chain
});
