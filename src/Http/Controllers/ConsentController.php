<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\AiCompliance\Consent\GuestKeys;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;
use Simtabi\Laranail\AiCompliance\Consent\ConsentTypes;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;

/**
 * The consumer write endpoint: record one consent decision for the current
 * subject (the authenticated user, or the guest key — issued here when
 * absent). Returns the record's public id and the refreshed state map.
 */
final class ConsentController
{
    public function store(
        Request $request,
        ConsentManager $consent,
        ConsentTypes $types,
        GuestKeys $guestKeys,
    ): JsonResponse|RedirectResponse {
        /** @var array{type: string, status: string} $validated */
        $validated = $request->validate([
            'type'   => ['required', 'string', Rule::in($types->slugs())],
            'status' => ['required', 'string', Rule::in(['granted', 'denied', 'withdrawn'])],
        ]);

        $subject = $request->user() ?? $guestKeys->issue($request);

        $record = $consent->record(
            $subject,
            $validated['type'],
            ConsentStatus::from($validated['status']),
            'api',
        );

        // the no-javascript preferences panel posts a plain form
        if (! $request->expectsJson()) {
            return back()->with('ai-compliance.saved', true);
        }

        return new JsonResponse([
            'data' => [
                'id'             => $record->public_id,
                'type'           => $validated['type'],
                'status'         => $record->status->value,
                'policy_version' => $record->policy_version,
                'recorded_at'    => $record->recorded_at->toIso8601String(),
            ],
            'state'     => $consent->stateFor($subject),
            'reconsent' => $consent->reconsentFor($subject),
        ], 201);
    }
}
