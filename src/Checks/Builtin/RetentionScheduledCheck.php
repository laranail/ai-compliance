<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks\Builtin;

use Simtabi\Laranail\AiCompliance\Checks\Check;
use Simtabi\Laranail\AiCompliance\Checks\CheckResult;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * A retention schedule must exist for the compliance stores. The pruning
 * jobs themselves arrive with the activity milestone; until configured, the
 * item stays at review.
 */
final readonly class RetentionScheduledCheck implements Check
{
    public function __construct(
        private ConfigRepository $config,
    ) {}

    public function key(): string
    {
        return 'privacy.retention_schedule';
    }

    public function run(): CheckResult
    {
        $retention = $this->config->get('laranail.ai-compliance.retention', []);

        if (! is_array($retention) || $retention === []) {
            return CheckResult::review('no retention periods are configured (laranail.ai-compliance.retention)');
        }

        $configured = array_keys(array_filter($retention, static fn (mixed $period): bool => $period !== null));

        if ($configured === []) {
            return CheckResult::review('retention config exists but every period is null');
        }

        return CheckResult::ok('retention periods configured for: ' . implode(', ', array_map(strval(...), $configured)));
    }
}
