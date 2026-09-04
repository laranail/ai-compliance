<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Gate::define('ai-compliance:audit', fn (GenericUser $user): bool => (bool) ($user->audit ?? false));
    Gate::define('ai-compliance:manage', fn (GenericUser $user): bool => (bool) ($user->manage ?? false));

    $this->app->make(PolicySync::class)->sync();
});

function auditor(): GenericUser
{
    return new GenericUser(['id' => 1, 'audit' => true, 'manage' => false]);
}

function manager(): GenericUser
{
    return new GenericUser(['id' => 2, 'audit' => true, 'manage' => true]);
}

it('denies guests everything', function (): void {
    $this->getJson('/ai-compliance/admin/policies')->assertForbidden();
    $this->postJson('/ai-compliance/admin/policies/transparency/draft')->assertForbidden();
});

it('lets auditors read but not write', function (): void {
    $this->actingAs(auditor())
        ->getJson('/ai-compliance/admin/policies')
        ->assertOk()
        ->assertJsonCount(14, 'data');

    $this->actingAs(auditor())
        ->postJson('/ai-compliance/admin/policies/transparency/draft')
        ->assertForbidden();

    $this->actingAs(auditor())
        ->postJson('/ai-compliance/admin/policies/preview', ['source_markdown' => 'x'])
        ->assertForbidden();
});

it('shows a document with its full version history', function (): void {
    $response = $this->actingAs(auditor())
        ->getJson('/ai-compliance/admin/policies/transparency')
        ->assertOk();

    expect($response->json('data.slug'))->toBe('transparency')
        ->and($response->json('data.versions.0.version'))->toBe('1.0')
        ->and($response->json('data.versions.0.status'))->toBe('published')
        ->and($response->json('data.versions.0.translations.0.locale'))->toBe('en');
});

it('walks the full editing flow: draft, edit, preview, publish', function (): void {
    // create a draft
    $this->actingAs(manager())
        ->postJson('/ai-compliance/admin/policies/transparency/draft')
        ->assertCreated()
        ->assertJsonPath('data.version', '1.1')
        ->assertJsonPath('data.status', 'draft');

    // creating again returns the same open draft
    $this->actingAs(manager())
        ->postJson('/ai-compliance/admin/policies/transparency/draft')
        ->assertOk()
        ->assertJsonPath('data.version', '1.1');

    // preview without saving
    $preview = $this->actingAs(manager())
        ->postJson('/ai-compliance/admin/policies/preview', [
            'source_markdown' => "---\ntitle: Preview\n---\n\nHello {{company}} and {{unknown_thing}}.",
        ])
        ->assertOk();

    expect($preview->json('data.html'))->toContain('Hello Acme')
        ->and($preview->json('data.unresolved_placeholders'))->toContain('unknown_thing');

    // edit the draft's english translation
    $update = $this->actingAs(manager())
        ->putJson('/ai-compliance/admin/policies/transparency/draft/translations/en', [
            'source_markdown' => "---\ntitle: Reworded transparency\ntype: policy\n---\n\nREWORDED for {{company}}. [[consent-panel fallback=\"Settings.\"]]",
        ])
        ->assertOk();

    expect($update->json('data.hand_edited'))->toBeTrue()
        ->and($update->json('data.compiled_html'))->toContain('<ai-c data-component="consent-panel"');

    // publish the draft
    $this->actingAs(manager())
        ->postJson('/ai-compliance/admin/policies/transparency/draft/publish')
        ->assertOk()
        ->assertJsonPath('data.status', 'published')
        ->assertJsonPath('data.version', '1.1');

    // the consumer surface serves the new version immediately
    $this->getJson('/ai-compliance/policies/transparency')
        ->assertOk()
        ->assertJsonPath('version', '1.1')
        ->assertJsonPath('title', 'Reworded transparency');

    // and the document has exactly one published version
    $document = PolicyDocument::query()->where('slug', 'transparency')->firstOrFail();
    expect($document->versions()->where('status', 'published')->count())->toBe(1)
        ->and($document->versions()->where('status', 'superseded')->count())->toBe(1);
});

it('conflicts when publishing or editing with no open draft', function (): void {
    $this->actingAs(manager())
        ->postJson('/ai-compliance/admin/policies/transparency/draft/publish')
        ->assertConflict();

    $this->actingAs(manager())
        ->putJson('/ai-compliance/admin/policies/transparency/draft/translations/en', [
            'source_markdown' => 'x',
        ])
        ->assertConflict();
});

it('404s unknown documents for readers and writers', function (): void {
    $this->actingAs(auditor())->getJson('/ai-compliance/admin/policies/nope')->assertNotFound();
    $this->actingAs(manager())->postJson('/ai-compliance/admin/policies/nope/draft')->assertNotFound();
});
