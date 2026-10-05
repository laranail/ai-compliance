<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Filament\Concerns;

use Filament\Panel;
use Filament\Pages\PageConfiguration;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\AiCompliance\Filament\RedirectLegacySlug;

/**
 * Registers a page, plus its pre-0.1 derived slug and route name, answering with a redirect to
 * the scoped slug.
 *
 * The redirect is registered in the same `pages.` group as the page, so it gets the panel's
 * prefix, tenancy and auth middleware. It is skipped for a configured or clustered page, whose
 * URL never was the derived slug. The using class declares `LEGACY_SLUG`.
 *
 * @deprecated the redirect only; it goes with the old slug, no earlier than the next minor
 *             after 0.1.
 */
trait RedirectsLegacyPageSlug
{
    public static function registerRoutes(Panel $panel, ?PageConfiguration $configuration = null): void
    {
        parent::registerRoutes($panel, $configuration);

        if ($configuration instanceof PageConfiguration || filled(static::getCluster())) {
            return;
        }

        $legacy = static::LEGACY_SLUG;

        Route::name('pages.')->group(static function () use ($panel, $legacy): void {
            RedirectLegacySlug::retarget(
                Route::get('/' . $legacy, RedirectLegacySlug::class)
                    ->middleware(static::getRouteMiddleware($panel))
                    ->name($legacy),
                static::getRouteName($panel),
                $legacy,
                static::getSlug($panel),
            );
        });
    }
}
