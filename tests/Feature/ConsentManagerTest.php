<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;
use Simtabi\Laranail\AiCompliance\Exceptions\UnknownConsentType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Models\ConsentType;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyPublisher;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;

uses(RefreshDatabase::class);

function consent(): ConsentManager
{
    return app(ConsentManager::class);
}

it('stamps new consents with the published policy version of the consent document', function (): void {
    app(PolicySync::class)->sync();
    $user = makeUser();

    $record = consent()->grant($user, 'ai_training', 'settings_page');

    expect($record->policy_version)->toBe('1.0')
        ->and($record->policy_version_id)->not->toBeNull()
        ->and($record->source)->toBe('settings_page')
        ->and($record->status)->toBe(ConsentStatus::Granted);
});

it('records null version when the consent text has never been published', function (): void {
    $user = makeUser();

    $record = consent()->grant($user, 'ai_training');

    expect($record->policy_version)->toBeNull()
        ->and($record->policy_version_id)->toBeNull();
});

it('creates consent type rows lazily from config and rejects unknown types', function (): void {
    $user = makeUser();

    consent()->grant($user, 'ai_chatbot');

    expect(ConsentType::query()->where('slug', 'ai_chatbot')->exists())->toBeTrue();

    consent()->grant($user, 'not_a_type');
})->throws(UnknownConsentType::class);

it('treats the latest row as the current state and keeps full history', function (): void {
    $user = makeUser();

    consent()->grant($user, 'ai_training');
    consent()->deny($user, 'ai_training');

    expect(consent()->granted($user, 'ai_training'))->toBeFalse()
        ->and(consent()->stateFor($user)['ai_training']['status'])->toBe('denied')
        ->and(ConsentRecord::query()->count())->toBe(2);
});

it('answers withdraw-then-query correctly', function (): void {
    $user = makeUser();

    consent()->grant($user, 'ai_personalization');
    expect(consent()->granted($user, 'ai_personalization'))->toBeTrue();

    consent()->withdraw($user, 'ai_personalization', 'settings_page');

    expect(consent()->granted($user, 'ai_personalization'))->toBeFalse()
        ->and(consent()->stateFor($user)['ai_personalization']['status'])->toBe('withdrawn');
});

it('falls back to the configured default state when no record exists', function (): void {
    $user = makeUser();

    $state = consent()->stateFor($user);

    expect($state['ai_training'])->toBe([
        'status' => 'denied',
        'recorded_at' => null,
        'policy_version' => null,
    ]);
});

it('gates features on their required consents, denying unknown features', function (): void {
    config()->set('laranail.ai-compliance.features', [
        'smart_summaries' => ['ai_training', 'ai_personalization'],
    ]);

    $user = makeUser();

    expect(consent()->allows($user, 'smart_summaries'))->toBeFalse();

    consent()->grant($user, 'ai_training');
    expect(consent()->allows($user, 'smart_summaries'))->toBeFalse();

    consent()->grant($user, 'ai_personalization');
    expect(consent()->allows($user, 'smart_summaries'))->toBeTrue()
        ->and(consent()->allows($user, 'unconfigured_feature'))->toBeFalse();
});

it('flags re-consent when the granted consent document version is superseded', function (): void {
    app(PolicySync::class)->sync();
    $user = makeUser();

    consent()->grant($user, 'ai_training');
    expect(consent()->reconsentFor($user))->toBe([]);

    // publish a new version of the consent document
    $document = PolicyDocument::query()->where('slug', 'consent.ai_training')->firstOrFail();
    $draft = $document->versions()->create(['version' => '2.0', 'status' => 'draft']);
    $draft->translations()->create([
        'locale' => 'en',
        'title' => 'AI training permissions',
        'source_markdown' => 'materially different terms',
        'compiled_html' => '<p>materially different terms</p>',
        'checksum' => hash('sha256', 'materially different terms'),
    ]);
    app(PolicyPublisher::class)->publish($draft);

    expect(consent()->reconsentFor($user))->toBe(['ai_training']);

    // re-granting under the new version clears the flag
    consent()->grant($user, 'ai_training');
    expect(consent()->reconsentFor($user))->toBe([])
        ->and(consent()->currentRecord($user, 'ai_training')?->policy_version)->toBe('2.0');
});

it('merges guest state into a user idempotently, preserving the version the guest saw', function (): void {
    app(PolicySync::class)->sync();
    $guestKey = 'g_'.str_repeat('a', 32);

    consent()->grant($guestKey, 'ai_chatbot');
    consent()->deny($guestKey, 'ai_training');

    $user = makeUser();
    consent()->mergeGuest($guestKey, $user);

    expect(consent()->granted($user, 'ai_chatbot'))->toBeTrue()
        ->and(consent()->stateFor($user)['ai_training']['status'])->toBe('denied');

    $merged = consent()->currentRecord($user, 'ai_chatbot');
    expect($merged?->source)->toBe('guest_merge')
        ->and($merged?->policy_version)->toBe('1.0');

    $countAfterFirstMerge = ConsentRecord::query()->count();
    consent()->mergeGuest($guestKey, $user);

    expect(ConsentRecord::query()->count())->toBe($countAfterFirstMerge);

    // guest history remains untouched
    expect(ConsentRecord::query()->where('guest_key', $guestKey)->count())->toBe(2);
});

it('mirrors every consent change into the activity log', function (): void {
    $user = makeUser();

    consent()->grant($user, 'ai_training');
    consent()->withdraw($user, 'ai_training');

    $events = ActivityEvent::query()->where('event_type', ActivityType::ConsentChange->value)->get();

    expect($events)->toHaveCount(2)
        ->and($events->first()?->subjectable_type)->toBe('user')
        ->and($events->first()?->context)->toHaveKey('consent_type', 'ai_training');
});

it('exports everything held about a subject using public ids only', function (): void {
    $user = makeUser();

    consent()->grant($user, 'ai_training');
    consent()->withdraw($user, 'ai_training');

    $export = consent()->exportSubject($user);

    expect($export['consents'])->toHaveCount(2)
        ->and($export['consents'][0]['type'])->toBe('ai_training')
        ->and($export['consents'][0]['id'])->toHaveLength(26)
        ->and($export['consents'][0])->not->toHaveKeys(['subjectable_id', 'consent_type_id'])
        ->and($export['activity'])->toHaveCount(2);
});

it('forgets a subject: anonymizes rows, keeps history, logs the dsr action', function (): void {
    $user = makeUser();

    consent()->grant($user, 'ai_training');
    consent()->grant($user, 'ai_chatbot');

    consent()->forgetSubject($user);

    expect(ConsentRecord::query()->where('subjectable_id', $user->id)->count())->toBe(0)
        ->and(ConsentRecord::query()->count())->toBe(2)
        ->and(consent()->stateFor($user)['ai_training']['status'])->toBe('denied')
        ->and(ActivityEvent::query()->where('event_type', ActivityType::DsrAction->value)->count())->toBe(1);

    // the anonymized activity rows no longer point at the user either
    expect(ActivityEvent::query()->where('subjectable_id', $user->id)->count())->toBe(0);
});

it('forgets guest subjects too', function (): void {
    $guestKey = 'g_'.str_repeat('b', 32);
    consent()->grant($guestKey, 'ai_training');

    consent()->forgetSubject($guestKey);

    expect(ConsentRecord::query()->where('guest_key', $guestKey)->count())->toBe(0)
        ->and(ConsentRecord::query()->count())->toBe(1);
});
