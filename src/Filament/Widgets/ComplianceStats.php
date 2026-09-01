<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Override;
use Simtabi\Laranail\AiCompliance\Support\DashboardStats;

/**
 * The spec fr-1 dashboard tiles as filament stats, from the same service
 * the admin json endpoint serves.
 */
final class ComplianceStats extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        $tiles = app(DashboardStats::class)->tiles();

        $checklist = $tiles['checklist'];

        return [
            Stat::make('Consents granted', (string) $tiles['consents']['granted']),
            Stat::make('Consents denied', (string) $tiles['consents']['denied']),
            Stat::make('AI providers', (string) $tiles['providers']),
            Stat::make('Logged AI events', (string) $tiles['activity_events']),
            Stat::make('Checklist', sprintf(
                '%d ok · %d review · %d fail · %d n/a',
                $checklist['ok'],
                $checklist['review'],
                $checklist['fail'],
                $checklist['na'],
            )),
        ];
    }
}
