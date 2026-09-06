<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Simtabi\Laranail\AiCompliance\Facades\AiConsent;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Events\InferenceLogged;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::fake(['api.openai.test/*' => Http::response(['ok' => true])]);

    config()->set('laranail.ai-compliance.providers.do_not_train', [
        'openai' => ['header' => 'X-Do-Not-Train', 'body' => 'store', 'body_value' => false],
    ]);

    Provider::factory()->dueDiligenceComplete()->create([
        'name'       => 'support-assistant',
        'vendor'     => 'OpenAI',
        'model_name' => 'model-1',
    ]);
});

it('carries the do-not-train flag when training consent is denied (spec acceptance 1)', function (): void {
    $user = makeUser();
    app(ConsentManager::class)->deny($user, 'ai_training');

    AiConsent::provider('support-assistant')
        ->forSubject($user)
        ->send('POST', 'https://api.openai.test/v1/responses', ['input' => 'redacted']);

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('X-Do-Not-Train', 'true')
        && $request->data()['store'] === false);
});

it('omits the flag once training consent is granted, and re-applies it after withdrawal', function (): void {
    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_training');

    AiConsent::provider('support-assistant')->forSubject($user)
        ->send('POST', 'https://api.openai.test/v1/responses');

    Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('X-Do-Not-Train'));

    app(ConsentManager::class)->withdraw($user, 'ai_training');

    AiConsent::provider('support-assistant')->forSubject($user)
        ->send('POST', 'https://api.openai.test/v1/responses');

    $flagged = collect(Http::recorded())
        ->filter(fn (array $pair): bool => $pair[0]->hasHeader('X-Do-Not-Train'));

    expect($flagged)->toHaveCount(1); // only the post-withdrawal call
});

it('treats subjectless calls as do-not-train', function (): void {
    expect(AiConsent::provider('support-assistant')->doNotTrain())->toBeTrue();
});

it('logs the inference with the provider id and fires InferenceLogged', function (): void {
    Event::fake([InferenceLogged::class]);

    $user = makeUser();

    AiConsent::provider('support-assistant')
        ->forSubject($user)
        ->send('POST', 'https://api.openai.test/v1/responses', [], purpose: 'support_chat');

    $event = ActivityEvent::query()->where('event_type', ActivityType::Inference->value)->firstOrFail();

    expect($event->provider_id)->toBe(Provider::query()->firstOrFail()->id)
        ->and($event->subjectable_type)->toBe('user')
        ->and($event->context)->toMatchArray([
            'provider'     => 'support-assistant',
            'vendor'       => 'OpenAI',
            'model'        => 'model-1',
            'purpose'      => 'support_chat',
            'do_not_train' => true,
        ])
        ->and($event->context)->not->toHaveKey('input'); // no raw prompts, ever

    Event::assertDispatched(InferenceLogged::class);
});

it('resolves providers by vendor and refuses unregistered ones', function (): void {
    expect(AiConsent::provider('OpenAI')->doNotTrain())->toBeTrue();

    AiConsent::provider('not-registered');
})->throws(NotFoundHttpException::class);

it('supports the record-only path for host sdks', function (): void {
    $guestKey = 'g_' . str_repeat('e', 32);

    $event = AiConsent::provider('support-assistant')
        ->forSubject($guestKey)
        ->record('summaries', ['tokens' => 120]);

    expect($event->event_type)->toBe(ActivityType::Inference)
        ->and($event->context['guest_key'] ?? null)->toBe($guestKey)
        ->and($event->context['tokens'] ?? null)->toBe(120);

    Http::assertNothingSent();
});
