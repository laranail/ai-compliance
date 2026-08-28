<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Gate::define('ai-compliance:audit', fn (GenericUser $user): bool => (bool) ($user->audit ?? false));
    Gate::define('ai-compliance:manage', fn (GenericUser $user): bool => (bool) ($user->manage ?? false));
});

it('serves the log with filters, public ids only, and logs the read itself', function (): void {
    ActivityEvent::factory()->ofType(ActivityType::ConsentChange)->create(['recorded_at' => now()->subDay()]);
    ActivityEvent::factory()->ofType(ActivityType::Inference)->create(['recorded_at' => now()]);

    $auditor = new GenericUser(['id' => 7, 'audit' => true]);

    $response = $this->actingAs($auditor)
        ->getJson('/ai-compliance/admin/activity?type=inference')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.event_type'))->toBe('inference')
        ->and($response->json('data.0.id'))->toHaveLength(26)
        ->and($response->json('data.0'))->not->toHaveKeys(['id_numeric', 'subjectable_id'])
        ->and($response->json('meta.total'))->toBe(1);

    // fr-10: the read is in the log, attributed, once per request
    $reads = ActivityEvent::query()->where('event_type', ActivityType::LogRead->value)->get();

    expect($reads)->toHaveCount(1)
        ->and($reads->first()?->context)->toMatchArray(['log' => 'activity', 'reader' => '7']);
});

it('denies guests and exposes chain state to auditors', function (): void {
    $this->getJson('/ai-compliance/admin/activity')->assertForbidden();

    $auditor = new GenericUser(['id' => 7, 'audit' => true]);

    $this->actingAs($auditor)->getJson('/ai-compliance/admin/activity/chain')
        ->assertOk()
        ->assertJsonPath('data.valid', true)
        ->assertJsonPath('data.enabled', false);
});
