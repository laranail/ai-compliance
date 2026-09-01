<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\AiCompliance\Enums\CheckStatus;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;

/**
 * @extends Factory<ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    protected $model = ChecklistItem::class;

    public function definition(): array
    {
        return [
            'key' => 'test.'.fake()->unique()->word(),
            'section' => 'governance',
            'label' => fake()->sentence(4),
            'evidence_type' => 'manual',
            'status' => CheckStatus::Review,
            'staleness_months' => 12,
        ];
    }

    public function ok(): static
    {
        return $this->state([
            'status' => CheckStatus::Ok,
            'last_verified_at' => now(),
            'verified_by' => 'factory',
        ]);
    }

    public function fail(): static
    {
        return $this->state(['status' => CheckStatus::Fail]);
    }

    public function stale(): static
    {
        return $this->state([
            'status' => CheckStatus::Ok,
            'last_verified_at' => now()->subMonths(13),
            'verified_by' => 'factory',
            'staleness_months' => 12,
        ]);
    }

    public function auto(): static
    {
        return $this->state(['evidence_type' => 'auto']);
    }
}
