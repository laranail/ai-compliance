<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\Versioning;

use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Models\PolicyTranslation;
use Simtabi\Laranail\AiCompliance\Policy\PolicyFileLoader;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;

/**
 * Computes the two staleness signals on demand, both cheap checksum
 * comparisons against the latest version of each document:
 *
 * - file_drift: the shipped/published file changed after this translation
 *   was imported (`file_checksum` no longer matches the file). When the
 *   database copy was also hand-edited, a human must reconcile.
 * - translation_drift: the default-locale source changed after this
 *   translation was made from it (`origin_checksum` no longer matches the
 *   default translation's checksum) — the locale needs re-translation.
 */
final readonly class PolicyStaleness
{
    public function __construct(
        private PolicyFileLoader $files,
    ) {}

    /**
     * @return list<array{slug: string, locale: string, signal: string, hand_edited: bool, version: string}>
     */
    public function report(): array
    {
        $entries = [];

        $documents = PolicyDocument::query()
            ->forDefaultTenant()
            ->with('latestVersion.translations')
            ->get();

        foreach ($documents as $document) {
            $latest = $document->latestVersion;

            if ($latest === null) {
                continue;
            }

            $translations = $latest->translations->keyBy('locale');
            /** @var PolicyTranslation|null $default */
            $default = $translations->get($document->default_locale);

            foreach ($translations as $locale => $translation) {
                $file = $this->files->find($document->slug, (string) $locale);

                if ($file instanceof PolicyFile
                    && $translation->file_checksum !== null
                    && $file->checksum !== $translation->file_checksum) {
                    $entries[] = [
                        'slug' => $document->slug,
                        'locale' => (string) $locale,
                        'signal' => 'file_drift',
                        'hand_edited' => $translation->isHandEdited(),
                        'version' => $latest->version,
                    ];
                }

                if ($default instanceof PolicyTranslation
                    && $locale !== $document->default_locale
                    && $translation->origin_checksum !== null
                    && $translation->origin_checksum !== $default->checksum) {
                    $entries[] = [
                        'slug' => $document->slug,
                        'locale' => (string) $locale,
                        'signal' => 'translation_drift',
                        'hand_edited' => $translation->isHandEdited(),
                        'version' => $latest->version,
                    ];
                }
            }
        }

        return $entries;
    }
}
