<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Database\Seeders\ChecklistSeeder;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Gate::define('ai-compliance:audit', fn (GenericUser $user): bool => (bool) ($user->audit ?? false));
    Gate::define('ai-compliance:manage', fn (GenericUser $user): bool => (bool) ($user->manage ?? false));

    app(ChecklistSeeder::class)->run();
});

function dashAuditor(): GenericUser
{
    return new GenericUser(['id' => 1, 'audit' => true, 'manage' => false]);
}

function dashManager(): GenericUser
{
    return new GenericUser(['id' => 2, 'audit' => true, 'manage' => true]);
}

it('serves the dashboard tiles with current-state consent counts', function (): void {
    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_training');
    app(ConsentManager::class)->grant($user, 'ai_chatbot');
    app(ConsentManager::class)->withdraw($user, 'ai_chatbot'); // current state: denied bucket

    Provider::factory()->create();

    $data = $this->actingAs(dashAuditor())->getJson('/ai-compliance/admin/dashboard')
        ->assertOk()
        ->json('data');

    expect($data['consents']['granted'])->toBe(1)
        ->and($data['consents']['denied'])->toBe(1)
        ->and($data['consents']['by_type']['ai_training']['granted'])->toBe(1)
        ->and($data['providers'])->toBe(1)
        ->and($data['activity_events'])->toBeGreaterThanOrEqual(3)
        ->and($data['checklist']['review'])->toBeGreaterThan(30);
});

it('lists the checklist and accepts manual evidence', function (): void {
    $this->actingAs(dashAuditor())->getJson('/ai-compliance/admin/checklist')
        ->assertOk()
        ->assertJsonPath('data.0.status', 'review');

    // manual evidence flips a manual item to ok
    $manualKey = collect($this->actingAs(dashAuditor())->getJson('/ai-compliance/admin/checklist')->json('data'))
        ->firstWhere('evidence_type', 'manual')['key'];

    $this->actingAs(dashManager())
        ->postJson("/ai-compliance/admin/checklist/{$manualKey}/evidence", ['evidence_ref' => 'https://drive.acme.test/dpia.pdf'])
        ->assertOk()
        ->assertJsonPath('data.status', 'ok');

    // auto items refuse manual evidence
    $this->actingAs(dashManager())
        ->postJson('/ai-compliance/admin/checklist/governance.provider_registry/evidence', ['evidence_ref' => 'nope'])
        ->assertConflict();

    // the evidence write landed in the activity log
    expect(ActivityEvent::query()->where('event_type', ActivityType::SettingChange->value)->exists())->toBeTrue();
});

it('runs the checks on demand from the admin api', function (): void {
    Http::fake(['*' => Http::response('User-agent: GPTBot')]);

    $this->actingAs(dashManager())
        ->postJson('/ai-compliance/admin/checklist/run')
        ->assertOk()
        ->assertJsonStructure(['data' => [['key', 'status', 'message']]]);
});

it('stores classification answers and reports them back', function (): void {
    $this->actingAs(dashManager())
        ->putJson('/ai-compliance/admin/classification', ['answers' => ['consequential_decisions' => 'no']])
        ->assertOk()
        ->assertJsonPath('data.consequential_decisions', 'no');

    $this->actingAs(dashAuditor())->getJson('/ai-compliance/admin/classification')
        ->assertOk()
        ->assertJsonPath('data.consequential_decisions', 'no');
});

it('manages the provider registry with activity logging', function (): void {
    $created = $this->actingAs(dashManager())->postJson('/ai-compliance/admin/providers', [
        'name'               => 'Claude assistant',
        'vendor'             => 'Anthropic',
        'model_name'         => 'claude-sonnet-5',
        'role'               => 'deployer',
        'trains_on_our_data' => 'no',
    ])->assertCreated();

    $id = $created->json('data.id');

    $this->actingAs(dashManager())
        ->putJson("/ai-compliance/admin/providers/{$id}", [
            'name'                 => 'Claude assistant',
            'vendor'               => 'Anthropic',
            'model_name'           => 'claude-sonnet-5',
            'role'                 => 'deployer',
            'trains_on_our_data'   => 'no',
            'endpoint_region'      => 'eu-central-1',
            'purpose'              => 'support assistant',
            'dpa_signed_at'        => now()->toDateString(),
            'due_diligence_status' => 'complete',
        ])
        ->assertOk()
        ->assertJsonPath('data.complete', true);

    $this->actingAs(dashManager())->deleteJson("/ai-compliance/admin/providers/{$id}")->assertNoContent();

    expect(Provider::withTrashed()->count())->toBe(1)
        ->and(ActivityEvent::query()->where('event_type', ActivityType::ProviderChange->value)->count())->toBe(3);

    // auditors read, never write
    $this->actingAs(dashAuditor())->getJson('/ai-compliance/admin/providers')->assertOk();
    $this->actingAs(dashAuditor())->postJson('/ai-compliance/admin/providers', [])->assertForbidden();
});

it('toggles feature kill switches and blocks gated routes', function (): void {
    config()->set('laranail.ai-compliance.features', ['chat_assistant' => ['ai_chatbot']]);

    Route::middleware(['web', 'ai.feature:chat_assistant'])
        ->get('/test-feature', static fn (): string => 'feature on');

    $this->getJson('/test-feature')->assertOk();

    $this->actingAs(dashManager())
        ->putJson('/ai-compliance/admin/features/chat_assistant', ['enabled' => false])
        ->assertOk()
        ->assertJsonPath('data.enabled', false);

    $this->getJson('/test-feature')->assertForbidden();

    // the kill switch also denies allows(), regardless of consent
    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_chatbot');
    expect(app(ConsentManager::class)->allows($user, 'chat_assistant'))->toBeFalse();

    $this->actingAs(dashManager())
        ->putJson('/ai-compliance/admin/features/chat_assistant', ['enabled' => true])
        ->assertOk();

    expect(app(ConsentManager::class)->allows($user, 'chat_assistant'))->toBeTrue();

    $features = $this->actingAs(dashAuditor())->getJson('/ai-compliance/admin/features')->json('data');
    expect($features)->toHaveKey('chat_assistant', true);
});
