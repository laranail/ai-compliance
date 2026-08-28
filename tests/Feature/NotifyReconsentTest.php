<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;
use Simtabi\Laranail\AiCompliance\Notifications\ReconsentRequested;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyPublisher;

uses(RefreshDatabase::class);

function supersedeConsentDocument(string $slug): void
{
    $document = PolicyDocument::query()->where('slug', $slug)->firstOrFail();
    $draft = $document->versions()->create(['version' => '2.0', 'status' => 'draft']);
    $draft->translations()->create([
        'locale'          => 'en',
        'title'           => 'New terms',
        'source_markdown' => 'materially different terms',
        'compiled_html'   => '<p>materially different terms</p>',
        'checksum'        => hash('sha256', 'materially different terms'),
    ]);
    app(PolicyPublisher::class)->publish($draft);
}

it('notifies exactly the users whose granted consent was superseded', function (): void {
    Notification::fake();
    app(PolicySync::class)->sync();

    $consent = app(ConsentManager::class);

    $affected = makeUser('Affected');
    $consent->grant($affected, 'ai_training');

    $unaffected = makeUser('Unaffected');
    $consent->grant($unaffected, 'ai_chatbot'); // different document

    $denier = makeUser('Denier');
    $consent->deny($denier, 'ai_training'); // never granted

    $guestKey = 'g_' . str_repeat('z', 32);
    $consent->grant($guestKey, 'ai_training'); // guests get the boot prompt, not mail

    supersedeConsentDocument('consent.ai_training');

    $this->artisan('laranail::ai-compliance.notify-reconsent')
        ->expectsOutputToContain('1 notifications queued')
        ->assertSuccessful();

    Notification::assertSentTo($affected, ReconsentRequested::class, fn (ReconsentRequested $notification): bool => $notification->toArray() === ['consent_types' => ['ai_training']]);
    Notification::assertNotSentTo($unaffected, ReconsentRequested::class);
    Notification::assertNotSentTo($denier, ReconsentRequested::class);
    Notification::assertCount(1);
});

it('sends nothing on a dry run and nothing after re-consent', function (): void {
    Notification::fake();
    app(PolicySync::class)->sync();

    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_training');
    supersedeConsentDocument('consent.ai_training');

    $this->artisan('laranail::ai-compliance.notify-reconsent', ['--dry-run' => true])
        ->expectsOutputToContain('Users needing re-consent')
        ->assertSuccessful();

    Notification::assertNothingSent();

    // the user re-grants under 2.0; a later run notifies nobody
    app(ConsentManager::class)->grant($user, 'ai_training');

    $this->artisan('laranail::ai-compliance.notify-reconsent')
        ->expectsOutputToContain('0 notifications queued')
        ->assertSuccessful();

    Notification::assertNothingSent();
});
