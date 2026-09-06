<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;

/**
 * @extends Factory<ActivityEvent>
 */
class ActivityEventFactory extends Factory
{
    protected $model = ActivityEvent::class;

    public function definition(): array
    {
        return [
            'event_type'  => ActivityType::ConsentChange,
            'context'     => [],
            'recorded_at' => now(),
        ];
    }

    public function ofType(ActivityType $type): static
    {
        return $this->state(['event_type' => $type]);
    }
}
