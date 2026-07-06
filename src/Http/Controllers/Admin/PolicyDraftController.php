<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;
use Simtabi\Laranail\AiCompliance\Events\PolicyDraftCreated;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Models\PolicyTranslation;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Policy\PolicyCompiler;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyPublisher;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\VersionNumber;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Write side of the policy editing api. A document has at most one open
 * draft; store() creates or returns it, updateTranslation() edits one
 * locale's markdown (recompiling on save), publish() promotes it and
 * supersedes the current published version atomically.
 */
final readonly class PolicyDraftController
{
    public function __construct(
        private PolicyCompiler $compiler,
        private PolicyPublisher $publisher,
        private Dispatcher $events,
    ) {}

    public function store(string $slug): JsonResponse
    {
        $document = $this->documentOrFail($slug);

        $existing = $document->draftVersion()->first();

        if ($existing instanceof PolicyVersion) {
            return new JsonResponse(['data' => $this->presentVersion($existing)]);
        }

        $draft = $this->createDraft($document);

        return new JsonResponse(['data' => $this->presentVersion($draft)], 201);
    }

    public function updateTranslation(Request $request, string $slug, string $locale): JsonResponse
    {
        $document = $this->documentOrFail($slug);
        $draft = $this->draftOrFail($document);

        /** @var array{source_markdown: string} $validated */
        $validated = $request->validate([
            'source_markdown' => ['required', 'string'],
        ]);

        $markdown = $validated['source_markdown'];

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
                'title' => $compiled->title(),
                'source_markdown' => $markdown,
                'compiled_html' => $compiled->html,
                'meta' => $compiled->meta,
                'checksum' => hash('sha256', $markdown),
                'origin_checksum' => $originChecksum,
            ], static fn (mixed $value): bool => $value !== null),
        );

        return new JsonResponse([
            'data' => [
                'locale' => $translation->locale,
                'title' => $translation->title,
                'checksum' => $translation->checksum,
                'compiled_html' => $translation->compiled_html,
                'hand_edited' => $translation->isHandEdited(),
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

    private function createDraft(PolicyDocument $document): PolicyVersion
    {
        $latest = $document->versions()->latest('id')->first();

        /** @var PolicyVersion $draft */
        $draft = $document->versions()->create([
            'version' => $latest instanceof PolicyVersion ? VersionNumber::next($latest->version) : VersionNumber::first(),
            'status' => PolicyVersionStatus::Draft,
        ]);

        if ($latest instanceof PolicyVersion) {
            foreach ($latest->translations as $translation) {
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
        }

        $this->events->dispatch(new PolicyDraftCreated($draft));

        return $draft;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentVersion(PolicyVersion $version): array
    {
        return [
            'version' => $version->version,
            'status' => $version->status->value,
            'published_at' => $version->published_at?->toIso8601String(),
            'locales' => $version->translations()->pluck('locale')->all(),
        ];
    }

    private function documentOrFail(string $slug): PolicyDocument
    {
        $document = PolicyDocument::query()
            ->whereNull('tenant_id')
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
