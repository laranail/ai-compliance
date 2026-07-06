<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Resources\PolicyDocuments\Pages;

use Filament\Resources\Pages\ListRecords;
use Simtabi\Laranail\AiCompliance\Filament\Resources\PolicyDocumentResource;

final class ListPolicyDocuments extends ListRecords
{
    protected static string $resource = PolicyDocumentResource::class;
}
