<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyContent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves one compiled policy document, resolved through the locale fallback
 * chain, with placeholders substituted at serve time.
 */
final class PolicyController
{
    public function show(Request $request, PolicyRepository $policies, string $slug): JsonResponse
    {
        $locale = $request->query('locale');
        $document = $policies->find($slug, is_string($locale) ? $locale : null);

        if (! $document instanceof PolicyContent) {
            throw new NotFoundHttpException(sprintf('policy document [%s] not found', $slug));
        }

        return new JsonResponse([
            'slug' => $document->slug,
            'type' => $document->type->value,
            'locale' => $document->locale,
            'requested_locale' => $document->requestedLocale,
            'fallback' => $document->isFallback(),
            'title' => $document->title,
            'html' => $document->html,
            'meta' => $document->meta,
            'version' => $document->version,
            'unresolved_placeholders' => $document->unresolvedPlaceholders,
        ]);
    }
}
