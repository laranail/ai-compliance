<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Events;

use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;

final readonly class ChecklistItemDegraded
{
    public function __construct(
        public ChecklistItem $item,
    ) {}
}
