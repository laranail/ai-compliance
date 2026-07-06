<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Events;

use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;

final readonly class ConsentRecorded
{
    public function __construct(
        public ConsentRecord $record,
    ) {}
}
