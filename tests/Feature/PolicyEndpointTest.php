<?php

declare(strict_types=1);

it('serves a compiled policy document', function (): void {
    $response = $this->getJson('/ai-compliance/policies/transparency');

    $response->assertOk()
        ->assertJsonPath('slug', 'transparency')
        ->assertJsonPath('type', 'policy')
        ->assertJsonPath('locale', 'en')
        ->assertJsonPath('fallback', false)
        ->assertJsonPath('version', null);

    expect($response->json('title'))->toBe('How Acme App uses AI')
        ->and($response->json('html'))->toContain('Acme')
        ->and($response->json('html'))->not->toContain('{{company}}')
        ->and($response->json('html'))->toContain('<ai-c data-component=')
        ->and($response->json('unresolved_placeholders'))->toBeArray();
});

it('reports the fallback when the requested locale is not translated', function (): void {
    $response = $this->getJson('/ai-compliance/policies/transparency?locale=de');

    $response->assertOk()
        ->assertJsonPath('locale', 'en')
        ->assertJsonPath('requested_locale', 'de')
        ->assertJsonPath('fallback', true);
});

it('returns 404 for unknown documents', function (): void {
    $this->getJson('/ai-compliance/policies/does-not-exist')->assertNotFound();
});

it('serves consent texts and disclosures as documents too', function (): void {
    $this->getJson('/ai-compliance/policies/consent.ai_training')
        ->assertOk()
        ->assertJsonPath('type', 'consent_text');

    $this->getJson('/ai-compliance/policies/disclosure.chat')
        ->assertOk()
        ->assertJsonPath('type', 'disclosure');
});
