<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Enums;

enum ActivityType: string
{
    case Inference = 'inference';
    case ConsentChange = 'consent_change';
    case ProviderChange = 'provider_change';
    case SettingChange = 'setting_change';
    case Decision = 'decision';
    case Override = 'override';
    case DsrAction = 'dsr_action';
    case Export = 'export';
    case Incident = 'incident';
}
