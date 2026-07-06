<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Models\Provider;

uses(RefreshDatabase::class);

it('erases a user completely and logs the dsr action (spec acceptance 2)', function (): void {
    $user = makeUser();
    $consent = app(ConsentManager::class);

    Provider::factory()->create(['name' => 'assistant', 'vendor' => 'OpenAI']);
    $consent->grant($user, 'ai_training');
    $consent->provider('assistant')->forSubject($user)->record('chat');

    // the package holds consent rows and activity events pointing at the user
    expect($consent->exportSubject($user)['consents'])->not->toBeEmpty()
        ->and(ActivityEvent::query()->where('subjectable_id', $user->id)->count())->toBeGreaterThanOrEqual(2);

    $consent->forgetSubject($user);

    // nothing points at the user anymore, history rows remain anonymous
    expect(ConsentRecord::query()->where('subjectable_id', $user->id)->count())->toBe(0)
        ->and(ActivityEvent::query()->where('subjectable_id', $user->id)->count())->toBe(0)
        ->and(ConsentRecord::query()->count())->toBe(1)
        ->and($consent->exportSubject($user))->toBe(['consents' => [], 'activity' => []])
        ->and(ActivityEvent::query()->where('event_type', ActivityType::DsrAction->value)->count())->toBe(1);
});

it('scrubs guest keys out of activity context on erasure', function (): void {
    $guestKey = 'g_' . str_repeat('f', 32);
    $consent = app(ConsentManager::class);

    $consent->grant($guestKey, 'ai_chatbot');

    expect(ActivityEvent::query()->where('context->guest_key', $guestKey)->count())->toBe(1);

    $consent->forgetSubject($guestKey);

    expect(ActivityEvent::query()->where('context->guest_key', $guestKey)->count())->toBe(0)
        ->and(ConsentRecord::query()->where('guest_key', $guestKey)->count())->toBe(0)
        ->and($consent->exportSubject($guestKey))->toBe(['consents' => [], 'activity' => []]);

    // the anonymized consent-change event survives, just without the key
    $anonymized = ActivityEvent::query()->where('event_type', ActivityType::ConsentChange->value)->firstOrFail();
    expect($anonymized->context)->not->toHaveKey('guest_key');
});
