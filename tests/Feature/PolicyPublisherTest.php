<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;
use Simtabi\Laranail\AiCompliance\Events\PolicyPublished;
use Simtabi\Laranail\AiCompliance\Exceptions\CannotPublishVersion;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyPublisher;

uses(RefreshDatabase::class);

it('publishes a draft and supersedes the current published version atomically', function (): void {
    $document = PolicyDocument::factory()->create();
    $published = PolicyVersion::factory()->published()->for($document, 'document')->create(['version' => '1.0']);
    $draft = PolicyVersion::factory()->for($document, 'document')->create(['version' => '1.1']);

    Event::fake([PolicyPublished::class]);

    $this->app->make(PolicyPublisher::class)->publish($draft);

    expect($draft->fresh()?->status)->toBe(PolicyVersionStatus::Published)
        ->and($draft->fresh()?->published_at)->not->toBeNull()
        ->and($published->fresh()?->status)->toBe(PolicyVersionStatus::Superseded)
        ->and($published->fresh()?->superseded_at)->not->toBeNull();

    Event::assertDispatched(PolicyPublished::class, fn (PolicyPublished $event): bool => $event->version->is($draft) && $event->superseded?->is($published) === true);
});

it('keeps the single-published invariant across repeated publish cycles', function (): void {
    $document = PolicyDocument::factory()->create();

    foreach (['1.0', '1.1', '1.2'] as $number) {
        $draft = PolicyVersion::factory()->for($document, 'document')->create(['version' => $number]);
        $this->app->make(PolicyPublisher::class)->publish($draft);

        expect($document->versions()->where('status', PolicyVersionStatus::Published->value)->count())->toBe(1);
    }

    expect($document->versions()->where('status', PolicyVersionStatus::Superseded->value)->count())->toBe(2)
        ->and($document->publishedVersion()->firstOrFail()->version)->toBe('1.2');
});

it('refuses to publish anything that is not a draft', function (): void {
    $version = PolicyVersion::factory()->published()->create();

    $this->app->make(PolicyPublisher::class)->publish($version);
})->throws(CannotPublishVersion::class);

it('records who published when a publisher is given', function (): void {
    config()->set('laranail.ai-compliance.morph_map', []);

    $draft = PolicyVersion::factory()->create();

    $publisher = new class extends Model
    {
        protected $table = 'users';

        public function getMorphClass(): string
        {
            return 'user';
        }
    };
    $publisher->id = 42;
    $publisher->exists = true;

    $this->app->make(PolicyPublisher::class)->publish($draft, $publisher);

    expect($draft->fresh()?->authorable_type)->toBe('user')
        ->and($draft->fresh()?->authorable_id)->toBe(42);
});
