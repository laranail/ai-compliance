<?php

declare(strict_types=1);

use Simtabi\Laranail\AiCompliance\Policy\PolicyCompiler;
use Simtabi\Laranail\AiCompliance\Policy\PolicyFileLoader;

it('parses yaml frontmatter into meta and keeps it out of the html', function (): void {
    $compiled = $this->app->make(PolicyCompiler::class)->compile(policyFile(<<<'MD'
        ---
        title: Test {{company}} policy
        type: consent_text
        short: "Short {{company}} text"
        ---

        Body with **bold** text.
        MD));

    expect($compiled->meta)->toHaveKey('title', 'Test {{company}} policy')
        ->toHaveKey('short', 'Short {{company}} text')
        ->and($compiled->title())->toBe('Test {{company}} policy')
        ->and($compiled->short())->toBe('Short {{company}} text')
        ->and($compiled->html)->toContain('<strong>bold</strong>')
        ->and($compiled->html)->not->toContain('title:');
});

it('leaves placeholders intact in compiled html', function (): void {
    $compiled = $this->app->make(PolicyCompiler::class)->compile(policyFile(
        'Operated by {{company}}.',
    ));

    expect($compiled->html)->toContain('{{company}}');
});

it('carries the source checksum through compilation', function (): void {
    $file = policyFile('Some content.');

    $compiled = $this->app->make(PolicyCompiler::class)->compile($file);

    expect($compiled->checksum)->toBe($file->checksum)
        ->and($compiled->checksum)->toBe(hash('sha256', 'Some content.'));
});

it('compiles inline short texts without a wrapping paragraph', function (): void {
    $html = $this->app->make(PolicyCompiler::class)->inline('Allow **{{company}}** to train.');

    expect($html)->toBe('Allow <strong>{{company}}</strong> to train.');
});

it('compiles the shipped documents without frontmatter leaking into output', function (): void {
    $loader = $this->app->make(PolicyFileLoader::class);
    $compiler = $this->app->make(PolicyCompiler::class);

    foreach ($loader->all('en') as $file) {
        $compiled = $compiler->compile($file);

        expect($compiled->title())->not->toBeNull()
            ->and($compiled->html)->not->toContain('---')
            ->and($compiled->html)->not->toBeEmpty();
    }
});
