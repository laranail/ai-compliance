<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Simtabi\Laranail\AiCompliance\Policy\PolicyFileLoader;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\CompiledPolicy;

it('falls back to the default locale and reports the locale actually served', function (): void {
    $content = $this->app->make(PolicyRepository::class)->find('transparency', 'de');

    expect($content)->not->toBeNull()
        ->and($content->locale)->toBe('en')
        ->and($content->requestedLocale)->toBe('de')
        ->and($content->isFallback())->toBeTrue();
});

it('serves a translated file when the locale has one', function (): void {
    config()->set('laranail.ai-compliance.policies.path', __DIR__ . '/../fixtures/policies');
    $this->app->make(PolicyFileLoader::class)->flush();

    $content = $this->app->make(PolicyRepository::class)->find('transparency', 'de');

    expect($content?->locale)->toBe('de')
        ->and($content?->html)->toContain('TESTMARKER_DE')
        ->and($content?->html)->toContain('Acme')
        ->and($content?->isFallback())->toBeFalse();
});

it('walks the configured fallback chain for regional locales', function (): void {
    config()->set('laranail.ai-compliance.policies.path', __DIR__ . '/../fixtures/policies');
    config()->set('laranail.ai-compliance.locales.fallbacks', ['de-CH' => ['de']]);
    $this->app->make(PolicyFileLoader::class)->flush();

    $repository = $this->app->make(PolicyRepository::class);

    expect($repository->fallbackChain('de-CH'))->toBe(['de-CH', 'de', 'en'])
        ->and($repository->find('transparency', 'de-CH')?->locale)->toBe('de');
});

it('substitutes placeholders at serve time and reports the unresolved ones', function (): void {
    $content = $this->app->make(PolicyRepository::class)->find('transparency', 'en');

    expect($content?->html)->toContain('Acme')
        ->and($content?->html)->not->toContain('{{company}}')
        ->and($content?->unresolvedPlaceholders)->not->toBeEmpty();
});

it('returns null for unknown slugs', function (): void {
    expect($this->app->make(PolicyRepository::class)->find('does-not-exist'))->toBeNull();
});

it('serves compiled policies from the cache when the checksum matches', function (): void {
    config()->set('laranail.ai-compliance.policies.cache.enabled', true);

    $file = $this->app->make(PolicyFileLoader::class)->find('transparency', 'en');
    expect($file)->not->toBeNull();

    Cache::forever(
        sprintf('laranail.ai-compliance.policy.%s.%s.%s', $file->slug, $file->locale, $file->checksum),
        new CompiledPolicy('<p>SENTINEL_FROM_CACHE</p>', ['title' => 'Cached'], $file->checksum),
    );

    $content = $this->app->make(PolicyRepository::class)->find('transparency', 'en');

    expect($content?->html)->toContain('SENTINEL_FROM_CACHE');
});

it('recompiles when the cache is disabled', function (): void {
    config()->set('laranail.ai-compliance.policies.cache.enabled', false);

    $file = $this->app->make(PolicyFileLoader::class)->find('transparency', 'en');

    Cache::forever(
        sprintf('laranail.ai-compliance.policy.%s.%s.%s', $file->slug, $file->locale, $file->checksum),
        new CompiledPolicy('<p>SENTINEL_FROM_CACHE</p>', [], $file->checksum),
    );

    $content = $this->app->make(PolicyRepository::class)->find('transparency', 'en');

    expect($content?->html)->not->toContain('SENTINEL_FROM_CACHE');
});

it('writes compiled policies to the cache on a miss', function (): void {
    config()->set('laranail.ai-compliance.policies.cache.enabled', true);

    $file = $this->app->make(PolicyFileLoader::class)->find('transparency', 'en');
    $key = sprintf('laranail.ai-compliance.policy.%s.%s.%s', $file->slug, $file->locale, $file->checksum);

    expect(Cache::has($key))->toBeFalse();

    $this->app->make(PolicyRepository::class)->find('transparency', 'en');

    expect(Cache::has($key))->toBeTrue()
        ->and(Cache::get($key))->toBeInstanceOf(CompiledPolicy::class);
});
