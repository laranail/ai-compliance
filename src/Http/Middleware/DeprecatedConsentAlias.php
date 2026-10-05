<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Simtabi\Laranail\AiCompliance\Support\RegisteredNames;

/**
 * What the bare `ai.consent` middleware alias resolves to.
 *
 * Laravel keeps middleware aliases in one flat map, so a bare `ai.consent` registered by this
 * package silently replaces an application's or another package's alias of the same name (or is
 * replaced by it). The scoped alias is `laranail-ai-compliance.consent`. This class keeps routes
 * that still name the bare alias working: it logs one warning per process naming the replacement
 * and then enforces exactly what the scoped alias enforces.
 *
 * @deprecated Use the `laranail-ai-compliance.consent` middleware alias. The bare `ai.consent` alias
 *             may be removed in the next minor after 0.1.
 */
final class DeprecatedConsentAlias
{
    private static bool $warned = false;

    public function __construct(private readonly EnsureConsent $middleware) {}

    /** @internal Lets a test observe the once-per-process warning again. */
    public static function resetWarning(): void
    {
        self::$warned = false;
    }

    public function handle(Request $request, Closure $next, string $type): Response
    {
        if (! self::$warned) {
            self::$warned = true;

            Log::warning(sprintf(
                'laranail/ai-compliance: the "%s" middleware alias is deprecated; use "%s" instead. '
                . 'The bare alias may be removed in the next minor after 0.1.',
                RegisteredNames::LEGACY_CONSENT_MIDDLEWARE,
                RegisteredNames::CONSENT_MIDDLEWARE,
            ));
        }

        return $this->middleware->handle($request, $next, $type);
    }
}
