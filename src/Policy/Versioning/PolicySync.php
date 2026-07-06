<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\Versioning;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;
use Simtabi\Laranail\AiCompliance\Events\PoliciesSynced;
use Simtabi\Laranail\AiCompliance\Events\PolicyDraftCreated;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Models\PolicyTranslation;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Policy\PolicyCompiler;
use Simtabi\Laranail\AiCompliance\Policy\PolicyFileLoader;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;

/**
 * Imports policy markdown files into the versioned database layer. The
 * checksum rules keep the two sides honest:
 *
 * - a document seen for the first time imports as published version 1.0
 *   (the shipped default going live, which is what "ready-to-use" means);
 * - a changed file whose database copy was never hand-edited becomes (or
 *   updates) a draft — publishing stays an explicit human action;
 * - a changed file whose database copy WAS hand-edited is flagged and never
 *   overwritten — the admin's text wins until a human reconciles.
 *
 * The default locale is synced first so translations can anchor their
 * origin_checksum to the source they were made from.
 */
final class PolicySync
{
    /** @var array<int, PolicyVersion> drafts created during this run, per document id */
    private array $draftsThisRun = [];

    public function __construct(
        private readonly PolicyFileLoader $files,
        private readonly PolicyCompiler $compiler,
        private readonly ConfigRepository $config,
        private readonly Dispatcher $events,
    ) {}

    public function sync(): SyncResult
    {
        $this->draftsThisRun = [];
        $result = new SyncResult;
        $defaultLocale = $this->defaultLocale();

        foreach ($this->localesDefaultFirst($defaultLocale) as $locale) {
            foreach ($this->files->all($locale) as $file) {
                $this->syncFile($file, $defaultLocale, $result);
            }
        }

        $this->events->dispatch(new PoliciesSynced($result));

        return $result;
    }

    private function syncFile(PolicyFile $file, string $defaultLocale, SyncResult $result): void
    {
        $document = $this->documentFor($file, $defaultLocale);

        /** @var PolicyVersion|null $latest */
        $latest = $document->versions()->latest('id')->first();

        if ($latest === null) {
            $version = $document->versions()->create([
                'version' => VersionNumber::first(),
                'status' => PolicyVersionStatus::Published,
                'effective_at' => now(),
                'published_at' => now(),
            ]);

            $this->writeTranslation($version, $file, $defaultLocale);
            $result->recordImported($file->slug, $file->locale);

            return;
        }

        /** @var PolicyTranslation|null $translation */
        $translation = $latest->translations()->where('locale', $file->locale)->first();

        if ($translation === null) {
            // a locale arriving after first import lands in a draft, never
            // directly in the published version
            $draft = $latest->isDraft() ? $latest : $this->draftFrom($document, $latest);
            $this->writeTranslation($draft, $file, $defaultLocale);
            $result->recordImported($file->slug, $file->locale);

            return;
        }

        if ($translation->file_checksum === $file->checksum) {
            $result->recordUnchanged($file->slug, $file->locale);

            return;
        }

        if ($translation->file_checksum === null || $translation->isHandEdited()) {
            // authored or edited in-app; the file changing underneath never
            // overwrites a human's text
            $result->recordFlagged($file->slug, $file->locale);

            return;
        }

        $draft = $latest->isDraft() ? $latest : $this->draftFrom($document, $latest);
        $this->writeTranslation($draft, $file, $defaultLocale);
        $result->recordDrafted($file->slug, $file->locale);
    }

    private function documentFor(PolicyFile $file, string $defaultLocale): PolicyDocument
    {
        /** @var PolicyDocument|null $document */
        $document = PolicyDocument::query()
            ->forDefaultTenant()
            ->where('slug', $file->slug)
            ->first();

        if ($document instanceof PolicyDocument) {
            return $document;
        }

        return PolicyDocument::query()->create([
            'tenant_id' => '',
            'slug' => $file->slug,
            'type' => $file->type,
            'surface' => Str::startsWith($file->slug, 'disclosure.') ? Str::after($file->slug, 'disclosure.') : null,
            'consent_type_slug' => Str::startsWith($file->slug, 'consent.') ? Str::after($file->slug, 'consent.') : null,
            'source_path' => $file->relativePath,
            'default_locale' => $defaultLocale,
            'active' => true,
        ]);
    }

    /**
     * Create (or reuse) the document's draft for this run: the next version
     * number, all translations copied from the version it supersedes so a
     * partial file change never drops locales.
     */
    private function draftFrom(PolicyDocument $document, PolicyVersion $from): PolicyVersion
    {
        if (isset($this->draftsThisRun[$document->id])) {
            return $this->draftsThisRun[$document->id];
        }

        /** @var PolicyVersion $draft */
        $draft = $document->versions()->create([
            'version' => VersionNumber::next($from->version),
            'status' => PolicyVersionStatus::Draft,
        ]);

        foreach ($from->translations as $translation) {
            $draft->translations()->create([
                'locale' => $translation->locale,
                'title' => $translation->title,
                'source_markdown' => $translation->source_markdown,
                'compiled_html' => $translation->compiled_html,
                'meta' => $translation->meta,
                'checksum' => $translation->checksum,
                'file_checksum' => $translation->file_checksum,
                'origin_checksum' => $translation->origin_checksum,
            ]);
        }

        $this->draftsThisRun[$document->id] = $draft;
        $this->events->dispatch(new PolicyDraftCreated($draft));

        return $draft;
    }

    private function writeTranslation(PolicyVersion $version, PolicyFile $file, string $defaultLocale): void
    {
        $compiled = $this->compiler->compile($file);

        $originChecksum = null;

        if ($file->locale !== $defaultLocale) {
            /** @var PolicyTranslation|null $defaultTranslation */
            $defaultTranslation = $version->translations()->where('locale', $defaultLocale)->first();
            $originChecksum = $defaultTranslation?->checksum;
        }

        $version->translations()->updateOrCreate(
            ['locale' => $file->locale],
            [
                'title' => $compiled->title() ?? Str::headline(Str::afterLast($file->slug, '.')),
                'source_markdown' => $file->contents,
                'compiled_html' => $compiled->html,
                'meta' => $compiled->meta,
                'checksum' => $file->checksum,
                'file_checksum' => $file->checksum,
                'origin_checksum' => $originChecksum,
            ],
        );
    }

    /**
     * @return list<string>
     */
    private function localesDefaultFirst(string $defaultLocale): array
    {
        $locales = $this->files->locales();

        usort($locales, static fn (string $a, string $b): int => match (true) {
            $a === $defaultLocale => -1,
            $b === $defaultLocale => 1,
            default => strcmp($a, $b),
        });

        return $locales;
    }

    private function defaultLocale(): string
    {
        $default = $this->config->get('laranail.ai-compliance.locales.default', 'en');

        return is_string($default) ? $default : 'en';
    }
}
