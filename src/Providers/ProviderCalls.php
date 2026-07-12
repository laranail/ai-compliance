<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory as HttpFactory;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Entry point for consent-aware outbound provider calls: resolves the
 * registry row and returns a pending call that injects the vendor's
 * do-not-train flag from the subject's consent state and logs the inference
 * event in the same motion.
 */
final readonly class ProviderCalls
{
    public function __construct(
        private ConsentManager $consent,
        private ActivityRecorder $activity,
        private HttpFactory $http,
        private ConfigRepository $config,
        private Dispatcher $events,
    ) {}

    /**
     * Resolve by registry name (exact) or vendor (first match).
     */
    public function provider(string $name): PendingProviderCall
    {
        $provider = Provider::query()->where('name', $name)->first()
            ?? Provider::query()->where('vendor', $name)->first();

        if (! $provider instanceof Provider) {
            throw new NotFoundHttpException(sprintf('provider [%s] is not in the registry; register it before calling it', $name));
        }

        return new PendingProviderCall(
            $provider,
            $this->consent,
            $this->activity,
            $this->http,
            $this->config,
            $this->events,
        );
    }
}
