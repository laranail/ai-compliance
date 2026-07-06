<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Consent;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;
use Simtabi\Laranail\AiCompliance\Events\ConsentRecorded;
use Simtabi\Laranail\AiCompliance\Events\ConsentWithdrawn;
use Simtabi\Laranail\AiCompliance\Features\FeatureGate;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Models\ConsentType;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Providers\PendingProviderCall;
use Simtabi\Laranail\AiCompliance\Providers\ProviderCalls;

/**
 * The consent api application code talks to instead of reading rows.
 * Subjects are eloquent users (Model) or guest keys (string). Writes are
 * append-only rows stamped with the policy version the subject was shown;
 * the current state is the latest row per (subject, type), falling back to
 * each type's configured default. On an unmigrated database every read
 * degrades to the configured defaults instead of erroring.
 */
final readonly class ConsentManager
{
    public function __construct(
        private ConsentTypes $types,
        private ConfigRepository $config,
        private Dispatcher $events,
        private ActivityRecorder $activity,
        private FeatureGate $features,
    ) {}

    public function grant(Model|Authenticatable|string $subject, string $type, string $source = 'app'): ConsentRecord
    {
        return $this->record($subject, $type, ConsentStatus::Granted, $source);
    }

    public function deny(Model|Authenticatable|string $subject, string $type, string $source = 'app'): ConsentRecord
    {
        return $this->record($subject, $type, ConsentStatus::Denied, $source);
    }

    public function withdraw(Model|Authenticatable|string $subject, string $type, string $source = 'app'): ConsentRecord
    {
        return $this->record($subject, $type, ConsentStatus::Withdrawn, $source);
    }

    public function record(
        Model|Authenticatable|string $subject,
        string $type,
        ConsentStatus $status,
        string $source = 'app',
    ): ConsentRecord {
        $typeModel = $this->types->resolve($type);
        [$versionId, $versionString] = $this->publishedPolicyVersionFor($type);

        $record = $this->write($subject, $typeModel, $status, $source, $versionId, $versionString);

        $this->events->dispatch($status === ConsentStatus::Withdrawn
            ? new ConsentWithdrawn($record)
            : new ConsentRecorded($record));

        return $record;
    }

    public function granted(Model|Authenticatable|string $subject, string $type): bool
    {
        $current = $this->currentRecord($subject, $type);

        if ($current instanceof ConsentRecord) {
            return $current->status === ConsentStatus::Granted;
        }

        return $this->types->defaultStateFor($type) === ConsentStatus::Granted;
    }

    /**
     * Whether a feature may run for this subject: the admin kill switch must
     * be on and every consent type the feature requires (config
     * laranail.ai-compliance.features) must be granted. Unconfigured
     * features are denied by default.
     */
    public function allows(Model|Authenticatable|string $subject, string $feature): bool
    {
        $features = $this->config->get('laranail.ai-compliance.features', []);
        $required = is_array($features) ? ($features[$feature] ?? null) : null;

        if (! is_array($required)) {
            return false;
        }

        if (! $this->features->enabled($feature)) {
            return false;
        }

        return array_all($required, fn (mixed $type): bool => is_string($type) && $this->granted($subject, $type));
    }

    /**
     * The full state map for the boot payload: per configured type, the
     * current status with its record metadata, or the default.
     *
     * @return array<string, array{status: string, recorded_at: string|null, policy_version: string|null}>
     */
    public function stateFor(Model|Authenticatable|string $subject): array
    {
        $current = $this->currentRecords($subject);
        $state = [];

        foreach ($this->types->slugs() as $slug) {
            $record = $current[$slug] ?? null;

            $state[$slug] = $record instanceof ConsentRecord
                ? [
                    'status' => $record->status->value,
                    'recorded_at' => $record->recorded_at->toIso8601String(),
                    'policy_version' => $record->policy_version,
                ]
                : [
                    'status' => $this->types->defaultStateFor($slug)->value,
                    'recorded_at' => null,
                    'policy_version' => null,
                ];
        }

        return $state;
    }

    /**
     * Consent types whose latest granted record references a superseded
     * policy version — the subject agreed to text that has since been
     * replaced and should be asked again.
     *
     * @return list<string>
     */
    public function reconsentFor(Model|Authenticatable|string $subject): array
    {
        $needing = [];

        foreach ($this->currentRecords($subject) as $slug => $record) {
            if ($record->status !== ConsentStatus::Granted) {
                continue;
            }
            if ($record->policy_version_id === null) {
                continue;
            }
            $version = $record->policyVersion()->first();

            if ($version?->status === PolicyVersionStatus::Superseded) {
                $needing[] = $slug;
            }
        }

        return $needing;
    }

    public function currentRecord(Model|Authenticatable|string $subject, string $type): ?ConsentRecord
    {
        return $this->currentRecords($subject)[$type] ?? null;
    }

    /**
     * Append the guest's current state onto the user, once. Guest rows stay
     * as history; the user gets a guest_merge record per type whose current
     * state differs from theirs, stamped with the policy version the guest
     * actually saw. Repeating the merge is a no-op.
     */
    public function mergeGuest(string $guestKey, Model|Authenticatable $user): void
    {
        $guestState = $this->currentRecords($guestKey);
        $userState = $this->currentRecords($user);

        foreach ($guestState as $slug => $guestRecord) {
            $userRecord = $userState[$slug] ?? null;

            if ($userRecord instanceof ConsentRecord && $userRecord->status === $guestRecord->status) {
                continue;
            }

            $record = $this->write(
                $user,
                $this->types->resolve($slug),
                $guestRecord->status,
                'guest_merge',
                $guestRecord->policy_version_id,
                $guestRecord->policy_version,
            );

            $this->events->dispatch($record->status === ConsentStatus::Withdrawn
                ? new ConsentWithdrawn($record)
                : new ConsentRecorded($record));
        }
    }

    /**
     * Everything the package holds about a subject, emitting public ids only.
     *
     * @return array{consents: list<array<string, mixed>>, activity: list<array<string, mixed>>}
     */
    public function exportSubject(Model|Authenticatable|string $subject): array
    {
        $slugs = ConsentType::query()->pluck('slug', 'id');

        $consents = array_values($this->recordsQuery($subject)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get()
            ->map(fn (ConsentRecord $record): array => [
                'id' => $record->public_id,
                'type' => $slugs->get($record->consent_type_id),
                'status' => $record->status->value,
                'source' => $record->source,
                'policy_version' => $record->policy_version,
                'recorded_at' => $record->recorded_at->toIso8601String(),
            ])
            ->all());

        $activity = array_values($this->activityQuery($subject)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get()
            ->map(fn (ActivityEvent $event): array => [
                'id' => $event->public_id,
                'event_type' => $event->event_type->value,
                'context' => $event->context,
                'recorded_at' => $event->recorded_at->toIso8601String(),
            ])
            ->all());

        return ['consents' => $consents, 'activity' => $activity];
    }

    /**
     * DSR erasure: strip the subject identity off consent rows and activity
     * events, keeping the anonymous history for statistics, and log the dsr
     * action. Runs through the query builder deliberately — the eloquent
     * append-only guards protect against application code, not maintenance.
     * Scrubbing guest keys out of event context mutates chained events, so
     * the hash chain breaks at that point by design: erasure outranks tamper
     * evidence, and the break itself documents that an erasure happened.
     */
    public function forgetSubject(Model|Authenticatable|string $subject): void
    {
        $this->recordsQuery($subject)->toBase()->update([
            'subjectable_type' => null,
            'subjectable_id' => null,
            'guest_key' => null,
        ]);

        if (is_string($subject)) {
            foreach ($this->activityQuery($subject)->get() as $event) {
                $context = $event->context ?? [];
                unset($context['guest_key']);
                $event->update(['context' => $context]);
            }
        } else {
            $this->activityQuery($subject)->toBase()->update([
                'subjectable_type' => null,
                'subjectable_id' => null,
            ]);
        }

        $this->activity->record(ActivityType::DsrAction, context: ['action' => 'forget']);
    }

    /**
     * A consent-aware outbound call to a registered provider: the vendor's
     * do-not-train flag is injected from the subject's ai_training state and
     * the inference is logged in the same motion.
     */
    public function provider(string $name): PendingProviderCall
    {
        // resolved from the container to avoid a constructor cycle
        return app(ProviderCalls::class)->provider($name);
    }

    /**
     * @return array<string, ConsentRecord> the latest record per type slug
     */
    private function currentRecords(Model|Authenticatable|string $subject): array
    {
        try {
            $records = $this->recordsQuery($subject)
                ->orderByDesc('recorded_at')
                ->orderByDesc('id')
                ->get();

            $slugs = ConsentType::query()->pluck('slug', 'id');
        } catch (QueryException) {
            return []; // unmigrated database: defaults apply
        }

        $current = [];

        foreach ($records as $record) {
            $slug = $slugs->get($record->consent_type_id);

            if (is_string($slug) && ! isset($current[$slug])) {
                $current[$slug] = $record;
            }
        }

        return $current;
    }

    /**
     * @return Builder<ConsentRecord>
     */
    private function recordsQuery(Model|Authenticatable|string $subject): Builder
    {
        $query = ConsentRecord::query();

        if (is_string($subject)) {
            return $query->where('guest_key', $subject);
        }

        return $query
            ->where('subjectable_type', $this->morphClassOf($subject))
            ->where('subjectable_id', $this->idOf($subject));
    }

    /**
     * @return Builder<ActivityEvent>
     */
    private function activityQuery(Model|Authenticatable|string $subject): Builder
    {
        $query = ActivityEvent::query();

        if (is_string($subject)) {
            return $query->where('context->guest_key', $subject);
        }

        return $query
            ->where('subjectable_type', $this->morphClassOf($subject))
            ->where('subjectable_id', $this->idOf($subject));
    }

    private function write(
        Model|Authenticatable|string $subject,
        ConsentType $type,
        ConsentStatus $status,
        string $source,
        ?int $versionId,
        ?string $versionString,
    ): ConsentRecord {
        $record = new ConsentRecord([
            'consent_type_id' => $type->id,
            'status' => $status,
            'source' => $source,
            'policy_version_id' => $versionId,
            'policy_version' => $versionString,
        ]);

        if (is_string($subject)) {
            $record->guest_key = $subject;
        } else {
            $record->subjectable_type = $this->morphClassOf($subject);
            $record->subjectable_id = $this->idOf($subject);
        }

        $record->save();

        return $record;
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function publishedPolicyVersionFor(string $type): array
    {
        $document = PolicyDocument::query()
            ->whereNull('tenant_id')
            ->where('slug', 'consent.' . $type)
            ->where('active', true)
            ->first();

        $published = $document?->publishedVersion()->first();

        if ($published === null) {
            return [null, null]; // file-served consent text; no version snapshot exists yet
        }

        return [(int) $published->id, $published->version];
    }

    private function morphClassOf(Model|Authenticatable $subject): string
    {
        return $subject instanceof Model ? $subject->getMorphClass() : $subject::class;
    }

    private function idOf(Model|Authenticatable $subject): mixed
    {
        return $subject instanceof Model ? $subject->getKey() : $subject->getAuthIdentifier();
    }
}
