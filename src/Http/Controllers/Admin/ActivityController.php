<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Simtabi\Laranail\AiCompliance\Activity\ActivityChain;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;

/**
 * The activity log read surface: filterable, paginated, public ids only —
 * and reading it is itself logged (spec FR-10), once per request.
 */
final class ActivityController
{
    public function index(Request $request, ActivityRecorder $recorder): JsonResponse
    {
        /** @var array{type?: string, from?: string, to?: string} $filters */
        $filters = $request->validate([
            'type' => ['sometimes', 'string'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $query = ActivityEvent::query()->orderByDesc('recorded_at')->orderByDesc('id');

        if (isset($filters['type'])) {
            $query->where('event_type', $filters['type']);
        }

        if (isset($filters['from'])) {
            $query->where('recorded_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('recorded_at', '<=', $filters['to']);
        }

        $page = $query->paginate(50);

        $reader = $request->user()?->getAuthIdentifier();

        $recorder->record(ActivityType::LogRead, context: [
            'log' => 'activity',
            'reader' => $reader !== null ? (string) $reader : 'unknown',
            'filters' => $filters,
        ]);

        return new JsonResponse([
            'data' => collect($page->items())->map(static fn (ActivityEvent $event): array => [
                'id' => $event->public_id,
                'event_type' => $event->event_type->value,
                'subject' => $event->subjectable_type !== null
                    ? $event->subjectable_type . '#' . $event->subjectable_id
                    : null,
                'provider_id' => $event->provider_id,
                'context' => $event->context,
                'chained' => $event->hash_prev !== null,
                'recorded_at' => $event->recorded_at->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function chain(ActivityChain $chain): JsonResponse
    {
        return new JsonResponse(['data' => $chain->verify() + ['enabled' => $chain->enabled()]]);
    }
}
