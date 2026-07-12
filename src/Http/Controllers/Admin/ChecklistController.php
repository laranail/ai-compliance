<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
use Simtabi\Laranail\AiCompliance\Checks\CheckRunner;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Enums\CheckStatus;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The checklist surface: the item list, manual evidence submission (FR-3),
 * and running the automated checks on demand (FR-2).
 */
final class ChecklistController
{
    public function index(): JsonResponse
    {
        $items = ChecklistItem::query()
            ->forDefaultTenant()
            ->orderBy('id')
            ->get()
            ->map(fn (ChecklistItem $item): array => [
                'key' => $item->key,
                'section' => $item->section,
                'label' => $item->label,
                'description' => $item->description,
                'status' => $item->status->value,
                'evidence_type' => $item->evidence_type,
                'evidence_ref' => $item->evidence_ref,
                'last_verified_at' => $item->last_verified_at?->toIso8601String(),
                'verified_by' => $item->verified_by,
                'staleness_months' => $item->staleness_months,
                'stale' => $item->isStale(),
            ]);

        return new JsonResponse(['data' => $items]);
    }

    public function verify(Request $request, ActivityRecorder $activity, string $key): JsonResponse
    {
        /** @var array{evidence_ref: string} $validated */
        $validated = $request->validate([
            'evidence_ref' => ['required', 'string', 'max:2000'],
        ]);

        $item = ChecklistItem::query()
            ->forDefaultTenant()
            ->where('key', $key)
            ->first();

        if (! $item instanceof ChecklistItem) {
            throw new NotFoundHttpException(sprintf('checklist item [%s] not found', $key));
        }

        if ($item->evidence_type !== 'manual') {
            throw new ConflictHttpException(sprintf('checklist item [%s] is verified automatically; run the checks instead', $key));
        }

        $verifiedBy = $request->user()?->getAuthIdentifier();

        $item->update([
            'status' => CheckStatus::Ok,
            'evidence_ref' => $validated['evidence_ref'],
            'last_verified_at' => now(),
            'verified_by' => $verifiedBy !== null ? (string) $verifiedBy : 'admin',
        ]);

        $activity->record(ActivityType::SettingChange, context: [
            'setting' => 'checklist_evidence',
            'item' => $key,
        ]);

        return new JsonResponse(['data' => [
            'key' => $item->key,
            'status' => $item->status->value,
            'last_verified_at' => $item->last_verified_at?->toIso8601String(),
        ]]);
    }

    public function run(CheckRunner $runner): JsonResponse
    {
        return new JsonResponse(['data' => $runner->run()]);
    }
}
