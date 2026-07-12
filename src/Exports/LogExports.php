<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Exports;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Builder;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Models\ConsentType;

/**
 * Consent-log and activity-log exports (spec FR-5): scoped by date and type,
 * emitting public ids only, with subjects pseudonymized by default — a
 * stable keyed hash, so the same subject lines up across exports without
 * identifying anyone. Every export writes its own activity event.
 */
final readonly class LogExports
{
    public function __construct(
        private ConfigRepository $config,
        private ActivityRecorder $activity,
    ) {}

    /**
     * @param  array{type?: string|null, status?: string|null, from?: string|null, to?: string|null}  $filters
     * @return list<array<string, string|null>>
     */
    public function consentRows(array $filters = [], bool $pseudonymize = true): array
    {
        $slugs = ConsentType::query()->pluck('slug', 'id');

        $query = ConsentRecord::query()->orderBy('recorded_at')->orderBy('id');

        if (is_string($filters['type'] ?? null)) {
            $typeId = $slugs->search($filters['type']);
            $query->where('consent_type_id', $typeId === false ? -1 : $typeId);
        }

        if (is_string($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        $this->applyDateRange($query, $filters);

        $rows = [];

        foreach ($query->lazy() as $record) {
            $rows[] = [
                'id' => $record->public_id,
                'subject' => $this->subjectColumn($record->subjectable_type, $record->subjectable_id, $record->guest_key, $pseudonymize),
                'consent_type' => (string) $slugs->get($record->consent_type_id),
                'status' => $record->status->value,
                'source' => $record->source,
                'policy_version' => $record->policy_version,
                'recorded_at' => $record->recorded_at->toIso8601String(),
            ];
        }

        $this->logExport('consents', count($rows), $filters, $pseudonymize);

        return $rows;
    }

    /**
     * @param  array{type?: string|null, from?: string|null, to?: string|null}  $filters
     * @return list<array<string, string|null>>
     */
    public function activityRows(array $filters = [], bool $pseudonymize = true): array
    {
        $query = ActivityEvent::query()->orderBy('recorded_at')->orderBy('id');

        if (is_string($filters['type'] ?? null)) {
            $query->where('event_type', $filters['type']);
        }

        $this->applyDateRange($query, $filters);

        $rows = [];

        foreach ($query->lazy() as $event) {
            $context = $event->context ?? [];

            if ($pseudonymize && isset($context['guest_key']) && is_string($context['guest_key'])) {
                $context['guest_key'] = $this->pseudonym('guest', $context['guest_key']);
            }

            $rows[] = [
                'id' => $event->public_id,
                'event_type' => $event->event_type->value,
                'subject' => $this->subjectColumn($event->subjectable_type, $event->subjectable_id, null, $pseudonymize),
                'provider_id' => $event->provider_id !== null ? (string) $event->provider_id : null,
                'context' => json_encode($context, JSON_THROW_ON_ERROR),
                'recorded_at' => $event->recorded_at->toIso8601String(),
            ];
        }

        $this->logExport('activity', count($rows), $filters, $pseudonymize);

        return $rows;
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     */
    public function toCsv(array $rows): string
    {
        if ($rows === []) {
            return "\n";
        }

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return "\n";
        }

        fputcsv($handle, array_keys($rows[0]), escape: '\\');

        foreach ($rows as $row) {
            fputcsv($handle, array_values($row), escape: '\\');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * A stable pseudonym: the same subject lines up across exports, nobody
     * is identified. Keyed with the app key so the mapping cannot be
     * recomputed outside this installation.
     */
    private function pseudonym(string $type, string $id): string
    {
        $key = $this->config->get('app.key');

        return 'sub_' . substr(hash_hmac('sha256', $type . '#' . $id, is_string($key) ? $key : ''), 0, 16);
    }

    private function subjectColumn(?string $subjectType, int|string|null $subjectId, ?string $guestKey, bool $pseudonymize): ?string
    {
        if ($guestKey !== null) {
            return $pseudonymize ? $this->pseudonym('guest', $guestKey) : $guestKey;
        }

        if ($subjectType === null || $subjectId === null) {
            return null; // anonymized history
        }

        return $pseudonymize
            ? $this->pseudonym($subjectType, (string) $subjectId)
            : $subjectType . '#' . $subjectId;
    }

    /**
     * @param  Builder<ConsentRecord>|Builder<ActivityEvent>  $query
     * @param  array<string, string|null>  $filters
     */
    private function applyDateRange($query, array $filters): void
    {
        if (is_string($filters['from'] ?? null)) {
            $query->where('recorded_at', '>=', $filters['from']);
        }

        if (is_string($filters['to'] ?? null)) {
            $query->where('recorded_at', '<=', $filters['to']);
        }
    }

    /**
     * @param  array<string, string|null>  $filters
     */
    private function logExport(string $log, int $rows, array $filters, bool $pseudonymize): void
    {
        $this->activity->record(ActivityType::Export, context: [
            'log' => $log,
            'rows' => $rows,
            'filters' => array_filter($filters, static fn (?string $value): bool => $value !== null),
            'pseudonymized' => $pseudonymize,
        ]);
    }
}
