<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks\Builtin;

use Simtabi\Laranail\AiCompliance\Checks\Check;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Simtabi\Laranail\AiCompliance\Checks\CheckResult;

/**
 * The provider registry must be non-empty with complete rows: no "unknown
 * model" entries.
 */
final class ProviderRegistryCheck implements Check
{
    public function key(): string
    {
        return 'governance.provider_registry';
    }

    public function run(): CheckResult
    {
        $providers = Provider::query()->get();

        if ($providers->isEmpty()) {
            return CheckResult::fail('no ai providers are registered');
        }

        $incomplete = $providers->reject(fn (Provider $provider): bool => $provider->isComplete());

        if ($incomplete->isNotEmpty()) {
            return CheckResult::review(sprintf(
                '%d of %d registry rows are incomplete (dpa, region, purpose, or due diligence missing): %s',
                $incomplete->count(),
                $providers->count(),
                $incomplete->pluck('name')->implode(', '),
            ));
        }

        return CheckResult::ok(sprintf('%d providers registered, all rows complete', $providers->count()));
    }
}
