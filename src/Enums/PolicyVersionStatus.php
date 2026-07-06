<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Enums;

enum PolicyVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Superseded = 'superseded';
}
