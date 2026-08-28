<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;

uses(RefreshDatabase::class);

it('records a guest consent, issuing the guest cookie', function (): void {
    $response = $this->postJson('/ai-compliance/consents', [
        'type'   => 'ai_chatbot',
        'status' => 'granted',
    ]);

    $response->assertCreated()
        ->assertCookie('laranail_ai_compliance_guest')
        ->assertJsonPath('data.type', 'ai_chatbot')
        ->assertJsonPath('data.status', 'granted')
        ->assertJsonPath('state.ai_chatbot.status', 'granted');

    $record = ConsentRecord::query()->firstOrFail();
    expect($record->guest_key)->toStartWith('g_')
        ->and($record->source)->toBe('api');
});

it('reuses the existing guest key from the cookie', function (): void {
    $key = 'g_' . str_repeat('c', 40);

    $this->withCredentials()->withCookie('laranail_ai_compliance_guest', $key)
        ->postJson('/ai-compliance/consents', ['type' => 'ai_training', 'status' => 'granted'])
        ->assertCreated();

    expect(ConsentRecord::query()->firstOrFail()->guest_key)->toBe($key);

    // and boot reports that guest's real state
    $boot = $this->withCredentials()->withCookie('laranail_ai_compliance_guest', $key)->getJson('/ai-compliance/boot');

    $boot->assertJsonPath('consent.state.ai_training.status', 'granted')
        ->assertJsonPath('guest_key', $key);
});

it('records consent for the authenticated user', function (): void {
    $user = makeUser();

    $this->actingAs($user)
        ->postJson('/ai-compliance/consents', ['type' => 'ai_personalization', 'status' => 'granted'])
        ->assertCreated();

    $record = ConsentRecord::query()->firstOrFail();

    expect($record->subjectable_type)->toBe('user')
        ->and((int) $record->subjectable_id)->toBe($user->id)
        ->and($record->guest_key)->toBeNull();
});

it('validates type and status', function (): void {
    $this->postJson('/ai-compliance/consents', ['type' => 'nope', 'status' => 'granted'])
        ->assertUnprocessable();

    $this->postJson('/ai-compliance/consents', ['type' => 'ai_training', 'status' => 'maybe'])
        ->assertUnprocessable();
});

it('serves the real consent state and reconsent list in the boot payload for users', function (): void {
    app(PolicySync::class)->sync();
    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_training');

    $boot = $this->actingAs($user)->getJson('/ai-compliance/boot');

    $boot->assertOk()
        ->assertJsonPath('consent.state.ai_training.status', 'granted')
        ->assertJsonPath('consent.state.ai_training.policy_version', '1.0')
        ->assertJsonPath('consent.reconsent', [])
        ->assertJsonPath('guest_key', null);

    expect($boot->json('endpoints.consents'))->toContain('/ai-compliance/consents');
});
