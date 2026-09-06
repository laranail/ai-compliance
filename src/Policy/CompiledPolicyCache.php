<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy;

use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\CompiledPolicy;

/**
 * Content-addressed cache for compiled policies. The key embeds the source
 * checksum, so an edited file is a natural miss and no invalidation is
 * needed for file changes; publish events flush eagerly anyway (from the
 * versioning milestone) to drop superseded entries.
 */
final readonly class CompiledPolicyCache
{
    private const string PREFIX = 'laranail.ai-compliance.policy';

    private const string GENERATION_KEY = 'laranail.ai-compliance.policy.generation';

    public function __construct(
        private CacheFactory $cache,
        private ConfigRepository $config,
    ) {}

    /**
     * @param Closure(): CompiledPolicy $compile
     */
    public function remember(PolicyFile $file, Closure $compile): CompiledPolicy
    {
        if (! $this->enabled()) {
            return $compile();
        }

        $key = $this->key($file);

        $cached = $this->store()->get($key);

        if ($cached instanceof CompiledPolicy) {
            return $cached;
        }

        $compiled = $compile();

        $this->store()->forever($key, $compiled);

        return $compiled;
    }

    /**
     * Invalidate every cached compiled policy by bumping the generation the
     * keys embed. Store-agnostic (no tag support required); orphaned entries
     * age out of stores with eviction.
     */
    public function flush(): void
    {
        $this->store()->forever(self::GENERATION_KEY, $this->generation() + 1);
    }

    public function key(PolicyFile $file): string
    {
        return sprintf('%s.g%d.%s.%s.%s', self::PREFIX, $this->generation(), $file->slug, $file->locale, $file->checksum);
    }

    private function generation(): int
    {
        $generation = $this->store()->get(self::GENERATION_KEY, 1);

        return is_int($generation) ? $generation : 1;
    }

    private function enabled(): bool
    {
        return (bool) $this->config->get('laranail.ai-compliance.policies.cache.enabled', true);
    }

    private function store(): CacheRepository
    {
        $store = $this->config->get('laranail.ai-compliance.policies.cache.store');

        return $this->cache->store(is_string($store) ? $store : null);
    }
}
