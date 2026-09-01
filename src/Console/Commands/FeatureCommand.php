<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Console\Commands;

use Simtabi\Laranail\AiCompliance\Features\FeatureGate;
use Simtabi\Laranail\Package\Tools\Commands\Command;

/**
 * The feature kill switches from the console: list the configured features
 * with their effective state, or flip one. The same toggle path as the
 * admin api and filament, so events fire and the change is logged.
 */
final class FeatureCommand extends Command
{
    protected $signature = 'laranail::ai-compliance.feature
                            {feature? : the feature to toggle; omit to list}
                            {--enable : turn the feature on}
                            {--disable : turn the feature off}';

    protected $description = 'List or toggle the AI feature kill switches';

    public function handle(FeatureGate $gate): int
    {
        /** @var array<string, mixed> $features */
        $features = (array) config('laranail.ai-compliance.features', []);

        $feature = $this->argument('feature');

        if (! is_string($feature) || $feature === '') {
            foreach (array_keys($features) as $name) {
                $this->components->twoColumnDetail($name, $gate->enabled($name) ? 'enabled' : 'disabled');
            }

            return self::SUCCESS;
        }

        if (! array_key_exists($feature, $features)) {
            $this->error(sprintf('unknown feature "%s"; configured: %s', $feature, implode(', ', array_keys($features))));

            return self::FAILURE;
        }

        $enable = (bool) $this->option('enable');
        $disable = (bool) $this->option('disable');

        if ($enable === $disable) {
            $this->components->twoColumnDetail($feature, $gate->enabled($feature) ? 'enabled' : 'disabled');
            $this->components->info('pass --enable or --disable to change it');

            return self::SUCCESS;
        }

        $gate->toggle($feature, $enable, 'console');

        $this->components->info(sprintf('%s is now %s', $feature, $enable ? 'enabled' : 'disabled'));

        return self::SUCCESS;
    }
}
