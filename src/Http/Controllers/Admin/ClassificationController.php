<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\AiCompliance\Checklist\Classification;

/**
 * The section-2 intake: answers switch checklist sections on or off and are
 * themselves evidence.
 */
final class ClassificationController
{
    public function index(Classification $classification): JsonResponse
    {
        return new JsonResponse(['data' => $classification->answers()]);
    }

    public function store(Request $request, Classification $classification): JsonResponse
    {
        /** @var array{answers: array<string, string>} $validated */
        $validated = $request->validate([
            'answers'   => ['required', 'array'],
            'answers.*' => ['string', 'max:255'],
        ]);

        $answeredBy = $request->user()?->getAuthIdentifier();

        $classification->record($validated['answers'], $answeredBy !== null ? (string) $answeredBy : 'admin');

        return new JsonResponse(['data' => $classification->answers()]);
    }
}
