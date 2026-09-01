<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Simtabi\Laranail\AiCompliance\Features\FeatureGate;

/**
 * The feature kill switches: one per configured feature, default on, with
 * who threw the switch and when.
 */
final class FeatureController
{
    public function index(ConfigRepository $config, FeatureGate $gate): JsonResponse
    {
        $features = $config->get('laranail.ai-compliance.features', []);
        $data = [];

        foreach (array_keys(is_array($features) ? $features : []) as $feature) {
            if (is_string($feature)) {
                $data[$feature] = $gate->enabled($feature);
            }
        }

        return new JsonResponse(['data' => $data]);
    }

    public function update(Request $request, FeatureGate $gate, string $feature): JsonResponse
    {
        /** @var array{enabled: bool} $validated */
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $toggledBy = $request->user()?->getAuthIdentifier();

        $gate->toggle($feature, $validated['enabled'], $toggledBy !== null ? (string) $toggledBy : null);

        return new JsonResponse(['data' => [
            'feature' => $feature,
            'enabled' => $gate->enabled($feature),
        ]]);
    }
}
