<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;

function scheduledExpressions(): array
{
    $expressions = [];

    foreach (app(Schedule::class)->events() as $event) {
        $expressions[(string) $event->command] = $event->expression;
    }

    return $expressions;
}

it('schedules the audit daily by default', function (): void {
    $found = collect(scheduledExpressions())
        ->filter(fn (string $expr, string $command): bool => str_contains($command, 'laranail::ai-compliance.audit'));

    expect($found)->toHaveCount(1)
        ->and($found->first())->toBe('0 0 * * *');
});

it('honors any cadence string from config', function (): void {
    config()->set('laranail.ai-compliance.checks.schedule', 'hourly');

    $found = collect(scheduledExpressions())
        ->filter(fn (string $expr, string $command): bool => str_contains($command, 'ai-compliance.audit'));

    expect($found->first())->toBe('0 * * * *');
});

it('skips the audit when the schedule config is null', function (): void {
    config()->set('laranail.ai-compliance.checks.schedule');

    $commands = array_keys(scheduledExpressions());

    expect(collect($commands)->contains(fn (string $c): bool => str_contains($c, 'ai-compliance.audit')))->toBeFalse();
});

it('schedules the prune only when activity retention is configured', function (): void {
    // default config ships retention.activity_events = null => no prune
    $withoutRetention = collect(scheduledExpressions())
        ->keys()
        ->contains(fn (string $c): bool => str_contains($c, 'ai-compliance.prune'));

    expect($withoutRetention)->toBeFalse();
});

it('schedules the prune when retention is set before the schedule resolves', function (): void {
    config()->set('laranail.ai-compliance.retention.activity_events', 180);

    $found = collect(scheduledExpressions())
        ->filter(fn (string $expr, string $command): bool => str_contains($command, 'ai-compliance.prune'));

    expect($found)->toHaveCount(1)
        ->and($found->first())->toBe('0 0 * * *');
});
