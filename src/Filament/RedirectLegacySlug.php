<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\AiCompliance\Support\RegisteredNames;

/**
 * Answers a deprecated Filament URL (`{panel}/providers/5/edit`) with a redirect to the page
 * under its scoped slug (`{panel}/laranail-ai-compliance/providers/5/edit`), keeping the query
 * string.
 *
 * Each legacy route is the page's own route, registered again under the old slug and old route
 * name with this class as its action, so it carries the panel's prefix, tenancy and middleware,
 * and a host's `route('filament.admin.resources.providers.edit', $record)` still generates a
 * working link. The target is generated from the scoped route's name with the request's own
 * route parameters, so a tenant segment survives without this class knowing about it. The page
 * itself still decides access when the redirect lands.
 *
 * @deprecated exists only for the pre-0.1 derived slugs; goes with them, no earlier than the
 *             next minor after 0.1.
 */
final class RedirectLegacySlug
{
    /** Route action key holding the scoped route name to redirect to. */
    public const string TARGET = 'laranail_ai_compliance_redirect_to';

    /** Route action key holding the deprecated slug, for the notice. */
    public const string LEGACY = 'laranail_ai_compliance_legacy_slug';

    /** Route action key holding the scoped slug, for the notice. */
    public const string SCOPED = 'laranail_ai_compliance_scoped_slug';

    public function __invoke(Request $request): RedirectResponse
    {
        /** @var Route $route */
        $route = $request->route();

        $legacy = (string) $route->getAction(self::LEGACY);
        $scoped = (string) $route->getAction(self::SCOPED);

        RegisteredNames::announce('filament:' . $legacy, $legacy, $scoped, 'Filament slug');

        $query = $request->getQueryString();

        return new RedirectResponse(
            URL::route((string) $route->getAction(self::TARGET), $route->parameters())
            . ($query !== null && $query !== '' ? '?' . $query : ''),
        );
    }

    /**
     * Turn a freshly registered page route into a redirect to its scoped counterpart.
     */
    public static function retarget(Route $route, string $target, string $legacySlug, string $scopedSlug): Route
    {
        $route->uses(self::class . '@__invoke');

        return $route->setAction([
            ...$route->getAction(),
            self::TARGET => $target,
            self::LEGACY => $legacySlug,
            self::SCOPED => $scopedSlug,
        ]);
    }
}
