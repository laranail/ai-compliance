<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Listeners;

use Illuminate\Database\Eloquent\Model;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Events\ConsentRecorded;
use Simtabi\Laranail\AiCompliance\Events\ConsentWithdrawn;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;

/**
 * Mirrors every consent event into the activity log, keyed by the record's
 * public id so exports can cross-reference without exposing sequences.
 */
final readonly class RecordConsentActivity
{
    public function __construct(
        private ActivityRecorder $activity,
    ) {}

    public function handle(ConsentRecorded|ConsentWithdrawn $event): void
    {
        $record = $event->record;

        $this->activity->record(
            type: ActivityType::ConsentChange,
            subject: $this->subjectOf($record),
            context: [
                'consent_record' => $record->public_id,
                'consent_type' => $record->type()->value('slug'),
                'status' => $record->status->value,
                'source' => $record->source,
                'policy_version' => $record->policy_version,
            ],
        );
    }

    private function subjectOf(ConsentRecord $record): Model|string|null
    {
        if ($record->guest_key !== null) {
            return $record->guest_key;
        }

        /** @var Model|null $subject */
        $subject = $record->subjectable;

        return $subject;
    }
}
