<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyDrafts;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyPublisher;

/**
 * Write side of the policy editing api, a thin http layer over the
 * PolicyDrafts service the filament editor shares: one open draft per
 * document, edits recompiled on save, publishing atomic.
 */
final readonly class PolicyDraftController
{
    public function __construct(
        private PolicyDrafts $drafts,
        private PolicyPublisher $publisher,
    ) {}

    public function store(string $slug): JsonResponse
    {
        $document = $this->documentOrFail($slug);

        $existed = $document->draftVersion()->exists();
        $draft = $this->drafts->openDraft($document);

        return new JsonResponse(['data' => $this->presentVersion($draft)], $existed ? 200 : 201);
    }

    public function updateTranslation(Request $request, string $slug, string $locale): JsonResponse
    {
        $document = $this->documentOrFail($slug);
        $draft = $this->draftOrFail($document);

        /** @var array{source_markdown: string} $validated */
        $validated = $request->validate([
            'source_markdown' => ['required', 'string'],
        ]);

        $translation = $this->drafts->updateTranslation($draft, $locale, $validated['source_markdown']);

        return new JsonResponse([
            'data' => [
                'locale'        => $translation->locale,
                'title'         => $translation->title,
                'checksum'      => $translation->checksum,
                'compiled_html' => $translation->compiled_html,
                'hand_edited'   => $translation->isHandEdited(),
            ],
        ]);
    }

    public function publish(Request $request, string $slug): JsonResponse
    {
        $document = $this->documentOrFail($slug);
        $draft = $this->draftOrFail($document);

        $published = $this->publisher->publish($draft, $request->user());

        return new JsonResponse(['data' => $this->presentVersion($published)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentVersion(PolicyVersion $version): array
    {
        return [
            'version'      => $version->version,
            'status'       => $version->status->value,
            'published_at' => $version->published_at?->toIso8601String(),
            'locales'      => $version->translations()->pluck('locale')->all(),
        ];
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

    private function draftOrFail(PolicyDocument $document): PolicyVersion
    {
        $draft = $document->draftVersion()->first();

        if (! $draft instanceof PolicyVersion) {
            throw new ConflictHttpException(sprintf('policy document [%s] has no open draft', $document->slug));
        }

        return $draft;
    }
}
