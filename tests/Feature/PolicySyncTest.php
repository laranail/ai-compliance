<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;

uses(RefreshDatabase::class);

it('imports every shipped document as a published 1.0 on first sync', function (): void {
    $result = $this->app->make(PolicySync::class)->sync();

    expect($result->imported)->toHaveCount(14)
        ->and(PolicyDocument::query()->count())->toBe(14);

    $transparency = PolicyDocument::query()->where('slug', 'transparency')->firstOrFail();
    $published = $transparency->publishedVersion()->firstOrFail();

    expect($published->version)->toBe('1.0')
        ->and($published->status)->toBe(PolicyVersionStatus::Published)
        ->and($published->translations()->where('locale', 'en')->exists())->toBeTrue();
});

it('records document metadata from the file layout', function (): void {
    $this->app->make(PolicySync::class)->sync();

    $consent = PolicyDocument::query()->where('slug', 'consent.ai_training')->firstOrFail();
    $disclosure = PolicyDocument::query()->where('slug', 'disclosure.chat')->firstOrFail();

    expect($consent->consent_type_slug)->toBe('ai_training')
        ->and($consent->type->value)->toBe('consent_text')
        ->and($disclosure->surface)->toBe('chat')
        ->and($disclosure->type->value)->toBe('disclosure')
        ->and($consent->source_path)->toBe('consent/ai_training.md');
});

it('is idempotent: a second sync changes nothing', function (): void {
    $sync = $this->app->make(PolicySync::class);
    $sync->sync();

    $result = $sync->sync();

    expect($result->imported)->toBeEmpty()
        ->and($result->drafted)->toBeEmpty()
        ->and($result->flagged)->toBeEmpty()
        ->and($result->unchanged)->toHaveCount(14)
        ->and(PolicyDocument::query()->count())->toBe(14);
});

it('drafts a new version when a file changes and the database copy was never edited', function (): void {
    $sync = $this->app->make(PolicySync::class);
    $sync->sync();

    overridePolicyDir([
        'en/transparency.md' => "---\ntitle: Changed transparency\ntype: policy\n---\n\nNEW_FILE_CONTENT for {{company}}.",
    ]);

    $result = $sync->sync();

    expect($result->drafted)->toContain('transparency (en)');

    $document = PolicyDocument::query()->where('slug', 'transparency')->firstOrFail();
    $draft = $document->draftVersion()->firstOrFail();
    $published = $document->publishedVersion()->firstOrFail();

    expect($draft->version)->toBe('1.1')
        ->and($draft->translations()->where('locale', 'en')->firstOrFail()->source_markdown)->toContain('NEW_FILE_CONTENT')
        ->and($published->version)->toBe('1.0')
        ->and($published->translations()->where('locale', 'en')->firstOrFail()->source_markdown)->not->toContain('NEW_FILE_CONTENT');
});

it('updates an existing draft in place instead of stacking drafts', function (): void {
    $sync = $this->app->make(PolicySync::class);
    $sync->sync();

    overridePolicyDir(['en/transparency.md' => "---\ntitle: T1\ntype: policy\n---\n\nCHANGE_ONE."]);
    $sync->sync();

    overridePolicyDir(['en/transparency.md' => "---\ntitle: T2\ntype: policy\n---\n\nCHANGE_TWO."]);
    $result = $sync->sync();

    expect($result->drafted)->toContain('transparency (en)');

    $document = PolicyDocument::query()->where('slug', 'transparency')->firstOrFail();

    expect($document->versions()->count())->toBe(2) // published 1.0 + one draft
        ->and($document->draftVersion()->firstOrFail()->translations()->firstOrFail()->source_markdown)->toContain('CHANGE_TWO');
});

it('flags and never overwrites a hand-edited translation when the file changes', function (): void {
    $sync = $this->app->make(PolicySync::class);
    $sync->sync();

    // simulate an in-app edit of the published translation
    $document = PolicyDocument::query()->where('slug', 'transparency')->firstOrFail();
    $translation = $document->publishedVersion()->firstOrFail()->translations()->firstOrFail();
    $translation->update([
        'source_markdown' => 'ADMIN_EDITED CONTENT',
        'checksum' => hash('sha256', 'ADMIN_EDITED CONTENT'),
    ]);

    overridePolicyDir(['en/transparency.md' => "---\ntitle: File change\ntype: policy\n---\n\nFILE_CHANGED_AGAIN."]);

    $result = $sync->sync();

    expect($result->flagged)->toContain('transparency (en)')
        ->and($result->drafted)->not->toContain('transparency (en)');

    expect($translation->fresh()?->source_markdown)->toBe('ADMIN_EDITED CONTENT')
        ->and($document->versions()->count())->toBe(1);
});

it('imports a newly arriving locale into a draft with an origin anchor', function (): void {
    $sync = $this->app->make(PolicySync::class);
    $sync->sync();

    overridePolicyDir([
        'de/transparency.md' => "---\ntitle: Transparenz\ntype: policy\n---\n\nDEUTSCHER INHALT.",
    ]);

    $result = $sync->sync();

    expect($result->imported)->toContain('transparency (de)');

    $document = PolicyDocument::query()->where('slug', 'transparency')->firstOrFail();
    $draft = $document->draftVersion()->firstOrFail();
    $de = $draft->translations()->where('locale', 'de')->firstOrFail();
    $en = $draft->translations()->where('locale', 'en')->firstOrFail();

    // the draft copied the english translation and anchored german to it
    expect($de->origin_checksum)->toBe($en->checksum)
        ->and($document->publishedVersion()->firstOrFail()->translations()->where('locale', 'de')->exists())->toBeFalse();
});
