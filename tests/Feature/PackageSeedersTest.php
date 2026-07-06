<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Database\Seeders\DemoSeeder;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\Package\Tools\Services\Database\SeederManager;

uses(RefreshDatabase::class);

it('registers the package seeders with the package-tools registry', function (): void {
    $registered = app(SeederManager::class)->registry()->get('laranail/ai-compliance');

    expect($registered)->not->toBeNull()
        ->and($registered['seeders'])->toHaveCount(2)
        ->and($registered['seeders'])->not->toContain(DemoSeeder::class); // demo data is on demand only
});

it('auto-seeds the checklist and policies when the host seeds', function (): void {
    expect(ChecklistItem::query()->count())->toBe(0);

    // resolving any seeder from the container is what db:seed does; the
    // package-tools hook fires once and runs the registered bundle first
    app(DemoSeeder::class);

    expect(ChecklistItem::query()->count())->toBeGreaterThan(40)
        ->and(PolicyDocument::query()->count())->toBe(14);
});
