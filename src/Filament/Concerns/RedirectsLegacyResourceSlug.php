<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Concerns;

use Closure;
use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Filament\Resources\ResourceConfiguration;
use Simtabi\Laranail\AiCompliance\Filament\RedirectLegacySlug;

/**
 * Registers a resource's pages, plus every page again under the resource's pre-0.1 derived
 * slug and route names, answering with a redirect to the scoped slug.
 *
 * The redirects are skipped for a configured, clustered or nested resource, whose URLs never
 * were the derived slug. The using class declares `LEGACY_SLUG`.
 *
 * @deprecated the redirects only; they go with the old slugs, no earlier than the next minor
 *             after 0.1.
 */
trait RedirectsLegacyResourceSlug
{
    public static function registerRoutes(Panel $panel, ?Closure $registerPageRoutes = null, ?ResourceConfiguration $configuration = null): void
    {
        parent::registerRoutes($panel, $registerPageRoutes, $configuration);

        if ($registerPageRoutes instanceof Closure
            || $configuration instanceof ResourceConfiguration
            || filled(static::getCluster())
            || static::getParentResourceRegistration() !== null) {
            return;
        }

        $legacy = static::LEGACY_SLUG;

        Route::name('resources.' . $legacy . '.')
            ->prefix($legacy)
            ->group(static function () use ($panel, $legacy): void {
                foreach (static::getPages() as $name => $registration) {
                    $route = $registration->registerRoute($panel);

                    if ($route === null) {
                        continue;
                    }

                    RedirectLegacySlug::retarget(
                        $route->name((string) $name),
                        static::getRouteBaseName($panel) . '.' . $name,
                        $legacy,
                        static::getSlug($panel),
                    );
                }
            });
    }
}
