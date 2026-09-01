<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks\Builtin;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Simtabi\Laranail\AiCompliance\Checks\Check;
use Simtabi\Laranail\AiCompliance\Checks\CheckResult;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;

/**
 * Logging is what turns every other section from claims into evidence: the
 * log must be receiving events. Silence beyond the configured window fails
 * the item (and alerts, via the CheckFailed listener).
 */
final readonly class ActivityLogAliveCheck implements Check
{
    public function __construct(
        private ConfigRepository $config,
    ) {}

    public function key(): string
    {
        return 'logging.activity_log_alive';
    }

    public function run(): CheckResult
    {
        /** @var ActivityEvent|null $latest */
        $latest = ActivityEvent::query()->orderByDesc('recorded_at')->orderByDesc('id')->first();

        if ($latest === null) {
            return CheckResult::review('the activity log has never received an event');
        }

        $hours = $this->silenceHours();

        if ($latest->recorded_at->addHours($hours)->isPast()) {
            return CheckResult::fail(sprintf(
                'the activity log has been silent since %s (threshold %d hours)',
                $latest->recorded_at->toIso8601String(),
                $hours,
            ));
        }

        return CheckResult::ok('the activity log is receiving events (last: '.$latest->recorded_at->toIso8601String().')');
    }

    private function silenceHours(): int
    {
        $hours = $this->config->get('laranail.ai-compliance.alerting.log_silence_hours', 24);

        return is_int($hours) && $hours > 0 ? $hours : 24;
    }
}
