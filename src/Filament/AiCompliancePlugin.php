<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Simtabi\Laranail\AiCompliance\Filament\Pages\Classification;
use Simtabi\Laranail\AiCompliance\Filament\Widgets\ComplianceStats;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ProviderResource;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ChecklistItemResource;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ConsentRecordResource;
use Simtabi\Laranail\AiCompliance\Filament\Resources\PolicyDocumentResource;

/**
 * The filament admin plugin: policy editor, provider registry, consent log,
 * checklist, classification intake, and the dashboard stats — all consumers
 * of the same services behind the admin json api. Opt-in per panel:
 *
 *   $panel->plugin(AiCompliancePlugin::make())
 *
 * Nothing outside src/Filament references filament classes, so the package
 * boots identically when filament is not installed.
 */
final class AiCompliancePlugin implements Plugin
{
    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'laranail-ai-compliance';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                PolicyDocumentResource::class,
                ProviderResource::class,
                ConsentRecordResource::class,
                ChecklistItemResource::class,
            ])
            ->pages([
                Classification::class,
            ])
            ->widgets([
                ComplianceStats::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // nothing to boot; everything registers above
    }
}
