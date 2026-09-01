<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Activity;

use Illuminate\Database\Eloquent\Model;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;

/**
 * Writes ai activity events. Context must never carry raw prompts or other
 * sensitive content — enough to reconstruct what happened, nothing more.
 * With the hash chain enabled, every event links to its predecessor for
 * tamper evidence.
 */
final readonly class ActivityRecorder
{
    public function __construct(
        private ActivityChain $chain,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function record(
        ActivityType $type,
        ?Model $actor = null,
        Model|string|null $subject = null,
        array $context = [],
        ?int $providerId = null,
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
            'provider_id' => $providerId,
            'context' => $context,
            'hash_prev' => $this->chain->enabled() ? $this->chain->nextLink() : null,
        ]);
    }
}
