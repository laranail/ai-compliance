<?php

declare(strict_types=1);

use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Livewire\ReconsentPrompt;
use Simtabi\Laranail\AiCompliance\Livewire\ConsentPreferences;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyPublisher;

uses(RefreshDatabase::class);

it('renders the preferences panel with the current state', function (): void {
    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_chatbot');
    $this->actingAs($user);

    Livewire::test(ConsentPreferences::class)
        ->assertSee('AI privacy choices')
        ->assertSee('data-consent-type="ai_chatbot"', false)
        ->assertSee('Withdraw');
});

it('toggling writes an append-only record and re-renders the fresh state', function (): void {
    $user = makeUser();
    $this->actingAs($user);

    Livewire::test(ConsentPreferences::class)
        ->call('toggle', 'ai_training', 'granted')
        ->assertDispatched('ai-compliance:consent-changed');

    expect(ConsentRecord::query()->count())->toBe(1)
        ->and(app(ConsentManager::class)->granted($user, 'ai_training'))->toBeTrue()
        ->and(ConsentRecord::query()->firstOrFail()->source)->toBe('livewire');

    // toggling back appends a second row, never mutates the first
    Livewire::test(ConsentPreferences::class)->call('toggle', 'ai_training', 'withdrawn');

    expect(ConsentRecord::query()->count())->toBe(2)
        ->and(app(ConsentManager::class)->granted($user, 'ai_training'))->toBeFalse();
});

it('ignores invalid statuses', function (): void {
    $user = makeUser();
    $this->actingAs($user);

    Livewire::test(ConsentPreferences::class)->call('toggle', 'ai_training', 'not-a-status');

    expect(ConsentRecord::query()->count())->toBe(0);
});

it('shows the reconsent prompt only when a granted version was superseded', function (): void {
    app(PolicySync::class)->sync();
    $user = makeUser();
    $this->actingAs($user);

    app(ConsentManager::class)->grant($user, 'ai_training');

    Livewire::test(ReconsentPrompt::class)->assertDontSee('data-consent-type', false);

    // supersede the granted version
    $document = PolicyDocument::query()->where('slug', 'consent.ai_training')->firstOrFail();
    $draft = $document->versions()->create(['version' => '2.0', 'status' => 'draft']);
    $draft->translations()->create([
        'locale'          => 'en',
        'title'           => 'AI training permissions',
        'source_markdown' => 'new terms',
        'compiled_html'   => '<p>new terms</p>',
        'checksum'        => hash('sha256', 'new terms'),
    ]);
    app(PolicyPublisher::class)->publish($draft);

    Livewire::test(ReconsentPrompt::class)
        ->assertSee('data-consent-type="ai_training"', false)
        ->call('regrant', 'ai_training')
        ->assertDispatched('ai-compliance:consent-changed');

    expect(app(ConsentManager::class)->reconsentFor($user))->toBe([])
        ->and(app(ConsentManager::class)->currentRecord($user, 'ai_training')?->policy_version)->toBe('2.0');
});

it('registers the components under their aliases', function (): void {
    // resolving by alias proves registration ran (it is guarded on livewire
    // being installed; without livewire the same guard skips it silently)
    expect(app('livewire')->new('ai-compliance.consent-preferences'))->toBeInstanceOf(ConsentPreferences::class)
        ->and(app('livewire')->new('ai-compliance.reconsent-prompt'))->toBeInstanceOf(ReconsentPrompt::class);
});
