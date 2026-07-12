<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyStaleness;

/**
 * The staleness report: file drift (shipped file changed after import) and
 * translation drift (default-locale source changed after a translation was
 * made from it).
 */
final class PolicyStalenessController
{
    public function __invoke(PolicyStaleness $staleness): JsonResponse
    {
        return new JsonResponse(['data' => $staleness->report()]);
    }
}
