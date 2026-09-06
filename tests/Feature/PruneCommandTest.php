<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;

uses(RefreshDatabase::class);

it('prunes expired activity events and logs the pruning itself', function (): void {
    config()->set('laranail.ai-compliance.retention.activity_events', 30);

    ActivityEvent::factory()->create(['recorded_at' => now()->subDays(40)]);
    ActivityEvent::factory()->create(['recorded_at' => now()->subDays(5)]);

    $this->artisan('laranail::ai-compliance.prune')->assertSuccessful();

    expect(ActivityEvent::query()->count())->toBe(2); // fresh one + the prune event itself

    $pruneEvent = ActivityEvent::query()->where('event_type', ActivityType::SettingChange->value)->firstOrFail();

    expect($pruneEvent->context)->toMatchArray(['action' => 'pruned', 'activity_events' => 1]);
});

it('keeps everything when no retention is configured', function (): void {
    ActivityEvent::factory()->create(['recorded_at' => now()->subYears(3)]);

    $this->artisan('laranail::ai-compliance.prune')->assertSuccessful();

    expect(ActivityEvent::query()->where('event_type', '!=', ActivityType::SettingChange->value)->count())->toBe(1);
});

it('refuses to prune consent history without a configured policy', function (): void {
    $this->artisan('laranail::ai-compliance.prune', ['--consents' => true])
        ->expectsOutputToContain('retention.consent_records')
        ->assertFailed();
});

it('prunes only superseded consent history, never the current state', function (): void {
    config()->set('laranail.ai-compliance.retention.consent_records', 30);

    $user = makeUser();
    $consent = app(ConsentManager::class);

    // an old superseded row, an old row that is still current, and fresh history
    $old = $consent->grant($user, 'ai_training');
    ConsentRecord::query()->whereKey($old->id)->toBase()->update(['recorded_at' => now()->subDays(60)]);

    $oldCurrent = $consent->grant($user, 'ai_chatbot');
    ConsentRecord::query()->whereKey($oldCurrent->id)->toBase()->update(['recorded_at' => now()->subDays(60)]);

    $consent->deny($user, 'ai_training'); // supersedes the old ai_training row

    $this->artisan('laranail::ai-compliance.prune', ['--consents' => true])->assertSuccessful();

    expect(ConsentRecord::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(ConsentRecord::query()->whereKey($oldCurrent->id)->exists())->toBeTrue()
        ->and($consent->granted($user, 'ai_chatbot'))->toBeTrue()
        ->and($consent->granted($user, 'ai_training'))->toBeFalse();
});
