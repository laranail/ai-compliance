<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Doctor;

use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorCheck;
use Simtabi\Laranail\Package\Tools\Services\Doctor\Checks\ConfigPresentCheck;

/**
 * Doctor checks contributed by laranail/ai-compliance. Run by
 * `php artisan laranail::package-tools.doctor`.
 */
final class Checks
{
    /** @return list<DoctorCheck|class-string<DoctorCheck>> */
    public static function all(): array
    {
        return [
            new ConfigPresentCheck(
                ['' => 'laranail.ai-compliance'],
                required: true,
                name: 'ai-compliance:config',
                description: 'AI Compliance config is published',
            ),
        ];
    }
}
