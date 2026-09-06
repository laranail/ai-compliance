<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Models\PolicyTranslation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Read side of the policy editing api: the document list and one document's
 * full version history with translation content.
 */
final class PolicyDocumentController
{
    public function index(): JsonResponse
    {
        $documents = PolicyDocument::query()
            ->forDefaultTenant()
            ->with(['publishedVersion', 'draftVersion'])
            ->orderBy('slug')
            ->get()
            ->map(fn (PolicyDocument $document): array => [
                'slug'              => $document->slug,
                'type'              => $document->type->value,
                'surface'           => $document->surface,
                'consent_type_slug' => $document->consent_type_slug,
                'default_locale'    => $document->default_locale,
                'active'            => $document->active,
                'published_version' => $document->publishedVersion?->version,
                'draft_version'     => $document->draftVersion?->version,
            ]);

        return new JsonResponse(['data' => $documents]);
    }

    public function show(string $slug): JsonResponse
    {
        $document = $this->documentOrFail($slug);

        $versions = $document->versions()
            ->with('translations')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PolicyVersion $version): array => [
                'version'       => $version->version,
                'status'        => $version->status->value,
                'effective_at'  => $version->effective_at?->toIso8601String(),
                'published_at'  => $version->published_at?->toIso8601String(),
                'superseded_at' => $version->superseded_at?->toIso8601String(),
                'translations'  => $version->translations->map(fn (PolicyTranslation $translation): array => [
                    'locale'          => $translation->locale,
                    'title'           => $translation->title,
                    'source_markdown' => $translation->source_markdown,
                    'checksum'        => $translation->checksum,
                    'hand_edited'     => $translation->isHandEdited(),
                ])->values(),
            ]);

        return new JsonResponse([
            'data' => [
                'slug'           => $document->slug,
                'type'           => $document->type->value,
                'default_locale' => $document->default_locale,
                'active'         => $document->active,
                'versions'       => $versions,
            ],
        ]);
    }

    private function documentOrFail(string $slug): PolicyDocument
    {
        $document = PolicyDocument::query()
            ->forDefaultTenant()
            ->where('slug', $slug)
            ->first();

        if (! $document instanceof PolicyDocument) {
            throw new NotFoundHttpException(sprintf('policy document [%s] not found', $slug));
        }

        return $document;
    }
}
