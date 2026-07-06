<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources\ConsentRecords\Pages;

use Filament\Resources\Pages\ListRecords;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ConsentRecordResource;

final class ListConsentRecords extends ListRecords
{
    protected static string $resource = ConsentRecordResource::class;
}
