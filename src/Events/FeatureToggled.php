<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Events;

final readonly class FeatureToggled
{
    public function __construct(
        public string $feature,
        public bool $enabled,
        public ?string $toggledBy = null,
    ) {}
}
