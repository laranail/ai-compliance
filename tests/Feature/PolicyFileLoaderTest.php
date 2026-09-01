<?php

declare(strict_types=1);

use Simtabi\Laranail\AiCompliance\Enums\PolicyType;
use Simtabi\Laranail\AiCompliance\Policy\PolicyFileLoader;

it('discovers the fourteen shipped policy documents', function (): void {
    $files = $this->app->make(PolicyFileLoader::class)->all('en');

    expect($files)->toHaveCount(14);

    $slugs = array_map(static fn ($file): string => $file->slug, $files);

    expect($slugs)->toContain('transparency')
        ->toContain('training-data')
        ->toContain('automated-decisions')
        ->toContain('data-protection')
        ->toContain('acceptable-use')
        ->toContain('incident-response')
        ->toContain('vendor')
        ->toContain('consent.ai_training')
        ->toContain('consent.ai_chatbot')
        ->toContain('consent.ai_recommendations')
        ->toContain('consent.ai_personalization')
        ->toContain('disclosure.chat')
        ->toContain('disclosure.content')
        ->toContain('disclosure.decision');
});

it('derives the document type from the directory', function (): void {
    $loader = $this->app->make(PolicyFileLoader::class);

    expect($loader->find('transparency', 'en')?->type)->toBe(PolicyType::Policy)
        ->and($loader->find('consent.ai_training', 'en')?->type)->toBe(PolicyType::ConsentText)
        ->and($loader->find('disclosure.chat', 'en')?->type)->toBe(PolicyType::Disclosure);
});

it('prefers app-published files over the shipped defaults', function (): void {
    config()->set('laranail.ai-compliance.policies.path', __DIR__.'/../Fixtures/policies-override');

    $loader = $this->app->make(PolicyFileLoader::class);
    $loader->flush();

    $file = $loader->find('transparency', 'en');

    expect($file?->contents)->toContain('OVERRIDE_MARKER');

    // the rest of the shipped set is still visible alongside the override
    expect($loader->all('en'))->toHaveCount(14);
});

it('returns null for locales that have no files', function (): void {
    expect($this->app->make(PolicyFileLoader::class)->find('transparency', 'sw'))->toBeNull();
});
