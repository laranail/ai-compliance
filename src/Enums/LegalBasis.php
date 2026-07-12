<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Enums;

enum LegalBasis: string
{
    case Consent = 'consent';
    case LegitimateInterest = 'legitimate_interest';
    case Contract = 'contract';
}
