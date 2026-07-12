<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Enums;

enum CheckStatus: string
{
    case Ok = 'ok';
    case Review = 'review';
    case Fail = 'fail';
    case NotApplicable = 'na';
}
