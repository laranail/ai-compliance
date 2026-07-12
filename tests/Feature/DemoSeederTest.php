<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Database\Seeders\DemoSeeder;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Simtabi\Laranail\AiCompliance\Support\DashboardStats;

uses(RefreshDatabase::class);

it('reproduces the reference dashboard state', function (): void {
    app(DemoSeeder::class)->run();

    $tiles = app(DashboardStats::class)->tiles();

    expect(ConsentRecord::query()->count())->toBe(8)
        ->and(ConsentRecord::query()->distinct()->count('recorded_at'))->toBe(2)
        ->and($tiles['consents']['granted'])->toBe(2)
        ->and($tiles['consents']['denied'])->toBe(6)
        ->and(Provider::query()->count())->toBe(0)
        ->and($tiles['activity_events'])->toBeGreaterThanOrEqual(2);
});
