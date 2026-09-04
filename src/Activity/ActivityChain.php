<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Activity;

use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * The tamper-evidence tier: when enabled, every new event stores the hash of
 * its predecessor, so editing or deleting any historic row breaks every link
 * after it. Verification recomputes the whole chain; the result names the
 * first broken event.
 */
final readonly class ActivityChain
{
    private const string GENESIS = 'genesis';

    public function __construct(
        private ConfigRepository $config,
    ) {}

    public function enabled(): bool
    {
        return (bool) $this->config->get('laranail.ai-compliance.activity.hash_chain', false);
    }

    /**
     * The hash_prev value for the next event to be written. The chain links
     * chained events only, so enabling it on an existing log starts a fresh
     * chain instead of failing verification forever.
     */
    public function nextLink(): string
    {
        /** @var ActivityEvent|null $latest */
        $latest = ActivityEvent::query()->whereNotNull('hash_prev')->orderByDesc('id')->first();

        if ($latest === null) {
            return hash('sha256', self::GENESIS);
        }

        return $this->hashOf($latest);
    }

    /**
     * Walk the chained events in insertion order and recompute every link.
     *
     * @return array{valid: bool, checked: int, broken_at: string|null}
     */
    public function verify(): array
    {
        $expected = hash('sha256', self::GENESIS);
        $checked = 0;

        foreach (ActivityEvent::query()->whereNotNull('hash_prev')->orderBy('id')->lazy() as $event) {
            if ($event->hash_prev !== $expected) {
                return ['valid' => false, 'checked' => $checked, 'broken_at' => $event->public_id];
            }

            $expected = $this->hashOf($event);
            $checked++;
        }

        return ['valid' => true, 'checked' => $checked, 'broken_at' => null];
    }

    /**
     * The canonical hash of one event: its identity, payload, and its own
     * link, so every field an auditor relies on is covered.
     */
    private function hashOf(ActivityEvent $event): string
    {
        return hash('sha256', implode('|', [
            $event->public_id,
            $event->event_type->value,
            $event->subjectable_type ?? '',
            (string) ($event->subjectable_id ?? ''),
            json_encode($event->context, JSON_THROW_ON_ERROR),
            $event->recorded_at->toIso8601String(),
            $event->hash_prev ?? '',
        ]));
    }
}
