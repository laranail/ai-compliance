<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Events;

use Simtabi\Laranail\AiCompliance\Checks\CheckResult;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;

final readonly class CheckFailed
{
    public function __construct(
        public ChecklistItem $item,
        public CheckResult $result,
    ) {}
}
