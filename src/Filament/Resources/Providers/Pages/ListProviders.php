<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources\Providers\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ProviderResource;

final class ListProviders extends ListRecords
{
    protected static string $resource = ProviderResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
