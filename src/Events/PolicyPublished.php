<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Events;

use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;

final readonly class PolicyPublished
{
    public function __construct(
        public PolicyVersion $version,
        public ?PolicyVersion $superseded = null,
    ) {}
}
