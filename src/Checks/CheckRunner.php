<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\AiCompliance\Enums\CheckStatus;
use Simtabi\Laranail\AiCompliance\Events\CheckFailed;
use Simtabi\Laranail\AiCompliance\Events\ChecklistItemDegraded;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;

/**
 * Runs every registered check (the built-ins plus anything the host tagged
 * 'ai-compliance.checks'), writes each result to its checklist item, and
 * sweeps manual items whose verification went stale from ok back to review.
 * Items switched off by classification (na) are skipped entirely.
 */
final readonly class CheckRunner
{
    /**
     * @param  list<class-string<Check>>  $builtin
     */
    public function __construct(
        private Container $container,
        private Dispatcher $events,
        private array $builtin,
    ) {}

    /**
     * @return list<array{key: string, status: string, message: string}>
     */
    public function run(): array
    {
        $results = [];

        foreach ($this->checks() as $check) {
            /** @var ChecklistItem|null $item */
            $item = ChecklistItem::query()
                ->forDefaultTenant()
                ->where('key', $check->key())
                ->first();
            if ($item === null) {
                continue;
            }
            if ($item->status === CheckStatus::NotApplicable) {
                continue;
            }

            $result = $check->run();

            $item->update([
                'status' => $result->status,
                'evidence_ref' => $result->message,
                'last_verified_at' => now(),
                'verified_by' => 'check-runner',
            ]);

            if ($result->status === CheckStatus::Fail) {
                $this->events->dispatch(new CheckFailed($item, $result));
            }

            $results[] = [
                'key' => $check->key(),
                'status' => $result->status->value,
                'message' => $result->message,
            ];
        }

        foreach ($this->staleSweep() as $degraded) {
            $results[] = $degraded;
        }

        return $results;
    }

    /**
     * @return list<array{key: string, status: string, message: string}>
     */
    private function staleSweep(): array
    {
        $degraded = [];

        $candidates = ChecklistItem::query()
            ->forDefaultTenant()
            ->where('evidence_type', 'manual')
            ->where('status', CheckStatus::Ok->value)
            ->get();

        foreach ($candidates as $item) {
            if (! $item->isStale()) {
                continue;
            }

            $message = sprintf(
                'verification from %s is older than %d months; re-verify',
                $item->last_verified_at?->toDateString() ?? 'unknown',
                $item->staleness_months,
            );

            $item->update([
                'status' => CheckStatus::Review,
                'evidence_ref' => $message,
            ]);

            $this->events->dispatch(new ChecklistItemDegraded($item));

            $degraded[] = ['key' => $item->key, 'status' => CheckStatus::Review->value, 'message' => $message];
        }

        return $degraded;
    }

    /**
     * @return list<Check>
     */
    private function checks(): array
    {
        $checks = [];

        foreach ($this->builtin as $class) {
            $check = $this->container->make($class);

            if ($check instanceof Check) {
                $checks[] = $check;
            }
        }

        foreach ($this->container->tagged('ai-compliance.checks') as $check) {
            if ($check instanceof Check) {
                $checks[] = $check;
            }
        }

        return $checks;
    }
}
