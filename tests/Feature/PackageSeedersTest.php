<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Database\Seeders\DemoSeeder;
use Simtabi\Laranail\Package\Tools\Services\Database\SeederManager;

uses(RefreshDatabase::class);

it('registers the package seeders with the package-tools registry', function (): void {
    $registered = app(SeederManager::class)->registry()->get('laranail/ai-compliance');

    expect($registered)->not->toBeNull()
        ->and($registered->seeders())->toHaveCount(2)
        ->and($registered->seeders())->not->toContain(DemoSeeder::class); // demo data is on demand only
});

it('auto-seeds the checklist and policies when the host seeds', function (): void {
    expect(ChecklistItem::query()->count())->toBe(0);

    // package-tools v7: registration happens at boot, execution at db:seed
    // time. run() executes every registered bundle — the path the host's
    // db:seed drives (runAutorun() is only for autorun-flagged bundles and is
    // gated off in tests; this bundle registers via whenConfig, not autorun).
    app(SeederManager::class)->run();

    expect(ChecklistItem::query()->count())->toBeGreaterThan(40)
        ->and(PolicyDocument::query()->count())->toBe(14);
});
