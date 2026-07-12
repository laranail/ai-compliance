<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Features;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\QueryException;
use Laravel\Pennant\Feature;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Events\FeatureToggled;
use Simtabi\Laranail\AiCompliance\Models\FeatureState;
use Throwable;

/**
 * The admin kill switch per ai feature. A feature runs unless a state row
 * disables it; when laravel/pennant is installed, a pennant feature of the
 * same name is consulted as well (both must allow). Unmigrated databases
 * count as enabled — the switch is an operations control, not a consent one.
 */
final readonly class FeatureGate
{
    public function __construct(
        private Dispatcher $events,
        private ActivityRecorder $activity,
    ) {}

    public function enabled(string $feature): bool
    {
        try {
            /** @var FeatureState|null $state */
            $state = FeatureState::query()
                ->forDefaultTenant()
                ->where('feature', $feature)
                ->first();
        } catch (QueryException) {
            return true;
        }

        if ($state instanceof FeatureState && ! $state->enabled) {
            return false;
        }

        return $this->pennantAllows($feature);
    }

    public function toggle(string $feature, bool $enabled, ?string $toggledBy = null): FeatureState
    {
        $existing = FeatureState::query()
            ->forDefaultTenant()
            ->where('feature', $feature)
            ->first();

        $attributes = [
            'enabled' => $enabled,
            'toggled_by' => $toggledBy,
            'toggled_at' => now(),
        ];

        if ($existing instanceof FeatureState) {
            $existing->update($attributes);
            $state = $existing;
        } else {
            $state = FeatureState::query()->create([
                'tenant_id' => '',
                'feature' => $feature,
                ...$attributes,
            ]);
        }

        $this->events->dispatch(new FeatureToggled($feature, $enabled, $toggledBy));

        $this->activity->record(ActivityType::SettingChange, context: [
            'setting' => 'feature',
            'feature' => $feature,
            'enabled' => $enabled,
            'toggled_by' => $toggledBy,
        ]);

        return $state;
    }

    /**
     * The pennant bridge: only consulted when pennant is installed and the
     * feature is actually defined there — an undefined pennant feature never
     * blocks.
     */
    private function pennantAllows(string $feature): bool
    {
        if (! class_exists(Feature::class)) {
            return true;
        }

        try {
            if (! in_array($feature, Feature::defined(), true)) {
                return true;
            }

            return (bool) Feature::active($feature);
        } catch (Throwable) {
            return true; // pennant present but not configured; never block on it
        }
    }
}
