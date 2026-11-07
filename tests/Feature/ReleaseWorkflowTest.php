<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

/*
 * The npm packages must never be published from a tag push.
 *
 * v0.1.0 is a moving tag and a published registry version is immutable, so an automatic publish fires once and
 * then skips in silence for ever, freezing the registry while the tag moves on. It already happened: GitHub
 * Packages holds @laranail/ai-compliance@0.1.0 from an early tag push, and the runs all reported success.
 *
 * These assertions read the parsed workflow rather than grepping it, so they survive a reformat, and they count
 * what they inspected so a renamed file or job fails instead of passing over nothing.
 */

/** @return array{0: array<string, mixed>, 1: string} the parsed workflow and its raw text */
function releaseWorkflow(): array
{
    $path = dirname(__DIR__, 2) . '/.github/workflows/release.yml';

    expect($path)->toBeFile();

    $raw = (string) file_get_contents($path);
    $parsed = Yaml::parse($raw);

    expect($parsed)->toBeArray()->toHaveKey('jobs');

    return [$parsed, $raw];
}

/** `on:` is a YAML 1.1 boolean, so some parsers key it as true. */
function workflowTriggers(array $parsed): array
{
    return $parsed['on'] ?? $parsed[true] ?? [];
}

it('publishes the npm packages only from a hand-started run, never from a tag push', function (): void {
    [$parsed] = releaseWorkflow();

    expect($parsed['jobs'])->toHaveKey('npm');

    $if = (string) ($parsed['jobs']['npm']['if'] ?? '');

    // A tag push would publish once and then skip for ever after, so the job must require a hand-started run;
    // NPM_PUBLISH_ENABLED stays as the second switch. toContain() is variadic, so neither takes a message.
    expect($if)->not->toBeEmpty('the npm job must stay gated')
        ->toContain("github.event_name == 'workflow_dispatch'")
        ->toContain('vars.NPM_PUBLISH_ENABLED');

    expect(workflowTriggers($parsed))->toHaveKey('workflow_dispatch');
});

it('still creates the GitHub release from the tag, which is the half that must stay automatic', function (): void {
    [$parsed] = releaseWorkflow();

    $triggers = workflowTriggers($parsed);

    expect($parsed['jobs'])->toHaveKey('release')
        ->and((string) ($parsed['jobs']['release']['if'] ?? ''))->toContain("github.event_name == 'push'")
        ->and($triggers)->toHaveKey('push')
        ->and($triggers['push'])->toHaveKey('tags')
        ->and($triggers['push']['tags'])->not->toBeEmpty();
});

it('never derives the published version from the tag', function (): void {
    [$parsed, $raw] = releaseWorkflow();

    // The tag names the commit to check out. It must not name the version that gets published, or every
    // release is 0.1.0 for ever, because that is the only tag this repository will ever have.
    // Stamping the tag version onto the workspaces is what froze the registry at 0.1.0.
    expect($raw)->not->toContain('npm version "$version"');

    $steps = $parsed['jobs']['npm']['steps'] ?? [];
    $probe = null;

    foreach ($steps as $step) {
        if (($step['id'] ?? null) === 'published') {
            $probe = $step;
        }
    }

    // The version probed must be the manifest's, not the tag's, and a run that published nothing must warn
    // rather than read as a clean release.
    expect($probe)->not->toBeNull('the already-published probe must still exist')
        ->and((string) $probe['run'])->toContain("require('./packages/core/package.json').version")
        ->and((string) $probe['run'])->toContain('::warning::');
});

it('keeps the three manifests in lockstep on a version that is not the tag', function (): void {
    $root = dirname(__DIR__, 2);
    $versions = [];

    foreach (['core', 'react', 'vue'] as $pkg) {
        $manifest = json_decode((string) file_get_contents("{$root}/packages/{$pkg}/package.json"), true);
        $versions[$pkg] = $manifest['version'] ?? null;
    }

    expect($versions)->toHaveCount(3)
        ->and(array_filter($versions))->toHaveCount(3)
        ->and(array_unique($versions))->toHaveCount(1, 'the three packages release in lockstep');

    // 0.1.0 is on GitHub Packages and immutable. A manifest still on it cannot publish anything.
    // 0.1.0 is already published and frozen, so a manifest still on it can publish nothing.
    expect($versions['core'])->not->toBe('0.1.0');
});
