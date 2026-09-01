<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::middleware(['web', 'ai.consent:ai_chatbot'])
        ->get('/test-chat', static fn (): string => 'chat ok');
});

it('blocks subjects without the required consent', function (): void {
    $this->getJson('/test-chat')->assertForbidden();

    $user = makeUser();
    $this->actingAs($user)->getJson('/test-chat')->assertForbidden();
});

it('lets consented users through', function (): void {
    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_chatbot');

    $this->actingAs($user)->getJson('/test-chat')->assertOk()->assertSee('chat ok');
});

it('lets consented guests through and blocks them again after withdrawal', function (): void {
    $key = 'g_'.str_repeat('d', 40);

    app(ConsentManager::class)->grant($key, 'ai_chatbot');

    $this->withCredentials()->withCookie('laranail_ai_compliance_guest', $key)
        ->getJson('/test-chat')
        ->assertOk();

    app(ConsentManager::class)->withdraw($key, 'ai_chatbot');

    $this->withCredentials()->withCookie('laranail_ai_compliance_guest', $key)
        ->getJson('/test-chat')
        ->assertForbidden();
});
