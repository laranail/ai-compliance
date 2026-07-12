<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\AiCompliance\Support\DashboardStats;

/**
 * The dashboard tiles (spec FR-1), served from the shared stats service the
 * filament widgets also render.
 */
final class DashboardController
{
    public function __invoke(DashboardStats $stats): JsonResponse
    {
        return new JsonResponse(['data' => $stats->tiles()]);
    }
}
