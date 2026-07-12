<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Events;

use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;

final readonly class InferenceLogged
{
    public function __construct(
        public ActivityEvent $event,
    ) {}
}
