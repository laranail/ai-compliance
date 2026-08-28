<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\Versioning;

use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Policy\PolicyCompiler;
use Simtabi\Laranail\AiCompliance\Models\PolicyTranslation;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;
use Simtabi\Laranail\AiCompliance\Events\PolicyDraftCreated;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;

/**
 * The draft-editing core shared by the http editing api and the filament
 * editor: one open draft per document, translations recompiled on every
 * save, checksums kept honest. The markdown is stored byte-for-byte as
 * given — editors must never normalize a legal text silently.
 */
final readonly class PolicyDrafts
{
    public function __construct(
        private PolicyCompiler $compiler,
        private Dispatcher $events,
    ) {}

    /**
     * The document's open draft, creating one from the latest version (all
     * translations copied) when none exists.
     */
    public function openDraft(PolicyDocument $document): PolicyVersion
    {
        $existing = $document->draftVersion()->first();

        if ($existing instanceof PolicyVersion) {
            return $existing;
        }

        $latest = $document->versions()->latest('id')->first();

        /** @var PolicyVersion $draft */
        $draft = $document->versions()->create([
            'version' => $latest instanceof PolicyVersion ? VersionNumber::next($latest->version) : VersionNumber::first(),
            'status'  => PolicyVersionStatus::Draft,
        ]);

        if ($latest instanceof PolicyVersion) {
            foreach ($latest->translations as $translation) {
                $draft->translations()->create([
                    'locale'          => $translation->locale,
                    'title'           => $translation->title,
                    'source_markdown' => $translation->source_markdown,
                    'compiled_html'   => $translation->compiled_html,
                    'meta'            => $translation->meta,
                    'checksum'        => $translation->checksum,
                    'file_checksum'   => $translation->file_checksum,
                    'origin_checksum' => $translation->origin_checksum,
                ]);
            }
        }

        $this->events->dispatch(new PolicyDraftCreated($draft));

        return $draft;
    }

    /**
     * Replace one locale's markdown on a draft, recompiling html and meta
     * and re-anchoring origin_checksum for non-default locales.
     */
    public function updateTranslation(PolicyVersion $draft, string $locale, string $markdown): PolicyTranslation
    {
        /** @var PolicyDocument $document */
        $document = $draft->document()->firstOrFail();

        $compiled = $this->compiler->compile(new PolicyFile(
            slug: $document->slug,
            locale: $locale,
            type: $document->type,
            relativePath: (string) $document->source_path,
            absolutePath: '',
            contents: $markdown,
            checksum: hash('sha256', $markdown),
        ));

        $originChecksum = null;

        if ($locale !== $document->default_locale) {
            /** @var PolicyTranslation|null $defaultTranslation */
            $defaultTranslation = $draft->translations()->where('locale', $document->default_locale)->first();
            $originChecksum = $defaultTranslation?->checksum;
        }

        /** @var PolicyTranslation $translation */
        $translation = $draft->translations()->updateOrCreate(
            ['locale' => $locale],
            array_filter([
                'title'           => $compiled->title(),
                'source_markdown' => $markdown,
                'compiled_html'   => $compiled->html,
                'meta'            => $compiled->meta,
                'checksum'        => hash('sha256', $markdown),
                'origin_checksum' => $originChecksum,
            ], static fn (mixed $value): bool => $value !== null),
        );

        return $translation;
    }
}
