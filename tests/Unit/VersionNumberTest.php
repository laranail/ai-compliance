<?php

declare(strict_types=1);

use Simtabi\Laranail\AiCompliance\Policy\Versioning\VersionNumber;

it('starts documents at 1.0', function (): void {
    expect(VersionNumber::first())->toBe('1.0');
});

it('bumps the minor for every new draft', function (): void {
    expect(VersionNumber::next('1.0'))->toBe('1.1')
        ->and(VersionNumber::next('1.9'))->toBe('1.10')
        ->and(VersionNumber::next('2.3'))->toBe('2.4');
});

it('recovers from malformed version strings', function (): void {
    expect(VersionNumber::next('not-a-version'))->toBe('1.0');
});
