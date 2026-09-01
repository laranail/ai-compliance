<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\AiCompliance\Models\Provider;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' assistant',
            'vendor' => fake()->randomElement(['Anthropic', 'OpenAI', 'Mistral']),
            'model_name' => fake()->bothify('model-##'),
            'role' => 'deployer',
            'trains_on_our_data' => 'no',
            'due_diligence_status' => 'pending',
        ];
    }

    public function dueDiligenceComplete(): static
    {
        return $this->state([
            'dpa_signed_at' => now()->subMonth(),
            'endpoint_region' => 'eu-central-1',
            'purpose' => 'support assistant',
            'due_diligence_status' => 'complete',
        ]);
    }

    public function trainsOnData(): static
    {
        return $this->state(['trains_on_our_data' => 'yes']);
    }
}
