<?php

declare(strict_types=1);

use Livewire\Livewire;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Simtabi\Laranail\Package\Tools\Testing\NamingScope;
use Simtabi\Laranail\Package\Tools\Testing\NameRegistry;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Support\RegisteredNames;
use Simtabi\Laranail\AiCompliance\Livewire\ReconsentPrompt;
use Simtabi\Laranail\AiCompliance\Livewire\ConsentPreferences;
use Simtabi\Laranail\AiCompliance\Filament\Pages\Classification;
use Simtabi\Laranail\Package\Tools\Testing\AssertsRegisteredNames;
use Simtabi\Laranail\AiCompliance\Filament\Widgets\ComplianceStats;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ProviderResource;
use Simtabi\Laranail\AiCompliance\Http\Middleware\DeprecatedConsentAlias;
use Simtabi\Laranail\AiCompliance\Http\Middleware\DeprecatedFeatureAlias;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ChecklistItemResource;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ConsentRecordResource;
use Simtabi\Laranail\AiCompliance\Filament\Resources\PolicyDocumentResource;

uses(AssertsRegisteredNames::class, RefreshDatabase::class);

/**
 * Run $callback and return the E_USER_DEPRECATED messages it raised.
 *
 * @return list<string>
 */
function aiComplianceDeprecations(callable $callback): array
{
    $messages = [];

    set_error_handler(static function (int $level, string $message) use (&$messages): bool {
        $messages[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        $callback();
    } finally {
        restore_error_handler();
    }

    return $messages;
}

function aiComplianceScope(): NamingScope
{
    return NamingScope::for(
        package: 'laranail/ai-compliance',
        ownerNamespace: 'Simtabi\\Laranail\\AiCompliance\\',
        prefixes: [
            NameRegistry::Route->value => [
                'laranail-ai-compliance',
                // D1: `laranail.ai-compliance.*` is a sanctioned vendor-scoped variant
                'laranail.ai-compliance',
                // Filament names a page or resource route `filament.<panel>.<kind>.<slug>`;
                // the scoped slug puts the package segment right after the kind
                'filament.admin.pages.laranail-ai-compliance',
                'filament.admin.resources.laranail-ai-compliance',
            ],
            NameRegistry::Livewire->value => [
                'laranail-ai-compliance',
                // Filament registers each page and widget as a Livewire component named by its
                // fully qualified class, which no other package can claim
                ...aiComplianceFilamentLivewireClasses(),
            ],
        ],
    );
}

/**
 * The Filament pages and widgets the plugin registers, which Livewire knows by class name.
 *
 * @return list<class-string>
 */
function aiComplianceFilamentLivewireClasses(): array
{
    $classes = [Classification::class, ComplianceStats::class];

    foreach ([PolicyDocumentResource::class, ProviderResource::class, ConsentRecordResource::class, ChecklistItemResource::class] as $resource) {
        foreach ($resource::getPages() as $registration) {
            $classes[] = $registration->getPage();
        }
    }

    return $classes;
}

/**
 * Every Filament route name the plugin answered to before the slugs were scoped.
 *
 * @return list<string>
 */
function aiComplianceLegacyFilamentRouteNames(): array
{
    return [
        'filament.admin.pages.classification',
        'filament.admin.resources.policy-documents.index',
        'filament.admin.resources.policy-documents.edit',
        'filament.admin.resources.providers.index',
        'filament.admin.resources.providers.create',
        'filament.admin.resources.providers.edit',
        'filament.admin.resources.consent-records.index',
        'filament.admin.resources.checklist-items.index',
    ];
}

beforeEach(function (): void {
    RegisteredNames::forgetWarnings();
    DeprecatedConsentAlias::resetWarning();
    DeprecatedFeatureAlias::resetWarning();
});

it('scopes the middleware aliases, keeping the bare ones as deprecated aliases', function (): void {
    $scoped = $this->assertMiddlewareAliasesScoped(
        aiComplianceScope(),
        deprecated: [RegisteredNames::LEGACY_CONSENT_MIDDLEWARE, RegisteredNames::LEGACY_FEATURE_MIDDLEWARE],
        atLeast: 2,
    );

    expect($scoped)->toContain('laranail-ai-compliance.consent', 'laranail-ai-compliance.feature');
});

it('scopes the Livewire components, keeping the bare names as deprecated aliases', function (): void {
    $scoped = $this->assertLivewireComponentsScoped(
        aiComplianceScope(),
        deprecated: array_keys(RegisteredNames::legacyLivewireMap()),
        atLeast: 2,
    );

    expect($scoped)->toContain(
        'laranail-ai-compliance.consent-preferences',
        'laranail-ai-compliance.reconsent-prompt',
    );
});

it('registers views and translations under both scoped forms', function (): void {
    expect($this->assertViewNamespacesScoped(aiComplianceScope(), atLeast: 2))
        ->toContain('laranail/ai-compliance', 'laranail-ai-compliance')
        ->and($this->assertTranslationNamespacesScoped(aiComplianceScope(), atLeast: 2))
        ->toContain('laranail/ai-compliance', 'laranail-ai-compliance');
});

it('scopes the route names, the commands and the Blade components', function (): void {
    $this->assertRouteNamesScoped(aiComplianceScope(), deprecated: aiComplianceLegacyFilamentRouteNames(), atLeast: 18);
    $this->assertCommandNamesScoped(aiComplianceScope(), atLeast: 11);
    $this->assertBladeComponentsScoped(aiComplianceScope(), atLeast: 1);
});

it('resolves a host override published by vendor:publish through both namespace forms', function (): void {
    // vendor:publish --tag=laranail::ai-compliance-views writes here
    $override = resource_path('views/vendor/laranail-ai-compliance/livewire');
    $created = ! is_dir($override);
    @mkdir($override, 0o777, true);
    file_put_contents($override . '/reconsent-prompt.blade.php', '<div>HOST_OVERRIDE</div>');

    try {
        // the override directory is only picked up when it exists at boot, as in a real host
        $this->refreshApplication();
        $this->actingAs(makeUser());

        expect(view('laranail-ai-compliance::livewire.reconsent-prompt', ['reconsent' => []])->render())->toBe('<div>HOST_OVERRIDE</div>')
            ->and(view('laranail/ai-compliance::livewire.reconsent-prompt', ['reconsent' => []])->render())->toBe('<div>HOST_OVERRIDE</div>');

        // the package's own call, which uses the slash form, still renders the host's file
        Livewire::test(ReconsentPrompt::class)->assertSee('HOST_OVERRIDE');
    } finally {
        unlink($override . '/reconsent-prompt.blade.php');

        if ($created) {
            rmdir($override);
            @rmdir(dirname($override));
        }
    }
});

it('enforces the same check under the deprecated middleware aliases and warns once each', function (string $legacy, string $parameter): void {
    Log::spy();

    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_chatbot');

    Route::middleware(['web', $legacy . ':' . $parameter])->get('/legacy-alias', static fn (): string => 'ok');

    $this->actingAs($user)->get('/legacy-alias')->assertOk();
    $this->actingAs($user)->get('/legacy-alias')->assertOk();

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(static fn (string $message): bool => str_contains($message, '"' . $legacy . '"')
            && str_contains($message, 'laranail-ai-compliance.'));
})->with([
    'consent' => [RegisteredNames::LEGACY_CONSENT_MIDDLEWARE, 'ai_chatbot'],
    'feature' => [RegisteredNames::LEGACY_FEATURE_MIDDLEWARE, 'chat_assistant'],
]);

it('blocks under the deprecated consent alias exactly as under the scoped one', function (): void {
    Route::middleware(['web', 'ai.consent:ai_chatbot'])->get('/legacy-blocked', static fn (): string => 'ok');
    Route::middleware(['web', 'laranail-ai-compliance.consent:ai_chatbot'])->get('/scoped-blocked', static fn (): string => 'ok');

    $this->actingAs(makeUser())->get('/legacy-blocked')->assertForbidden();
    $this->actingAs(makeUser())->get('/scoped-blocked')->assertForbidden();
});

it('maps each component class back to its scoped Livewire name', function (): void {
    expect(app('livewire')->new(ConsentPreferences::class)->getName())->toBe('laranail-ai-compliance.consent-preferences')
        ->and(app('livewire')->new(ReconsentPrompt::class)->getName())->toBe('laranail-ai-compliance.reconsent-prompt');
});

it('mounts under a deprecated Livewire name with one notice per name, and none for the scoped name', function (): void {
    $this->actingAs(makeUser());

    $scoped = aiComplianceDeprecations(static fn () => Livewire::test('laranail-ai-compliance.reconsent-prompt'));

    $legacy = aiComplianceDeprecations(static function (): void {
        Livewire::test('ai-compliance.reconsent-prompt')->assertOk();
        Livewire::test('ai-compliance.reconsent-prompt')->assertOk();
    });

    expect($scoped)->toBe([])
        ->and($legacy)->toHaveCount(1)
        ->and($legacy[0])->toContain('[ai-compliance.reconsent-prompt]', '[laranail-ai-compliance.reconsent-prompt]');
});

it('serves every Filament page and resource under the scoped slug', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(Classification::getSlug())->toBe('laranail-ai-compliance/classification')
        ->and(ProviderResource::getSlug())->toBe('laranail-ai-compliance/providers')
        ->and(PolicyDocumentResource::getSlug())->toBe('laranail-ai-compliance/policy-documents')
        ->and(ConsentRecordResource::getSlug())->toBe('laranail-ai-compliance/consent-records')
        ->and(ChecklistItemResource::getSlug())->toBe('laranail-ai-compliance/checklist-items')
        ->and(ProviderResource::getUrl('index'))->toEndWith('/admin/laranail-ai-compliance/providers')
        ->and(Classification::getUrl())->toEndWith('/admin/laranail-ai-compliance/classification');
});

it('redirects every deprecated Filament URL to its scoped URL, keeping the old route names', function (string $legacyRoute, array $parameters, string $target): void {
    Gate::define('ai-compliance:audit', static fn (): bool => true);
    Gate::define('ai-compliance:manage', static fn (): bool => true);

    $provider = Provider::factory()->create();
    $parameters = array_map(static fn (mixed $value): mixed => $value === '@provider' ? $provider->getKey() : $value, $parameters);
    $target = str_replace('@provider', (string) $provider->getKey(), $target);

    $legacyUrl = route($legacyRoute, $parameters);

    $notices = aiComplianceDeprecations(fn () => $this->actingAs(makeUser())
        ->get($legacyUrl . '?tab=a')
        ->assertRedirect(url($target) . '?tab=a'));

    expect($notices)->toHaveCount(1)
        ->and($notices[0])->toContain('Filament slug', 'laranail-ai-compliance/');

    $this->actingAs(makeUser())->get(url($target))->assertOk();
})->with([
    'page'             => ['filament.admin.pages.classification', [], '/admin/laranail-ai-compliance/classification'],
    'resource index'   => ['filament.admin.resources.providers.index', [], '/admin/laranail-ai-compliance/providers'],
    'resource create'  => ['filament.admin.resources.providers.create', [], '/admin/laranail-ai-compliance/providers/create'],
    'resource edit'    => ['filament.admin.resources.providers.edit', ['record' => '@provider'], '/admin/laranail-ai-compliance/providers/@provider/edit'],
    'policy documents' => ['filament.admin.resources.policy-documents.index', [], '/admin/laranail-ai-compliance/policy-documents'],
    'consent records'  => ['filament.admin.resources.consent-records.index', [], '/admin/laranail-ai-compliance/consent-records'],
    'checklist items'  => ['filament.admin.resources.checklist-items.index', [], '/admin/laranail-ai-compliance/checklist-items'],
]);

it('keeps the derived legacy URLs unchanged so existing bookmarks still land', function (): void {
    expect(route('filament.admin.resources.providers.index', [], false))->toBe('/admin/providers')
        ->and(route('filament.admin.pages.classification', [], false))->toBe('/admin/classification');
});
