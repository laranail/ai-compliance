<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Simtabi\Laranail\AiCompliance\Policy\PolicyCompiler;

it('compiles a registered shortcode to an ai-c element with props and fallback', function (): void {
    $compiled = $this->app->make(PolicyCompiler::class)->compile(policyFile(
        'Choose: [[consent-toggle type="ai_training" fallback="Manage in settings."]]',
    ));

    expect($compiled->html)
        ->toContain('<ai-c data-component="consent-toggle"')
        ->toContain('&quot;type&quot;:&quot;ai_training&quot;')
        ->toContain('>Manage in settings.</ai-c>');
});

it('keeps the fallback out of the props json', function (): void {
    $compiled = $this->app->make(PolicyCompiler::class)->compile(policyFile(
        '[[consent-panel fallback="See settings."]]',
    ));

    expect($compiled->html)->not->toContain('fallback&quot;')
        ->and($compiled->html)->toContain('>See settings.</ai-c>');
});

it('renders only the fallback text for unknown shortcodes and logs a warning', function (): void {
    Log::spy();

    $compiled = $this->app->make(PolicyCompiler::class)->compile(policyFile(
        'Before [[not-a-component fallback="plain text instead"]] after.',
    ));

    expect($compiled->html)->toContain('plain text instead')
        ->and($compiled->html)->not->toContain('<ai-c');

    Log::shouldHaveReceived('warning')->once();
});

it('compiles every shortcode in the registered vocabulary', function (): void {
    $compiler = $this->app->make(PolicyCompiler::class);

    $registered = config('laranail.ai-compliance.shortcodes');
    expect($registered)->not->toBeEmpty();

    foreach ($registered as $name) {
        $compiled = $compiler->compile(policyFile(sprintf('[[%s]]', $name)));

        expect($compiled->html)->toContain(sprintf('<ai-c data-component="%s"', $name));
    }
});

it('escapes raw html in policy sources', function (): void {
    $compiled = $this->app->make(PolicyCompiler::class)->compile(policyFile(
        "Safe text.\n\n<script>alert(1)</script>",
    ));

    expect($compiled->html)->not->toContain('<script>')
        ->and($compiled->html)->toContain('&lt;script&gt;');
});

it('leaves markdown that only resembles a shortcode alone', function (): void {
    $compiled = $this->app->make(PolicyCompiler::class)->compile(policyFile(
        'An array access like [[0]] is not a shortcode.',
    ));

    expect($compiled->html)->toContain('[[0]]');
});
