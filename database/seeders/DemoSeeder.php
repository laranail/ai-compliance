<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Seeders;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;

/**
 * Local-dev demo data reproducing the reference dashboard state (spec
 * 12.3): eight consent rows across two moments — two granted, six denied —
 * zero providers so the registry check shows its review flag, and a couple
 * of activity events. Never run by install; only on demand:
 *
 * php artisan db:seed --class="Simtabi\Laranail\AiCompliance\Database\Seeders\DemoSeeder"
 */
final class DemoSeeder extends Seeder
{
    public function __construct(
        private readonly ConsentManager $consent,
        private readonly ActivityRecorder $activity,
    ) {}

    public function run(): void
    {
        $earlier = now()->subDays(3);
        $now = now();

        $guests = [
            'g_demo_' . str_pad('1', 26, '1'),
            'g_demo_' . str_pad('2', 26, '2'),
        ];

        $this->travelTo($earlier, function () use ($guests): void {
            $this->consent->grant($guests[0], 'ai_chatbot', 'demo');
            $this->consent->deny($guests[0], 'ai_training', 'demo');
            $this->consent->deny($guests[0], 'ai_recommendations', 'demo');
            $this->consent->deny($guests[0], 'ai_personalization', 'demo');
        });

        $this->travelTo($now, function () use ($guests): void {
            $this->consent->grant($guests[1], 'ai_personalization', 'demo');
            $this->consent->deny($guests[1], 'ai_training', 'demo');
            $this->consent->deny($guests[1], 'ai_chatbot', 'demo');
            $this->consent->deny($guests[1], 'ai_recommendations', 'demo');
        });

        $this->activity->record(ActivityType::SettingChange, context: [
            'setting' => 'demo',
            'action'  => 'seeded',
        ]);
    }

    /**
     * @param callable(): void $callback
     */
    private function travelTo(DateTimeInterface $moment, callable $callback): void
    {
        Carbon::setTestNow($moment);

        try {
            $callback();
        } finally {
            Carbon::setTestNow();
        }
    }
}
