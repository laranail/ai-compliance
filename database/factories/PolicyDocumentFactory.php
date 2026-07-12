<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\AiCompliance\Enums\PolicyType;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;

/**
 * @extends Factory<PolicyDocument>
 */
class PolicyDocumentFactory extends Factory
{
    protected $model = PolicyDocument::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'type' => PolicyType::Policy,
            'default_locale' => 'en',
            'active' => true,
        ];
    }

    public function consentText(string $consentTypeSlug = 'ai_training'): static
    {
        return $this->state([
            'type' => PolicyType::ConsentText,
            'slug' => 'consent.' . $consentTypeSlug,
            'consent_type_slug' => $consentTypeSlug,
        ]);
    }

    public function disclosure(string $surface = 'chat'): static
    {
        return $this->state([
            'type' => PolicyType::Disclosure,
            'slug' => 'disclosure.' . $surface,
            'surface' => $surface,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
