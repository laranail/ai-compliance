<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\AiCompliance\Enums\LegalBasis;
use Simtabi\Laranail\AiCompliance\Models\ConsentType;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;

/**
 * @extends Factory<ConsentType>
 */
class ConsentTypeFactory extends Factory
{
    protected $model = ConsentType::class;

    public function definition(): array
    {
        return [
            'slug'          => 'type_' . fake()->unique()->word(),
            'label'         => fake()->words(3, true),
            'legal_basis'   => LegalBasis::Consent,
            'default_state' => ConsentStatus::Denied,
            'active'        => true,
        ];
    }
}
