<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources\Providers\Pages;

use Filament\Resources\Pages\CreateRecord;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ProviderResource;

final class CreateProvider extends CreateRecord
{
    protected static string $resource = ProviderResource::class;
}
