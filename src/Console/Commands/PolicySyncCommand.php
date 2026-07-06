<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Console\Commands;

use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;
use Simtabi\Laranail\Package\Tools\Commands\Command;

/**
 * Imports the policy markdown files (shipped and app-published) into the
 * versioned database layer using the checksum rules: first import publishes
 * 1.0, changed-but-untouched content drafts, hand-edited content is flagged
 * and never overwritten.
 */
final class PolicySyncCommand extends Command
{
    protected $signature = 'laranail::ai-compliance.policy.sync';

    protected $description = 'Import policy markdown files into the versioned database layer';

    public function handle(PolicySync $sync): int
    {
        $result = $sync->sync();

        $this->components->twoColumnDetail('Imported (published 1.0 or added locale)', (string) count($result->imported));
        $this->components->twoColumnDetail('Drafted (file changed, database copy untouched)', (string) count($result->drafted));
        $this->components->twoColumnDetail('Flagged (hand-edited, left alone)', (string) count($result->flagged));
        $this->components->twoColumnDetail('Unchanged', (string) count($result->unchanged));

        foreach ($result->flagged as $entry) {
            $this->components->warn(sprintf('%s was edited in the app after import; the changed file was NOT applied. Reconcile via the editing api.', $entry));
        }

        return self::SUCCESS;
    }
}
