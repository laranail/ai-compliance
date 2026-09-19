<?php

declare(strict_types=1);

use Simtabi\Laranail\AiCompliance\Console\Commands\ExportCommand;
use Simtabi\Laranail\Package\Tools\Testing\AssertsDriverContract;

uses(AssertsDriverContract::class);

/**
 * `stringOption('x') !== '' ? stringOption('x') : null` IS `strOption('x')`,
 * written out because `strOption()` lived in a package this one did not have.
 * Four filters were built that way, each calling the accessor twice.
 */
it('resolves export filters with the absence-preserving accessor', function (): void {
    $source = (string) file_get_contents((string) (new ReflectionClass(ExportCommand::class))->getFileName());

    expect($source)->not->toContain("!== '' ? \$this->stringOption")
        ->and(substr_count($source, '$this->strOption('))->toBe(4);
});

it('has no console option defaulted by a null-only test', function (): void {
    $this->assertNoNullOnlyOptionGuards(__DIR__ . '/../../src');
});
