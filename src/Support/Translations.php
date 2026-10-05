<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Support;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Translation\Translator;

/**
 * The package's translation namespaces, and the one place its own strings are looked up.
 *
 * `laranail/ai-compliance` is the canonical namespace: the composer package name, and the one
 * whose overrides are read from `lang/vendor/laranail/ai-compliance/`, which is where
 * `vendor:publish --tag=laranail::ai-compliance-translations` writes them.
 * `laranail-ai-compliance` is the namespace the package translated through before; it is still
 * registered over the same files, so a host calling `__('laranail-ai-compliance::…')` keeps
 * working.
 *
 * Moving the internal lookups to the canonical namespace would, on its own, silently drop every
 * override a host made against the old one -- a file in `lang/vendor/laranail-ai-compliance/` or
 * lines added at runtime with `addLines(…, 'laranail-ai-compliance')`. Both namespaces start from
 * the same packaged files, so they can only disagree when one of them was overridden. When they
 * do, the canonical one wins unless it is still the packaged default, in which case the old
 * namespace's override is what the host asked for.
 *
 * The same rule as laranail/console's `Tools\Support\Translations` (v0.1.5) and
 * laranail/db-console-webui's `Support\Translations`. Two additions: {@see self::lines()} applies
 * the rule leaf by leaf to a whole group of lines, and both lookups accept the translator a caller
 * already holds, so a class that injects one keeps using it.
 *
 * @internal Lookup helper for this package's own strings; not part of the public API.
 */
final class Translations
{
    /** The canonical translation namespace: `__('laranail/ai-compliance::ai-compliance.strings.disclosure.badge')`. */
    public const string NAMESPACE = 'laranail/ai-compliance';

    /**
     * The namespace the package translated through before. Still registered over the same files,
     * and its overrides are still honoured by {@see self::get()} and {@see self::lines()}; it is an
     * alias, not deprecated.
     */
    public const string LEGACY_NAMESPACE = 'laranail-ai-compliance';

    private const string LANG_PATH = __DIR__ . '/../../resources/lang';

    /**
     * The canonical, fully-qualified key: `laranail/ai-compliance::<group>.<item>`.
     */
    public static function key(string $key): string
    {
        return self::NAMESPACE . '::' . $key;
    }

    /**
     * Translate `<group>.<item>` from this package's strings. Returns the canonical key itself when
     * no translator is bound or the key is missing, matching what `trans()` does for a miss.
     *
     * @param array<string, scalar|null> $replace
     */
    public static function get(string $key, array $replace = [], ?string $locale = null, ?Translator $translator = null): string
    {
        $canonicalKey = self::key($key);
        $legacyKey = self::LEGACY_NAMESPACE . '::' . $key;
        $translator ??= self::translator();

        if (! $translator instanceof Translator) {
            return $canonicalKey;
        }

        $canonical = $translator->get($canonicalKey, [], $locale);
        $legacy = $translator->get($legacyKey, [], $locale);

        $useLegacy = $canonical !== $legacy
            && is_string($legacy)
            && $legacy !== $legacyKey
            && ($canonical === $canonicalKey || $canonical === self::packaged($key, $locale, $translator));

        $message = $translator->get($useLegacy ? $legacyKey : $canonicalKey, $replace, $locale);

        return is_string($message) ? $message : $canonicalKey;
    }

    /**
     * A whole group of lines (`ai-compliance.strings`), with the rule of {@see self::get()} applied
     * to each leaf: a leaf overridden only in the old namespace takes that override, and a leaf
     * overridden in the canonical namespace keeps it. Returns `[]` when no translator is bound or
     * the key does not name an array.
     *
     * @return array<array-key, mixed>
     */
    public static function lines(string $key, ?string $locale = null, ?Translator $translator = null): array
    {
        $translator ??= self::translator();

        if (! $translator instanceof Translator) {
            return [];
        }

        $canonical = $translator->get(self::key($key), [], $locale);
        $legacy = $translator->get(self::LEGACY_NAMESPACE . '::' . $key, [], $locale);
        $packaged = self::packaged($key, $locale, $translator);

        $lines = is_array($canonical) ? Arr::dot($canonical) : [];
        $legacyLines = is_array($legacy) ? Arr::dot($legacy) : [];
        $packagedLines = is_array($packaged) ? Arr::dot($packaged) : [];

        foreach ($legacyLines as $leaf => $line) {
            $current = $lines[$leaf] ?? null;

            if ($current === $line) {
                continue;
            }

            if (! array_key_exists($leaf, $lines)
                || (array_key_exists($leaf, $packagedLines) && $current === $packagedLines[$leaf])) {
                $lines[$leaf] = $line;
            }
        }

        return $lines === [] ? [] : Arr::undot($lines);
    }

    private static function translator(): ?Translator
    {
        if (! function_exists('app') || ! app()->bound('translator')) {
            return null;
        }

        $translator = app('translator');

        return $translator instanceof Translator ? $translator : null;
    }

    /**
     * The line as shipped in `resources/lang`, before any override, in the locale the translator
     * would use (requested, else current, else fallback).
     */
    private static function packaged(string $key, ?string $locale, Translator $translator): mixed
    {
        [$group, $item] = array_pad(explode('.', $key, 2), 2, null);

        $locales = array_unique(array_filter([
            $locale,
            $translator->getLocale(),
            method_exists($translator, 'getFallback') ? $translator->getFallback() : null,
        ], is_string(...)));

        foreach ($locales as $candidate) {
            $file = self::LANG_PATH . '/' . $candidate . '/' . $group . '.php';

            if (! is_file($file)) {
                continue;
            }

            $lines = require $file;

            if (! is_array($lines)) {
                continue;
            }

            $line = $item === null ? $lines : Arr::get($lines, (string) $item);

            if ($line !== null) {
                return $line;
            }
        }

        return null;
    }
}
