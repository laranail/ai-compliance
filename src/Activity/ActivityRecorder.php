<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Activity;

use Illuminate\Database\Eloquent\Model;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;

/**
 * Writes ai activity events. Context must never carry raw prompts or other
 * sensitive content — enough to reconstruct what happened, nothing more.
 */
final class ActivityRecorder
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(
        ActivityType $type,
        ?Model $actor = null,
        Model|string|null $subject = null,
        array $context = [],
    ): ActivityEvent {
        if (is_string($subject)) {
            $context['guest_key'] = $subject;
        }

        return ActivityEvent::query()->create([
            'event_type' => $type,
            'actorable_type' => $actor?->getMorphClass(),
            'actorable_id' => $actor?->getKey(),
            'subjectable_type' => $subject instanceof Model ? $subject->getMorphClass() : null,
            'subjectable_id' => $subject instanceof Model ? $subject->getKey() : null,
            'context' => $context,
        ]);
    }
}
