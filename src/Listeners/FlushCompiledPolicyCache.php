<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Listeners;

use Simtabi\Laranail\AiCompliance\Policy\CompiledPolicyCache;

/**
 * Drops all cached compiled policies whenever content changes shape at the
 * database layer (a publish or a sync), so superseded file-compiled entries
 * can never shadow the new source of truth.
 */
final readonly class FlushCompiledPolicyCache
{
    public function __construct(
        private CompiledPolicyCache $cache,
    ) {}

    public function handle(): void
    {
        $this->cache->flush();
    }
}
