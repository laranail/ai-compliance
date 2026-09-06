<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Console\Commands;

use Simtabi\Laranail\Package\Tools\Commands\Command;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;

/**
 * Applies the configured retention. Activity events prune by schedule-able
 * command; consent records are a legal decision, so pruning them requires
 * the explicit --consents flag AND a configured policy, and even then only
 * superseded history is removed — the current state per (subject, type) is
 * always kept. Every prune writes its own activity event (spec FR-9).
 */
final class PruneCommand extends Command
{
    protected $signature = 'laranail::ai-compliance.prune
                            {--consents : also prune superseded consent history per the configured policy}';

    protected $description = 'Prune the compliance stores per the configured retention';

    public function handle(ActivityRecorder $activity): int
    {
        $prunedEvents = $this->pruneActivityEvents();
        $this->components->twoColumnDetail('Activity events pruned', (string) $prunedEvents);

        $prunedConsents = 0;

        if ($this->option('consents')) {
            $days = config('laranail.ai-compliance.retention.consent_records');

            if (! is_int($days) || $days <= 0) {
                $this->components->error('set laranail.ai-compliance.retention.consent_records (days) before pruning consent history');

                return self::FAILURE;
            }

            $prunedConsents = $this->pruneConsentHistory($days);
            $this->components->twoColumnDetail('Superseded consent rows pruned', (string) $prunedConsents);
        }

        $activity->record(ActivityType::SettingChange, context: [
            'setting'         => 'retention',
            'action'          => 'pruned',
            'activity_events' => $prunedEvents,
            'consent_records' => $prunedConsents,
        ]);

        return self::SUCCESS;
    }

    private function pruneActivityEvents(): int
    {
        $days = config('laranail.ai-compliance.retention.activity_events');

        if (! is_int($days) || $days <= 0) {
            return 0;
        }

        return ActivityEvent::query()
            ->where('recorded_at', '<', now()->subDays($days))
            ->toBase()
            ->delete();
    }

    /**
     * Deletes expired consent rows that a newer row has superseded; the
     * latest row per (subject, type) always survives, whatever its age.
     * Deliberately a query-builder delete: the eloquent guards protect
     * application code, not this explicit, configured maintenance.
     */
    private function pruneConsentHistory(int $days): int
    {
        $pruned = 0;

        $expired = ConsentRecord::query()
            ->where('recorded_at', '<', now()->subDays($days))
            ->orderBy('id')
            ->get();

        foreach ($expired as $record) {
            $newer = ConsentRecord::query()
                ->where('consent_type_id', $record->consent_type_id)
                ->where('id', '>', $record->id)
                ->when(
                    $record->guest_key !== null,
                    static fn ($query) => $query->where('guest_key', $record->guest_key),
                    static fn ($query) => $query
                        ->where('subjectable_type', $record->subjectable_type)
                        ->where('subjectable_id', $record->subjectable_id),
                )
                ->exists();

            if ($newer) {
                $pruned += ConsentRecord::query()->whereKey($record->id)->toBase()->delete();
            }
        }

        return $pruned;
    }
}
