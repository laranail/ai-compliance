<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Console\Commands;

use Simtabi\Laranail\AiCompliance\Consent\ConsentTypes;
use Simtabi\Laranail\AiCompliance\Database\Seeders\ChecklistSeeder;
use Simtabi\Laranail\AiCompliance\Database\Seeders\DemoSeeder;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;
use Simtabi\Laranail\Package\Tools\Commands\Command;

/**
 * One-shot setup: publish the config and policy files, run the migrations,
 * import the policies as published version 1.0, seed the consent types and
 * the full checklist (everything at review), and report which placeholders
 * the operator still needs to fill.
 */
final class InstallCommand extends Command
{
    protected $signature = 'laranail::ai-compliance.install
                            {--no-publish : skip publishing config and policy files}
                            {--demo : also seed the demo data (local development only)}';

    protected $description = 'Install ai-compliance: publish, migrate, and import the policy documents';

    public function handle(PolicySync $sync, PolicyRepository $policies, ConsentTypes $types): int
    {
        if (! $this->option('no-publish')) {
            $this->call('vendor:publish', ['--tag' => 'laranail::ai-compliance-config']);
            $this->call('vendor:publish', ['--tag' => 'laranail::ai-compliance-policies']);
        }

        $this->call('migrate');

        $types->seedFromConfig();
        $this->laravel->make(ChecklistSeeder::class)->run();
        $this->components->info('consent types and the compliance checklist are seeded (checklist starts at review)');

        if ($this->option('demo')) {
            $this->laravel->make(DemoSeeder::class)->run();
            $this->components->warn('demo data seeded (8 consent records; local development only)');
        }

        $result = $sync->sync();
        $this->components->info(sprintf(
            'policy import: %d imported, %d drafted, %d flagged, %d unchanged',
            count($result->imported),
            count($result->drafted),
            count($result->flagged),
            count($result->unchanged),
        ));

        $unresolved = [];

        foreach ($policies->all() as $content) {
            foreach ($content->unresolvedPlaceholders as $placeholder) {
                $unresolved[$placeholder] = true;
            }
        }

        if ($unresolved !== []) {
            $this->components->warn('these placeholders are still unresolved in your policy documents:');

            foreach (array_keys($unresolved) as $placeholder) {
                $this->components->bulletList(['{{'.$placeholder.'}}']);
            }

            $this->line('fill the simple keys in config/ai-compliance.php (placeholders block) and edit the prose ones in the published policy files.');
        }

        return self::SUCCESS;
    }
}
