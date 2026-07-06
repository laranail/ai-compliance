<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Simtabi\Laranail\AiCompliance\Filament\AiCompliancePlugin;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(AiCompliancePlugin::make());
    }
}
