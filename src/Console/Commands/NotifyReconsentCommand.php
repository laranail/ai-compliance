<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Console\Commands;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Simtabi\Laranail\Package\Tools\Commands\Command;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Models\PolicyVersion;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;
use Simtabi\Laranail\AiCompliance\Notifications\ReconsentRequested;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;

/**
 * Notifies exactly the subjects whose current granted consent references a
 * superseded policy version — everyone else's consent stands. Guests are
 * unreachable by mail; the boot payload's reconsent flag prompts them on
 * their next visit. Run it after publishing a new consent-text version;
 * re-running notifies whoever is still affected.
 */
final class NotifyReconsentCommand extends Command
{
    protected $signature = 'laranail::ai-compliance.notify-reconsent
                            {--dry-run : list affected subjects without notifying}';

    protected $description = 'Notify subjects whose granted consent references a superseded policy version';

    public function handle(ConsentManager $consent, NotificationDispatcher $notifications): int
    {
        $affected = $this->affectedSubjects($consent);

        $this->components->twoColumnDetail('Users needing re-consent', (string) count($affected['users']));
        $this->components->twoColumnDetail('Guests needing re-consent (prompted at next visit)', (string) $affected['guests']);

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        foreach ($affected['users'] as $entry) {
            $notifications->send($entry['subject'], new ReconsentRequested($entry['types']));
        }

        $this->components->info(sprintf('%d notifications queued', count($affected['users'])));

        return self::SUCCESS;
    }

    /**
     * @return array{users: list<array{subject: Model, types: list<string>}>, guests: int}
     */
    private function affectedSubjects(ConsentManager $consent): array
    {
        $supersededVersionIds = PolicyVersion::query()
            ->where('status', PolicyVersionStatus::Superseded->value)
            ->pluck('id');

        // candidates: granted rows referencing superseded versions; whether
        // they are the CURRENT state is confirmed per subject below
        $candidates = ConsentRecord::query()
            ->whereIn('policy_version_id', $supersededVersionIds)
            ->where('status', ConsentStatus::Granted->value)
            ->get();

        $users = [];
        $guests = [];

        foreach ($candidates as $record) {
            if ($record->guest_key !== null) {
                $needing = $consent->reconsentFor($record->guest_key);

                if ($needing !== []) {
                    $guests[$record->guest_key] = true;
                }

                continue;
            }

            if ($record->subjectable_type === null || $record->subjectable_id === null) {
                continue; // anonymized history
            }

            $key = $record->subjectable_type . '#' . $record->subjectable_id;

            if (isset($users[$key])) {
                continue;
            }

            $subject = $this->resolveSubject($record->subjectable_type, $record->subjectable_id);

            if (! $subject instanceof Model) {
                continue;
            }

            $needing = $consent->reconsentFor($subject);

            if ($needing !== []) {
                $users[$key] = ['subject' => $subject, 'types' => $needing];
            }
        }

        return ['users' => array_values($users), 'guests' => count($guests)];
    }

    private function resolveSubject(string $morphType, int|string $id): ?Model
    {
        $class = Relation::getMorphedModel($morphType) ?? $morphType;

        if (! is_string($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        return $class::query()->find($id);
    }
}
