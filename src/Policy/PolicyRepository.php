<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Models\PolicyTranslation;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\CompiledPolicy;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyContent;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;

/**
 * Resolves policy documents for serving. A published database version is
 * authoritative: its translations resolve through the locale fallback chain
 * and files are never consulted for that document (a file could otherwise
 * shadow the operator's published text). Documents with no published version
 * — and installs that never ran the migrations — resolve from files through
 * the same chain. Deactivated documents resolve to nothing at all. The
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

        $fromDatabase = $this->fromDatabase($slug, $requested);

        if ($fromDatabase === false) {
            return null; // document exists but is deactivated
        }

        if ($fromDatabase instanceof PolicyContent) {
            return $fromDatabase;
        }

        foreach ($this->fallbackChain($requested) as $candidate) {
            $file = $this->files->find($slug, $candidate);

            if ($file instanceof PolicyFile) {
                return $this->serve($file, $requested);
            }
        }

        return null;
    }

    /**
     * All documents visible in a locale: database-published documents first,
     * then shipped files filling the gaps (skipping deactivated documents).
     *
     * @return list<PolicyContent>
     */
    public function all(?string $locale = null): array
    {
        $requested = $locale ?? $this->appLocale();
        $resolved = [];
        $suppressed = [];

        foreach ($this->databaseDocuments() as $document) {
            if (! $document->active) {
                $suppressed[] = $document->slug;

                continue;
            }

            $content = $this->serveDocument($document, $requested);

            // a document row with nothing published yet stays file-served,
            // so it is neither resolved nor suppressed here
            if ($content instanceof PolicyContent) {
                $resolved[$content->slug] = $content;
            }
        }

        foreach ($this->fallbackChain($requested) as $candidate) {
            foreach ($this->files->all($candidate) as $file) {
                if (! isset($resolved[$file->slug]) && ! in_array($file->slug, $suppressed, true)) {
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

    /**
     * Resolve from the database. Returns PolicyContent when a published
     * translation was found, false when the document exists but is
     * deactivated (nothing should be served at all), and null to fall
     * through to files.
     */
    private function fromDatabase(string $slug, string $requested): PolicyContent|false|null
    {
        try {
            /** @var PolicyDocument|null $document */
            $document = PolicyDocument::query()
                ->forDefaultTenant()
                ->where('slug', $slug)
                ->first();
        } catch (QueryException) {
            return null; // schema not migrated: pure file mode
        }

        if ($document === null) {
            return null;
        }

        if (! $document->active) {
            return false;
        }

        return $this->serveDocument($document, $requested);
    }

    /**
     * @return list<PolicyDocument>
     */
    private function databaseDocuments(): array
    {
        try {
            return array_values(PolicyDocument::query()
                ->forDefaultTenant()
                ->with('publishedVersion.translations')
                ->get()
                ->all());
        } catch (QueryException) {
            return [];
        }
    }

    private function serveDocument(PolicyDocument $document, string $requested): ?PolicyContent
    {
        /** @var PolicyVersion|null $published */
        $published = $document->publishedVersion;

        if ($published === null) {
            return null;
        }

        $translations = $published->translations->keyBy('locale');

        foreach ([...$this->fallbackChain($requested), $document->default_locale] as $candidate) {
            $translation = $translations->get($candidate);

            if ($translation instanceof PolicyTranslation) {
                return $this->serveTranslation($document, $published, $translation, $requested);
            }
        }

        return null;
    }

    private function serveTranslation(
        PolicyDocument $document,
        PolicyVersion $published,
        PolicyTranslation $translation,
        string $requestedLocale,
    ): PolicyContent {
        $html = $this->placeholders->substitute($translation->compiled_html);
        $title = $this->placeholders->substitute($translation->title);

        return new PolicyContent(
            slug: $document->slug,
            type: $document->type,
            locale: $translation->locale,
            requestedLocale: $requestedLocale,
            title: $title->text,
            html: $html->text,
            meta: $translation->meta ?? [],
            version: $published->version,
            unresolvedPlaceholders: array_values(array_unique([...$html->unresolved, ...$title->unresolved])),
        );
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
