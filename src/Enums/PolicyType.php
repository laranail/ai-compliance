<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Enums;

enum PolicyType: string
{
    case Policy = 'policy';
    case ConsentText = 'consent_text';
    case Disclosure = 'disclosure';
}
