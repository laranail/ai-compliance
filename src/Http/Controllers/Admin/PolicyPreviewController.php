<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Simtabi\Laranail\AiCompliance\Enums\PolicyType;
use Simtabi\Laranail\AiCompliance\Policy\PlaceholderRegistry;
use Simtabi\Laranail\AiCompliance\Policy\PolicyCompiler;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;

/**
 * Compiles markdown without saving anything — the live preview behind the
 * editors. Returns the substituted html (what a user would see) plus the
 * placeholders that remained unresolved.
 */
final class PolicyPreviewController
{
    public function __invoke(
        Request $request,
        PolicyCompiler $compiler,
        PlaceholderRegistry $placeholders,
    ): JsonResponse {
        /** @var array{source_markdown: string} $validated */
        $validated = $request->validate([
            'source_markdown' => ['required', 'string'],
        ]);

        $markdown = $validated['source_markdown'];

        $compiled = $compiler->compile(new PolicyFile(
            slug: 'preview',
            locale: (string) config('app.locale', 'en'),
            type: PolicyType::Policy,
            relativePath: 'preview.md',
            absolutePath: '',
            contents: $markdown,
            checksum: hash('sha256', $markdown),
        ));

        $html = $placeholders->substitute($compiled->html);

        return new JsonResponse([
            'data' => [
                'title' => $compiled->title(),
                'html' => $html->text,
                'meta' => $compiled->meta,
                'unresolved_placeholders' => $html->unresolved,
            ],
        ]);
    }
}
