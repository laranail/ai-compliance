<?php

declare(strict_types=1);

use Simtabi\Laranail\AiCompliance\Policy\PlaceholderRegistry;

it('substitutes configured placeholders', function (): void {
    $result = $this->app->make(PlaceholderRegistry::class)
        ->substitute('Contact {{company}} at {{contact_email}}.');

    expect($result->text)->toBe('Contact Acme at privacy@acme.test.')
        ->and($result->unresolved)->toBe([]);
});

it('reports unresolved simple and prose placeholders', function (): void {
    $result = $this->app->make(PlaceholderRegistry::class)
        ->substitute('{{company}} uses {{unknown_key}} and {{list the features, e.g. "chat"}}.');

    expect($result->text)->toContain('Acme')
        ->and($result->text)->toContain('{{unknown_key}}')
        ->and($result->unresolved)->toContain('unknown_key')
        ->and($result->unresolved)->toContain('list the features, e.g. "chat"');
});

it('supports runtime resolvers registered by the host app', function (): void {
    $registry = $this->app->make(PlaceholderRegistry::class);
    $registry->register('effective_date', static fn (): string => '2026-07-05');

    expect($registry->substitute('Effective {{effective_date}}.')->text)
        ->toBe('Effective 2026-07-05.');
});

it('leaves null-valued placeholders unresolved instead of substituting empty text', function (): void {
    config()->set('laranail.ai-compliance.placeholders.jurisdiction');

    $result = $this->app->make(PlaceholderRegistry::class)->substitute('Law of {{jurisdiction}}.');

    expect($result->text)->toContain('{{jurisdiction}}')
        ->and($result->unresolved)->toContain('jurisdiction');
});
