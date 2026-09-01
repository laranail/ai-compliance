<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Console\Commands;

use Simtabi\Laranail\AiCompliance\Activity\ActivityChain;
use Simtabi\Laranail\Package\Tools\Commands\Command;

/**
 * Recomputes the activity log's hash chain and reports the first broken
 * link, if any. Only meaningful with the hash_chain tier enabled.
 */
final class VerifyChainCommand extends Command
{
    protected $signature = 'laranail::ai-compliance.verify-chain';

    protected $description = 'Verify the activity log hash chain';

    public function handle(ActivityChain $chain): int
    {
        if (! $chain->enabled()) {
            $this->components->warn('the hash chain is disabled (laranail.ai-compliance.activity.hash_chain); verifying whatever chained events exist');
        }

        $result = $chain->verify();

        if ($result['valid']) {
            $this->components->info(sprintf('chain intact across %d chained events', $result['checked']));

            return self::SUCCESS;
        }

        $this->components->error(sprintf(
            'chain BROKEN at event %s (after %d intact links) — the log was altered or pruned mid-chain',
            $result['broken_at'] ?? 'unknown',
            $result['checked'],
        ));

        return self::FAILURE;
    }
}
