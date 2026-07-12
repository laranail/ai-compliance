<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources\Providers\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ProviderResource;

final class EditProvider extends EditRecord
{
    protected static string $resource = ProviderResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
