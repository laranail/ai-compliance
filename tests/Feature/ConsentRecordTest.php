<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;

uses(RefreshDatabase::class);

it('assigns a public ulid and a recorded_at on creation', function (): void {
    $record = ConsentRecord::factory()->create();

    expect($record->public_id)->toHaveLength(26)
        ->and($record->recorded_at)->not->toBeNull();
});

it('throws on update: consent records are append-only', function (): void {
    $record = ConsentRecord::factory()->create();

    $record->update(['status' => 'granted']);
})->throws(LogicException::class, 'append-only');

it('throws on delete', function (): void {
    $record = ConsentRecord::factory()->create();

    $record->delete();
})->throws(LogicException::class, 'append-only');

it('rejects a record carrying both a subject and a guest key', function (): void {
    $user = makeUser();

    ConsentRecord::factory()
        ->forSubject($user)
        ->create(['guest_key' => 'g_also_a_guest']);
})->throws(InvalidArgumentException::class, 'exactly one of subject or guest_key');

it('rejects a record carrying neither subject nor guest key', function (): void {
    ConsentRecord::factory()->create(['guest_key' => null]);
})->throws(InvalidArgumentException::class, 'exactly one of subject or guest_key');

it('stores the short user morph alias, not the fqcn', function (): void {
    $user = makeUser();

    $record = ConsentRecord::factory()->forSubject($user)->create();

    expect($record->subjectable_type)->toBe('user')
        ->and($record->subjectable->is($user))->toBeTrue();
});
