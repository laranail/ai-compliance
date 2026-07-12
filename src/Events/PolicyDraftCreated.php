<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Events;

use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;

final readonly class PolicyDraftCreated
{
    public function __construct(
        public PolicyVersion $draft,
    ) {}
}
