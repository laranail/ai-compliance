<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Payload;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Enums\PolicyType;
use Simtabi\Laranail\AiCompliance\Policy\PlaceholderRegistry;
use Simtabi\Laranail\AiCompliance\Policy\PolicyCompiler;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyContent;

/**
 * Assembles the shared component contract: one payload consumed in-process
 * by the Blade/Livewire/Filament renderers and over GET /ai-compliance/boot
 * by the JS core (and its React/Vue bindings). CONTRACT bumps only on
 * breaking shape changes; additive keys are fine within a major.
 */
final readonly class BootPayload
{
    public const int CONTRACT = 1;

    public function __construct(
        private PolicyRepository $policies,
        private PolicyCompiler $compiler,
        private PlaceholderRegistry $placeholders,
        private ConsentManager $consent,
        private ConfigRepository $config,
        private Translator $translator,
        private UrlGenerator $url,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(
        ?string $locale = null,
        Model|Authenticatable|null $user = null,
        ?string $guestKey = null,
    ): array {
        $locale ??= $this->appLocale();
        $subject = $user ?? $guestKey;

        return [
            'contract' => self::CONTRACT,
            'locale' => $locale,
            'fallback_locale' => $this->fallbackLocale(),
            'consent' => [
                'types' => $this->consentTypes($locale),
                'state' => $subject !== null ? $this->consent->stateFor($subject) : $this->defaultState(),
                'reconsent' => $subject !== null ? $this->consent->reconsentFor($subject) : [],
            ],
            'disclosures' => $this->disclosures($locale),
            'documents' => $this->documents($locale),
            'strings' => $this->strings($locale),
            'endpoints' => $this->endpoints(),
            'guest_key' => $guestKey,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function consentTypes(string $locale): array
    {
        $types = [];

        foreach ($this->configuredConsentTypes() as $slug => $settings) {
            $document = $this->policies->find('consent.' . $slug, $locale);

            $types[] = [
                'slug' => $slug,
                'label' => $this->translate('consent_types.' . $slug . '.label', $locale),
                'description' => $this->translate('consent_types.' . $slug . '.description', $locale),
                'legal_basis' => $this->stringSetting($settings, 'legal_basis', 'consent'),
                'default_state' => $this->stringSetting($settings, 'default_state', 'denied'),
                'short_html' => $this->shortHtml($document),
                'policy_version' => $document?->version,
            ];
        }

        return $types;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function defaultState(): array
    {
        $state = [];

        foreach ($this->configuredConsentTypes() as $slug => $settings) {
            $state[$slug] = [
                'status' => $this->stringSetting($settings, 'default_state', 'denied'),
                'recorded_at' => null,
                'policy_version' => null,
            ];
        }

        return $state;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function disclosures(string $locale): array
    {
        $surfaces = $this->config->get('laranail.ai-compliance.disclosure_surfaces', []);
        $disclosures = [];

        foreach (is_array($surfaces) ? $surfaces : [] as $surface) {
            if (! is_string($surface)) {
                continue;
            }

            $document = $this->policies->find('disclosure.' . $surface, $locale);

            if (! $document instanceof PolicyContent) {
                continue;
            }

            $disclosures[$surface] = [
                'html' => $document->html,
                'version' => $document->version,
            ];
        }

        return $disclosures;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function documents(string $locale): array
    {
        $documents = [];

        foreach ($this->policies->all($locale) as $document) {
            if ($document->type !== PolicyType::Policy) {
                continue;
            }

            $documents[$document->slug] = [
                'title' => $document->title,
                'url' => $this->url->route('laranail.ai-compliance.policy', ['slug' => $document->slug]),
                'version' => $document->version,
            ];
        }

        return $documents;
    }

    /**
     * @return array<string, string>
     */
    private function strings(string $locale): array
    {
        $strings = $this->translator->get('ai-compliance::ai-compliance.strings', [], $locale);

        if (! is_array($strings)) {
            return [];
        }

        $flat = [];

        foreach ($strings as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $flat[$key] = $this->placeholders->substitute($value)->text;
            }
        }

        return $flat;
    }

    /**
     * @return array<string, string>
     */
    private function endpoints(): array
    {
        return [
            'boot' => $this->url->route('laranail.ai-compliance.boot'),
            'policy' => $this->url->route('laranail.ai-compliance.policy', ['slug' => '__slug__']),
            'consents' => $this->url->route('laranail.ai-compliance.consents'),
        ];
    }

    private function shortHtml(?PolicyContent $document): ?string
    {
        if (! $document instanceof PolicyContent) {
            return null;
        }

        $short = $document->meta['short'] ?? null;

        if (! is_string($short) || $short === '') {
            return null;
        }

        return $this->placeholders->substitute($this->compiler->inline($short))->text;
    }

    /**
     * @return array<string, mixed>
     */
    private function configuredConsentTypes(): array
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

    private function stringSetting(mixed $settings, string $key, string $default): string
    {
        if (is_array($settings) && is_string($settings[$key] ?? null)) {
            return $settings[$key];
        }

        return $default;
    }

    private function translate(string $key, string $locale): ?string
    {
        $fullKey = 'ai-compliance::ai-compliance.' . $key;
        $translated = $this->translator->get($fullKey, [], $locale);

        return is_string($translated) && $translated !== $fullKey ? $translated : null;
    }

    private function appLocale(): string
    {
        $locale = $this->config->get('app.locale', 'en');

        return is_string($locale) ? $locale : 'en';
    }

    private function fallbackLocale(): string
    {
        $fallback = $this->config->get('app.fallback_locale', 'en');

        return is_string($fallback) ? $fallback : 'en';
    }
}
