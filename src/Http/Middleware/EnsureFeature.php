<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Simtabi\Laranail\AiCompliance\Features\FeatureGate;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Route middleware `ai.feature:{feature}`: 403 when the admin kill switch
 * (or the pennant bridge) has the feature off. Pair with ai.consent for the
 * subject-level check.
 */
final readonly class EnsureFeature
{
    public function __construct(
        private FeatureGate $features,
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! $this->features->enabled($feature)) {
            throw new AccessDeniedHttpException(sprintf('feature [%s] is disabled', $feature));
        }

        return $next($request);
    }
}
