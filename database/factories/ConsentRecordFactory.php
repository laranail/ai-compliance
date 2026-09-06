<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;

/**
 * @extends Factory<ConsentRecord>
 */
class ConsentRecordFactory extends Factory
{
    protected $model = ConsentRecord::class;

    public function definition(): array
    {
        return [
            'consent_type_id' => ConsentTypeFactory::new(),
            'guest_key'       => 'g_' . Str::random(32),
            'status'          => ConsentStatus::Denied,
            'source'          => 'factory',
            'recorded_at'     => now(),
        ];
    }

    public function granted(): static
    {
        return $this->state(['status' => ConsentStatus::Granted]);
    }

    public function withdrawn(): static
    {
        return $this->state(['status' => ConsentStatus::Withdrawn]);
    }

    public function guest(?string $key = null): static
    {
        return $this->state(['guest_key' => $key ?? 'g_' . Str::random(32)]);
    }

    public function forSubject(Model $subject): static
    {
        return $this->state([
            'guest_key'        => null,
            'subjectable_type' => $subject->getMorphClass(),
            'subjectable_id'   => $subject->getKey(),
        ]);
    }
}
