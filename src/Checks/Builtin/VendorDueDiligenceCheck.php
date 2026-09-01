<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks\Builtin;

use Simtabi\Laranail\AiCompliance\Checks\Check;
use Simtabi\Laranail\AiCompliance\Checks\CheckResult;
use Simtabi\Laranail\AiCompliance\Models\Provider;

/**
 * Every provider needs a completed due-diligence record, re-reviewed on a
 * cadence: a dpa signed more than twelve months ago counts as lapsed.
 */
final class VendorDueDiligenceCheck implements Check
{
    public function key(): string
    {
        return 'vendors.due_diligence';
    }

    public function run(): CheckResult
    {
        $providers = Provider::query()->get();

        if ($providers->isEmpty()) {
            return CheckResult::review('no providers registered yet; due diligence starts with the first registry row');
        }

        $pending = $providers->where('due_diligence_status', '!=', 'complete');

        $lapsed = $providers->filter(
            fn (Provider $provider): bool => $provider->due_diligence_status === 'complete'
                && $provider->dpa_signed_at !== null
                && $provider->dpa_signed_at->addMonths(12)->isPast(),
        );

        if ($pending->isNotEmpty() || $lapsed->isNotEmpty()) {
            $parts = [];

            if ($pending->isNotEmpty()) {
                $parts[] = 'pending: '.$pending->pluck('name')->implode(', ');
            }

            if ($lapsed->isNotEmpty()) {
                $parts[] = 'lapsed (dpa older than 12 months): '.$lapsed->pluck('name')->implode(', ');
            }

            return CheckResult::review(implode('; ', $parts));
        }

        return CheckResult::ok(sprintf('due diligence complete and current for all %d providers', $providers->count()));
    }
}
