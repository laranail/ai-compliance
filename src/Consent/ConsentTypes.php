<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Consent;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Translation\Translator;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;
use Simtabi\Laranail\AiCompliance\Exceptions\UnknownConsentType;
use Simtabi\Laranail\AiCompliance\Models\ConsentType;

/**
 * Resolves consent-type rows. Config declares the types; database rows are
 * the foreign-key anchor for consent records and are created lazily from
 * config on first use, so recording consent works without a seeding step.
 */
final readonly class ConsentTypes
{
    public function __construct(
        private ConfigRepository $config,
        private Translator $translator,
    ) {}

    public function resolve(string $slug): ConsentType
    {
        $existing = ConsentType::query()->where('slug', $slug)->first();

        if ($existing instanceof ConsentType) {
            return $existing;
        }

        $configured = $this->configured();

        if (! array_key_exists($slug, $configured)) {
            throw UnknownConsentType::slug($slug);
        }

        return $this->createFromConfig($slug, $configured[$slug]);
    }

    /**
     * Insert every configured type idempotently (the seeder's whole job).
     */
    public function seedFromConfig(): void
    {
        foreach ($this->configured() as $slug => $settings) {
            if (! ConsentType::query()->where('slug', $slug)->exists()) {
                $this->createFromConfig($slug, $settings);
            }
        }
    }

    public function defaultStateFor(string $slug): ConsentStatus
    {
        $configured = $this->configured();
        $state = is_array($configured[$slug] ?? null) ? ($configured[$slug]['default_state'] ?? null) : null;

        return ConsentStatus::tryFrom(is_string($state) ? $state : '') ?? ConsentStatus::Denied;
    }

    /**
     * @return list<string>
     */
    public function slugs(): array
    {
        return array_keys($this->configured());
    }

    /**
     * @return array<string, mixed>
     */
    private function configured(): array
    {
        $types = $this->config->get('laranail.ai-compliance.consent_types', []);
        $configured = [];

        foreach (is_array($types) ? $types : [] as $slug => $settings) {
            if (is_string($slug)) {
                $configured[$slug] = $settings;
            }
        }

        return $configured;
    }

    private function createFromConfig(string $slug, mixed $settings): ConsentType
    {
        $label = $this->translator->get('laranail-ai-compliance::ai-compliance.consent_types.' . $slug . '.label');
        $description = $this->translator->get('laranail-ai-compliance::ai-compliance.consent_types.' . $slug . '.description');

        return ConsentType::query()->create([
            'slug' => $slug,
            'label' => is_string($label) && ! str_contains($label, '::') ? $label : $slug,
            'description' => is_string($description) && ! str_contains($description, '::') ? $description : null,
            'legal_basis' => is_array($settings) && is_string($settings['legal_basis'] ?? null) ? $settings['legal_basis'] : 'consent',
            'default_state' => is_array($settings) && is_string($settings['default_state'] ?? null) ? $settings['default_state'] : 'denied',
            'active' => true,
        ]);
    }
}
