<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;

/**
 * @extends Factory<PolicyVersion>
 */
class PolicyVersionFactory extends Factory
{
    protected $model = PolicyVersion::class;

    public function definition(): array
    {
        return [
            'policy_document_id' => PolicyDocumentFactory::new(),
            'version'            => '1.0',
            'status'             => PolicyVersionStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status'       => PolicyVersionStatus::Published,
            'effective_at' => now(),
            'published_at' => now(),
        ]);
    }

    public function superseded(): static
    {
        return $this->state([
            'status'        => PolicyVersionStatus::Superseded,
            'effective_at'  => now()->subDay(),
            'published_at'  => now()->subDay(),
            'superseded_at' => now(),
        ]);
    }
}
