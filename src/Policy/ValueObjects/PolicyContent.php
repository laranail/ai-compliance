<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\ValueObjects;

use Simtabi\Laranail\AiCompliance\Enums\PolicyType;

/**
 * A policy document ready to serve: compiled, placeholder-substituted, and
 * resolved through the locale fallback chain. `$locale` is the locale
 * actually served, which differs from `$requestedLocale` when a fallback was
 * taken. `$version` is null while the document only exists as a file (no
 * published database version).
 */
final readonly class PolicyContent
{
    /**
     * @param array<string, mixed> $meta
     * @param list<string> $unresolvedPlaceholders
     */
    public function __construct(
        public string $slug,
        public PolicyType $type,
        public string $locale,
        public string $requestedLocale,
        public string $title,
        public string $html,
        public array $meta,
        public ?string $version,
        public array $unresolvedPlaceholders,
    ) {}

    public function isFallback(): bool
    {
        return $this->locale !== $this->requestedLocale;
    }
}
