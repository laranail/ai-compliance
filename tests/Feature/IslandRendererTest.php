<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Policy\PolicyCompiler;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\View\IslandRenderer;

uses(RefreshDatabase::class);

function renderIslands(string $markdown): string
{
    $compiled = app(PolicyCompiler::class)->compile(policyFile($markdown));

    return app(IslandRenderer::class)->render($compiled->html);
}

it('replaces the consent-toggle island with a working form', function (): void {
    $html = renderIslands('Choose: [[consent-toggle type="ai_training" fallback="Manage in settings."]]');

    expect($html)->not->toContain('<ai-c')
        ->and($html)->toContain('ai-compliance-consent-toggle')
        ->and($html)->toContain('data-consent-type="ai_training"')
        ->and($html)->toContain('/ai-compliance/consents');
});

it('replaces the consent-panel island with the preferences component', function (): void {
    $html = renderIslands('[[consent-panel fallback="See settings."]]');

    expect($html)->toContain('ai-compliance-preferences')
        ->and($html)->toContain('data-consent-type="ai_training"');
});

it('replaces the policy-link island with a titled link', function (): void {
    $html = renderIslands('Read [[policy-link slug="transparency" fallback="our transparency page"]].');

    expect($html)->toContain('ai-compliance-policy-link')
        ->and($html)->toContain('How Acme App uses AI')
        ->and($html)->toContain('/ai-compliance/policies/transparency');
});

it('replaces the disclosure island with the disclosure component', function (): void {
    $html = renderIslands('[[disclosure surface="content"]]');

    expect($html)->toContain('ai-compliance-disclosure')
        ->and($html)->toContain('data-surface="content"');
});

it('keeps the fallback text for islands without a server view', function (): void {
    $html = renderIslands('[[provider-list fallback="Contact us for the provider list."]]');

    expect($html)->not->toContain('<ai-c')
        ->and($html)->toContain('Contact us for the provider list.');
});

it('renders the shipped transparency document with every island resolved', function (): void {
    $document = app(PolicyRepository::class)->find('transparency');

    $html = app(IslandRenderer::class)->render($document->html);

    expect($html)->not->toContain('<ai-c');
});
