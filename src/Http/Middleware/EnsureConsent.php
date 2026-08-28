<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Simtabi\Laranail\AiCompliance\Consent\GuestKeys;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Route middleware `ai.consent:{type}`: the current subject (user or guest)
 * must have granted the consent type, otherwise 403. Guests without a key
 * carry every default state, so a denied-by-default type blocks them too.
 */
final readonly class EnsureConsent
{
    public function __construct(
        private ConsentManager $consent,
        private GuestKeys $guestKeys,
    ) {}

    public function handle(Request $request, Closure $next, string $type): Response
    {
        $subject = $request->user() ?? $this->guestKeys->current($request);

        if ($subject === null || ! $this->consent->granted($subject, $type)) {
            throw new AccessDeniedHttpException(sprintf('consent [%s] has not been granted', $type));
        }

        return $next($request);
    }
}
