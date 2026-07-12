<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\AiCompliance\Models\PolicyTranslation;

/**
 * @extends Factory<PolicyTranslation>
 */
class PolicyTranslationFactory extends Factory
{
    protected $model = PolicyTranslation::class;

    public function definition(): array
    {
        $markdown = fake()->paragraph();
        $checksum = hash('sha256', $markdown);

        return [
            'policy_version_id' => PolicyVersionFactory::new(),
            'locale' => 'en',
            'title' => fake()->sentence(4),
            'source_markdown' => $markdown,
            'compiled_html' => '<p>' . e($markdown) . '</p>',
            'meta' => ['title' => 'Factory document'],
            'checksum' => $checksum,
            'file_checksum' => $checksum, // imported and untouched by default
            'origin_checksum' => null,
        ];
    }

    /**
     * An admin changed the markdown after import — sync must flag, never
     * overwrite.
     */
    public function handEdited(): static
    {
        return $this->state(function (array $attributes): array {
            $edited = ($attributes['source_markdown'] ?? 'edited') . ' (edited)';

            return [
                'source_markdown' => $edited,
                'checksum' => hash('sha256', $edited),
            ];
        });
    }

    /**
     * A translation made from a default-locale source that has since changed.
     */
    public function stale(): static
    {
        return $this->state([
            'locale' => 'de',
            'origin_checksum' => hash('sha256', 'a previous default-locale source'),
        ]);
    }
}
