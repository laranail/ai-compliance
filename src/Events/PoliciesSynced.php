<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Events;

use Simtabi\Laranail\AiCompliance\Policy\Versioning\SyncResult;

final readonly class PoliciesSynced
{
    public function __construct(
        public SyncResult $result,
    ) {}
}
