<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyStaleness;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;

uses(RefreshDatabase::class);

function stalenessEntries(string $slug): array
{
    return array_values(array_filter(
        app(PolicyStaleness::class)->report(),
        static fn (array $entry): bool => $entry['slug'] === $slug,
    ));
}

it('reports nothing right after a clean sync', function (): void {
    $this->app->make(PolicySync::class)->sync();

    expect($this->app->make(PolicyStaleness::class)->report())->toBeEmpty();
});

it('reports file drift when a shipped file changes after import', function (): void {
    $this->app->make(PolicySync::class)->sync();

    overridePolicyDir(['en/vendor.md' => "---\ntitle: Vendor policy v2\ntype: policy\n---\n\nCHANGED VENDOR TEXT."]);

    $entries = stalenessEntries('vendor');

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['signal'])->toBe('file_drift')
        ->and($entries[0]['hand_edited'])->toBeFalse();
});

it('marks file drift as hand-edited when the database copy was changed too', function (): void {
    $this->app->make(PolicySync::class)->sync();

    $document = PolicyDocument::query()->where('slug', 'vendor')->firstOrFail();
    $translation = $document->publishedVersion()->firstOrFail()->translations()->firstOrFail();
    $translation->update([
        'source_markdown' => 'ADMIN CHANGED THIS',
        'checksum' => hash('sha256', 'ADMIN CHANGED THIS'),
    ]);

    overridePolicyDir(['en/vendor.md' => "---\ntitle: Vendor policy v2\ntype: policy\n---\n\nFILE CHANGED TOO."]);

    $entries = stalenessEntries('vendor');

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['signal'])->toBe('file_drift')
        ->and($entries[0]['hand_edited'])->toBeTrue();
});

it('reports translation drift when the default-locale source changes after a translation was made', function (): void {
    // import a german translation alongside english
    overridePolicyDir([
        'de/transparency.md' => "---\ntitle: Transparenz\ntype: policy\n---\n\nDEUTSCHER INHALT.",
    ]);
    $this->app->make(PolicySync::class)->sync();

    // the admin rewrites the english source on the same (draft) version
    $document = PolicyDocument::query()->where('slug', 'transparency')->firstOrFail();
    $draft = $document->draftVersion()->firstOrFail();
    $draft->translations()->where('locale', 'en')->firstOrFail()->update([
        'source_markdown' => 'REWRITTEN ENGLISH SOURCE',
        'checksum' => hash('sha256', 'REWRITTEN ENGLISH SOURCE'),
    ]);

    $entries = array_values(array_filter(
        stalenessEntries('transparency'),
        static fn (array $entry): bool => $entry['signal'] === 'translation_drift',
    ));

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['locale'])->toBe('de');
});
