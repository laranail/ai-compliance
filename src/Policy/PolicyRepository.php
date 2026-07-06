<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\CompiledPolicy;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyContent;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;

/**
 * Resolves policy documents for serving. Resolution order per (slug,
 * locale): a published database version (from the versioning milestone),
 * then each locale in the fallback chain against the file loader. The
 * returned content reports the locale actually served so UIs can flag
 * fallbacks, and the placeholders that remained unresolved.
 */
final readonly class PolicyRepository
{
    public function __construct(
        private PolicyFileLoader $files,
        private PolicyCompiler $compiler,
        private CompiledPolicyCache $cache,
        private PlaceholderRegistry $placeholders,
        private ConfigRepository $config,
    ) {}

    public function find(string $slug, ?string $locale = null): ?PolicyContent
    {
        $requested = $locale ?? $this->appLocale();

        foreach ($this->fallbackChain($requested) as $candidate) {
            $file = $this->files->find($slug, $candidate);

            if ($file instanceof PolicyFile) {
                return $this->serve($file, $requested);
            }
        }

        return null;
    }

    /**
     * All documents visible in a locale, resolved slug by slug through the
     * fallback chain.
     *
     * @return list<PolicyContent>
     */
    public function all(?string $locale = null): array
    {
        $requested = $locale ?? $this->appLocale();
        $resolved = [];

        foreach ($this->fallbackChain($requested) as $candidate) {
            foreach ($this->files->all($candidate) as $file) {
                if (! isset($resolved[$file->slug])) {
                    $resolved[$file->slug] = $this->serve($file, $requested);
                }
            }
        }

        return array_values($resolved);
    }

    /**
     * The locales tried for a request, most specific first, always ending at
     * the package default.
     *
     * @return list<string>
     */
    public function fallbackChain(string $locale): array
    {
        $chain = [$locale];

        $fallbacks = $this->config->get('laranail.ai-compliance.locales.fallbacks', []);
        $configured = is_array($fallbacks) ? ($fallbacks[$locale] ?? []) : [];

        foreach (is_array($configured) ? $configured : [$configured] as $fallback) {
            if (is_string($fallback)) {
                $chain[] = $fallback;
            }
        }

        $appFallback = $this->config->get('app.fallback_locale');

        if (is_string($appFallback) && $appFallback !== '') {
            $chain[] = $appFallback;
        }

        $default = $this->config->get('laranail.ai-compliance.locales.default', 'en');
        $chain[] = is_string($default) ? $default : 'en';

        return array_values(array_unique($chain));
    }

    private function serve(PolicyFile $file, string $requestedLocale): PolicyContent
    {
        $compiled = $this->cache->remember($file, fn (): CompiledPolicy => $this->compiler->compile($file));

        $html = $this->placeholders->substitute($compiled->html);
        $title = $this->placeholders->substitute($compiled->title() ?? Str::headline(Str::afterLast($file->slug, '.')));

        return new PolicyContent(
            slug: $file->slug,
            type: $file->type,
            locale: $file->locale,
            requestedLocale: $requestedLocale,
            title: $title->text,
            html: $html->text,
            meta: $compiled->meta,
            version: null,
            unresolvedPlaceholders: array_values(array_unique([...$html->unresolved, ...$title->unresolved])),
        );
    }

    private function appLocale(): string
    {
        $locale = $this->config->get('app.locale', 'en');

        return is_string($locale) ? $locale : 'en';
    }
}
