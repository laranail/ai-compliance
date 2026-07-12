<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\Versioning;

/**
 * Policy version strings are simple major.minor counters: documents start at
 * 1.0 and every new draft bumps the minor. Operators who need a major bump
 * (a materially different policy) can set it explicitly through the api.
 */
final class VersionNumber
{
    public static function first(): string
    {
        return '1.0';
    }

    public static function next(string $latest): string
    {
        if (preg_match('/^(\d+)\.(\d+)$/', $latest, $matches) !== 1) {
            return self::first();
        }

        return sprintf('%d.%d', (int) $matches[1], (int) $matches[2] + 1);
    }
}
