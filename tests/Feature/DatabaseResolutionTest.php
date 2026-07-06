<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyPublisher;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;

uses(RefreshDatabase::class);

it('serves the published database version with its version string after a sync', function (): void {
    $this->app->make(PolicySync::class)->sync();

    $content = $this->app->make(PolicyRepository::class)->find('transparency');

    expect($content?->version)->toBe('1.0')
        ->and($content?->html)->toContain('Acme')
        ->and($content?->locale)->toBe('en');
});

it('serves published edits and ignores the file once a version is published', function (): void {
    $this->app->make(PolicySync::class)->sync();

    $document = PolicyDocument::query()->where('slug', 'transparency')->firstOrFail();
    $draft = $document->versions()->create(['version' => '1.1', 'status' => 'draft']);
    $draft->translations()->create([
        'locale' => 'en',
        'title' => 'Edited transparency',
        'source_markdown' => 'DB_EDITED for {{company}}.',
        'compiled_html' => '<p>DB_EDITED for {{company}}.</p>',
        'meta' => [],
        'checksum' => hash('sha256', 'DB_EDITED for {{company}}.'),
    ]);

    $this->app->make(PolicyPublisher::class)->publish($draft);

    $content = $this->app->make(PolicyRepository::class)->find('transparency');

    expect($content?->version)->toBe('1.1')
        ->and($content?->html)->toContain('DB_EDITED for Acme')
        ->and($content?->title)->toBe('Edited transparency');

    // the http surface serves the same thing
    $this->getJson('/ai-compliance/policies/transparency')
        ->assertOk()
        ->assertJsonPath('version', '1.1');
});

it('resolves database translations through the fallback chain and reports the served locale', function (): void {
    $this->app->make(PolicySync::class)->sync();

    $content = $this->app->make(PolicyRepository::class)->find('transparency', 'de');

    expect($content?->version)->toBe('1.0')
        ->and($content?->locale)->toBe('en')
        ->and($content?->requestedLocale)->toBe('de')
        ->and($content?->isFallback())->toBeTrue();
});

it('serves nothing at all for a deactivated document, even when a file exists', function (): void {
    $this->app->make(PolicySync::class)->sync();

    PolicyDocument::query()->where('slug', 'transparency')->update(['active' => false]);

    expect($this->app->make(PolicyRepository::class)->find('transparency'))->toBeNull();

    $this->getJson('/ai-compliance/policies/transparency')->assertNotFound();

    // and the boot payload document index skips it
    expect($this->getJson('/ai-compliance/boot')->json('documents'))->not->toHaveKey('transparency');
});

it('keeps serving files for documents whose only version is a draft', function (): void {
    $document = PolicyDocument::factory()->create(['slug' => 'transparency']);
    $document->versions()->create(['version' => '1.0', 'status' => 'draft']);

    $content = $this->app->make(PolicyRepository::class)->find('transparency');

    expect($content?->version)->toBeNull() // file-served
        ->and($content?->html)->toContain('Acme');
});

it('lists database-published and file-only documents together in the boot payload', function (): void {
    $this->app->make(PolicySync::class)->sync();

    $documents = $this->getJson('/ai-compliance/boot')->json('documents');

    expect($documents)->toHaveKey('transparency')
        ->and($documents['transparency']['version'])->toBe('1.0');
});
