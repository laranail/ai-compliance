<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources\ChecklistItems\Pages;

use Filament\Resources\Pages\ListRecords;
use Simtabi\Laranail\AiCompliance\Filament\Resources\ChecklistItemResource;

final class ListChecklistItems extends ListRecords
{
    protected static string $resource = ChecklistItemResource::class;
}
