<?php

declare(strict_types=1);

it('serves the contract-1 boot payload', function (): void {
    $response = $this->getJson('/ai-compliance/boot');

    $response->assertOk()
        ->assertJsonPath('contract', 1)
        ->assertJsonPath('locale', 'en')
        ->assertJsonPath('fallback_locale', 'en')
        ->assertJsonPath('guest_key', null)
        ->assertJsonCount(4, 'consent.types')
        ->assertJsonPath('consent.reconsent', [])
        ->assertJsonStructure([
            'contract',
            'locale',
            'fallback_locale',
            'consent' => ['types', 'state', 'reconsent'],
            'disclosures' => ['chat', 'content', 'decision'],
            'documents',
            'strings',
            'endpoints' => ['boot', 'policy'],
            'guest_key',
        ]);
});

it('describes every consent type with label, basis, state, and short text', function (): void {
    $types = $this->getJson('/ai-compliance/boot')->json('consent.types');

    $bySlug = collect($types)->keyBy('slug');

    expect($bySlug->keys()->all())->toContain('ai_training', 'ai_chatbot', 'ai_recommendations', 'ai_personalization');

    $training = $bySlug->get('ai_training');

    expect($training['label'])->toBe('AI training permissions')
        ->and($training['legal_basis'])->toBe('consent')
        ->and($training['default_state'])->toBe('denied')
        ->and($training['short_html'])->toContain('Acme');
});

it('defaults every consent state to the configured default with no record', function (): void {
    $state = $this->getJson('/ai-compliance/boot')->json('consent.state');

    expect($state)->toHaveKeys(['ai_training', 'ai_chatbot', 'ai_recommendations', 'ai_personalization'])
        ->and($state['ai_training'])->toBe([
            'status' => 'denied',
            'recorded_at' => null,
            'policy_version' => null,
        ]);
});

it('serves substituted disclosure texts per surface', function (): void {
    $response = $this->getJson('/ai-compliance/boot');

    expect($response->json('disclosures.chat.html'))->toContain('AI assistant')
        ->and($response->json('disclosures.content.html'))->toContain('Acme App')
        ->and($response->json('disclosures.decision.html'))->toContain('privacy@acme.test');
});

it('indexes the long-form policy documents with urls', function (): void {
    $documents = $this->getJson('/ai-compliance/boot')->json('documents');

    expect($documents)->toHaveKey('transparency')
        ->and($documents)->not->toHaveKey('consent.ai_training')
        ->and($documents['transparency']['title'])->toContain('Acme App')
        ->and($documents['transparency']['url'])->toContain('/ai-compliance/policies/transparency');
});

it('serves translatable component strings', function (): void {
    $strings = $this->getJson('/ai-compliance/boot')->json('strings');

    expect($strings)->toHaveKey('preferences.save')
        ->and($strings['preferences.save'])->toBe('Save choices');
});

it('honours an explicit locale request', function (): void {
    $response = $this->getJson('/ai-compliance/boot?locale=de');

    $response->assertOk()->assertJsonPath('locale', 'de');

    // no de files ship, so disclosure content still resolves through the fallback
    expect($response->json('disclosures.chat.html'))->toContain('AI assistant');
});
