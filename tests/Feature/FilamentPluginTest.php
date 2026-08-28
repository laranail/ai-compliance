<?php

declare(strict_types=1);

use Livewire\Livewire;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Simtabi\Laranail\AiCompliance\Enums\CheckStatus;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Policy\PolicyFileLoader;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;
use Simtabi\Laranail\AiCompliance\Policy\CompiledPolicyCache;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;
use Simtabi\Laranail\AiCompliance\Filament\Pages\Classification;
use Simtabi\Laranail\AiCompliance\Database\Seeders\ChecklistSeeder;
use Simtabi\Laranail\AiCompliance\Filament\Widgets\ComplianceStats;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ConsentRecordResource;
use Simtabi\Laranail\AiCompliance\Filament\Resources\Providers\Pages\CreateProvider;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ChecklistItems\Pages\ListChecklistItems;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ConsentRecords\Pages\ListConsentRecords;
use Simtabi\Laranail\AiCompliance\Filament\Resources\PolicyDocuments\Pages\EditPolicyDocument;
use Simtabi\Laranail\AiCompliance\Filament\Resources\PolicyDocuments\Pages\ListPolicyDocuments;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // the consent-record model policy delegates to the host gates; filament
    // enforces it like every other surface
    Gate::define('ai-compliance:audit', static fn (): bool => true);
    Gate::define('ai-compliance:manage', static fn (): bool => true);

    $this->actingAs(makeUser());
});

it('registers the plugin on the panel', function (): void {
    expect(Filament::getPanel('admin')->getPlugin('laranail-ai-compliance'))->not->toBeNull();
});

it('round-trips draft markdown byte-identically through the editor', function (): void {
    app(PolicySync::class)->sync();
    $document = PolicyDocument::query()->where('slug', 'transparency')->firstOrFail();

    // deliberately awkward bytes: trailing spaces, a tab, windows newline kept verbatim
    $markdown = "---\ntitle: Edited transparency\ntype: policy\n---\n\nline with trailing spaces   \n\tindented\twith tabs\n";

    Livewire::test(EditPolicyDocument::class, ['record' => $document->getRouteKey()])
        ->fillForm(['source_markdown' => $markdown])
        ->call('save')
        ->assertHasNoFormErrors();

    $draft = $document->draftVersion()->firstOrFail();
    $translation = $draft->translations()->where('locale', 'en')->firstOrFail();

    expect($translation->source_markdown)->toBe($markdown)
        ->and($translation->checksum)->toBe(hash('sha256', $markdown))
        ->and($translation->title)->toBe('Edited transparency');

    // a no-op save changes nothing: same checksum, still one draft
    Livewire::test(EditPolicyDocument::class, ['record' => $document->getRouteKey()])
        ->fillForm(['source_markdown' => $markdown])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($translation->fresh()?->checksum)->toBe(hash('sha256', $markdown))
        ->and($document->versions()->where('status', PolicyVersionStatus::Draft->value)->count())->toBe(1);
});

it('publishes the draft from the editor, superseding and flushing the compiled cache', function (): void {
    app(PolicySync::class)->sync();
    $document = PolicyDocument::query()->where('slug', 'transparency')->firstOrFail();

    Livewire::test(EditPolicyDocument::class, ['record' => $document->getRouteKey()])
        ->fillForm(['source_markdown' => "---\ntitle: V2\ntype: policy\n---\n\nFILAMENT_PUBLISHED body."])
        ->call('save');

    // the cache key embeds a generation; publish must bump it
    $file = app(PolicyFileLoader::class)->find('transparency', 'en');
    $keyBefore = app(CompiledPolicyCache::class)->key($file);

    Livewire::test(EditPolicyDocument::class, ['record' => $document->getRouteKey()])
        ->callAction('publish')
        ->assertHasNoActionErrors();

    expect($document->publishedVersion()->firstOrFail()->version)->toBe('1.1')
        ->and($document->versions()->where('status', PolicyVersionStatus::Superseded->value)->count())->toBe(1)
        ->and(app(PolicyRepository::class)->find('transparency')?->html)->toContain('FILAMENT_PUBLISHED')
        ->and(app(CompiledPolicyCache::class)->key($file))->not->toBe($keyBefore);
});

it('lists policy documents with their version state', function (): void {
    app(PolicySync::class)->sync();

    Livewire::test(ListPolicyDocuments::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(PolicyDocument::query()->limit(5)->get());
});

it('creates providers through the resource', function (): void {
    Livewire::test(CreateProvider::class)
        ->fillForm([
            'name'               => 'Claude assistant',
            'vendor'             => 'Anthropic',
            'model_name'         => 'claude-sonnet-5',
            'role'               => 'deployer',
            'trains_on_our_data' => 'no',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Provider::query()->where('name', 'Claude assistant')->exists())->toBeTrue();
});

it('serves the consent log strictly read-only', function (): void {
    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_training');

    Livewire::test(ListConsentRecords::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(ConsentRecord::query()->get());

    expect(ConsentRecordResource::canCreate())->toBeFalse();
});

it('renders the checklist with status badges', function (): void {
    app(ChecklistSeeder::class)->run();

    Livewire::test(ListChecklistItems::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(ChecklistItem::query()->limit(5)->get());
});

it('records the classification intake and re-derives the checklist', function (): void {
    app(ChecklistSeeder::class)->run();

    Livewire::test(Classification::class)
        ->fillForm(['consequential_decisions' => 'no'])
        ->call('save');

    expect(ChecklistItem::query()->where('section', 'decisions')->get()
        ->every(fn (ChecklistItem $item): bool => $item->status === CheckStatus::NotApplicable))->toBeTrue();
});

it('renders the dashboard stats widget from the shared service', function (): void {
    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_chatbot');
    Provider::factory()->create();

    Livewire::test(ComplianceStats::class)
        ->assertSuccessful()
        ->assertSee('Consents granted')
        ->assertSee('AI providers');
});
