<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Events;

use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;

final readonly class ConsentWithdrawn
{
    public function __construct(
        public ConsentRecord $record,
    ) {}
}
