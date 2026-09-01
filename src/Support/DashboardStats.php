<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Support;

use Illuminate\Database\QueryException;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Models\ConsentType;
use Simtabi\Laranail\AiCompliance\Models\Provider;

/**
 * The dashboard numbers (spec FR-1), shared by the admin json endpoint and
 * the filament widgets. Consent counts reflect the CURRENT state per
 * (subject, type) — the append-only log is history, the dashboard is now.
 */
final class DashboardStats
{
    /**
     * @return array{consents: array{granted: int, denied: int, by_type: array<string, array{granted: int, denied: int}>}, providers: int, activity_events: int, checklist: array<string, int>}
     */
    public function tiles(): array
    {
        return [
            'consents' => $this->consentTiles(),
            'providers' => $this->count(static fn (): int => Provider::query()->count()),
            'activity_events' => $this->count(static fn (): int => ActivityEvent::query()->count()),
            'checklist' => $this->checklistSummary(),
        ];
    }

    /**
     * @return array{granted: int, denied: int, by_type: array<string, array{granted: int, denied: int}>}
     */
    private function consentTiles(): array
    {
        $tiles = ['granted' => 0, 'denied' => 0, 'by_type' => []];

        try {
            $slugs = ConsentType::query()->pluck('slug', 'id');

            $records = ConsentRecord::query()
                ->orderByDesc('recorded_at')
                ->orderByDesc('id')
                ->get();
        } catch (QueryException) {
            return $tiles;
        }

        $seen = [];

        foreach ($records as $record) {
            $subject = $record->guest_key ?? ($record->subjectable_type.'#'.$record->subjectable_id);
            $slug = $slugs->get($record->consent_type_id);

            if (! is_string($slug) || $subject === '#') {
                continue; // anonymized rows carry no current state
            }

            $key = $subject.'|'.$slug;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            $bucket = $record->status === ConsentStatus::Granted ? 'granted' : 'denied';
            $tiles[$bucket]++;
            $tiles['by_type'][$slug] ??= ['granted' => 0, 'denied' => 0];
            $tiles['by_type'][$slug][$bucket]++;
        }

        return $tiles;
    }

    /**
     * @return array<string, int>
     */
    private function checklistSummary(): array
    {
        $summary = ['ok' => 0, 'review' => 0, 'fail' => 0, 'na' => 0];

        try {
            $counts = ChecklistItem::query()
                ->forDefaultTenant()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        } catch (QueryException) {
            return $summary;
        }

        foreach ($counts as $status => $total) {
            if (is_string($status) && array_key_exists($status, $summary)) {
                $summary[$status] = (int) $total;
            }
        }

        return $summary;
    }

    /**
     * @param  callable(): int  $count
     */
    private function count(callable $count): int
    {
        try {
            return $count();
        } catch (QueryException) {
            return 0;
        }
    }
}
