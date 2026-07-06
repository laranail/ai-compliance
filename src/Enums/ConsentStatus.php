<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Enums;

enum ConsentStatus: string
{
    case Granted = 'granted';
    case Denied = 'denied';
    case Withdrawn = 'withdrawn';
}
