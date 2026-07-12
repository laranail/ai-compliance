<?php

declare(strict_types=1);

use Simtabi\Laranail\AiCompliance\Policy\CompiledPolicyCache;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\CompiledPolicy;

it('recompiles after a flush even when the source checksum is unchanged', function (): void {
    config()->set('laranail.ai-compliance.policies.cache.enabled', true);

    $cache = $this->app->make(CompiledPolicyCache::class);
    $file = policyFile('Cached content.');
    $compilations = 0;

    $compile = function () use (&$compilations, $file): CompiledPolicy {
        $compilations++;

        return new CompiledPolicy('<p>compiled</p>', [], $file->checksum);
    };

    $cache->remember($file, $compile);
    $cache->remember($file, $compile);

    expect($compilations)->toBe(1);

    $cache->flush();
    $cache->remember($file, $compile);

    expect($compilations)->toBe(2);
});
