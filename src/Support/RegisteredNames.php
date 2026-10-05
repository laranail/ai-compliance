<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Support;

/**
 * The names this package registers into host-owned registries, in one place.
 *
 * Middleware aliases, Livewire component names, Filament slugs and browser
 * event names each live in a flat map shared with the host application and
 * every other package, so each carries the vendor and the package slug. The
 * pre-0.1 bare names are kept working as deprecated aliases and listed here
 * beside their replacements, so the provider, the Filament classes and the
 * naming test read the same map.
 */
final class RegisteredNames
{
    /** Route middleware alias for {@see \Simtabi\Laranail\AiCompliance\Http\Middleware\EnsureConsent}. */
    public const string CONSENT_MIDDLEWARE = 'laranail-ai-compliance.consent';

    /** Route middleware alias for {@see \Simtabi\Laranail\AiCompliance\Http\Middleware\EnsureFeature}. */
    public const string FEATURE_MIDDLEWARE = 'laranail-ai-compliance.feature';

    /**
     * @deprecated the bare `ai.consent` middleware alias. Use {@see self::CONSENT_MIDDLEWARE}.
     *             Earliest removal: the next minor after 0.1.
     */
    public const string LEGACY_CONSENT_MIDDLEWARE = 'ai.consent';

    /**
     * @deprecated the bare `ai.feature` middleware alias. Use {@see self::FEATURE_MIDDLEWARE}.
     *             Earliest removal: the next minor after 0.1.
     */
    public const string LEGACY_FEATURE_MIDDLEWARE = 'ai.feature';

    public const string LIVEWIRE_PREFIX = 'laranail-ai-compliance.';

    /**
     * @deprecated the bare `ai-compliance.` Livewire prefix. Use {@see self::LIVEWIRE_PREFIX}.
     *             Earliest removal: the next minor after 0.1.
     */
    public const string LEGACY_LIVEWIRE_PREFIX = 'ai-compliance.';

    /** Browser event dispatched whenever a consent changes through a Livewire surface. */
    public const string CONSENT_CHANGED_EVENT = 'laranail-ai-compliance:consent-changed';

    /**
     * @deprecated the bare `ai-compliance:consent-changed` browser event, still dispatched beside
     *             {@see self::CONSENT_CHANGED_EVENT}. Earliest removal: the next minor after 0.1.
     */
    public const string LEGACY_CONSENT_CHANGED_EVENT = 'ai-compliance:consent-changed';

    /** Every Filament page and resource slug sits under this segment. */
    public const string FILAMENT_SLUG_PREFIX = 'laranail-ai-compliance';

    /** @var array<string, true> legacy names already announced in this process */
    private static array $announced = [];

    /**
     * Deprecated bare Livewire name => scoped Livewire name.
     *
     * @return array<string, string>
     */
    public static function legacyLivewireMap(): array
    {
        $map = [];

        foreach (['consent-preferences', 'reconsent-prompt'] as $component) {
            $map[self::LEGACY_LIVEWIRE_PREFIX . $component] = self::LIVEWIRE_PREFIX . $component;
        }

        return $map;
    }

    /** The scoped Filament slug for a page or resource, `laranail-ai-compliance/<slug>`. */
    public static function filamentSlug(string $slug): string
    {
        return self::FILAMENT_SLUG_PREFIX . '/' . $slug;
    }

    /**
     * Raise one E_USER_DEPRECATED notice per process for a deprecated name.
     */
    public static function announce(string $key, string $deprecated, string $replacement, string $kind): void
    {
        if (isset(self::$announced[$key])) {
            return;
        }

        self::$announced[$key] = true;

        trigger_error(sprintf(
            'laranail/ai-compliance: the %s [%s] is deprecated and will be removed no earlier than the next minor after 0.1; use [%s].',
            $kind,
            $deprecated,
            $replacement,
        ), E_USER_DEPRECATED);
    }

    /**
     * Raise the deprecation for a Livewire component mounted under its bare name. Any other
     * name is ignored.
     */
    public static function announceIfLegacyLivewire(string $name): void
    {
        $scoped = self::legacyLivewireMap()[$name] ?? null;

        if ($scoped !== null) {
            self::announce('livewire:' . $name, $name, $scoped, 'Livewire component name');
        }
    }

    /**
     * Forget which names were announced. For test suites.
     */
    public static function forgetWarnings(): void
    {
        self::$announced = [];
    }
}
