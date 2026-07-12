<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Console\Commands;

use Simtabi\Laranail\AiCompliance\Checks\CheckRunner;
use Simtabi\Laranail\Package\Tools\Commands\Command;

/**
 * Runs every automated compliance check now and prints the results. Exits
 * non-zero when anything failed, so it slots into ci and cron alike; the
 * scheduler runs the same command daily by default.
 */
final class AuditCommand extends Command
{
    protected $signature = 'laranail::ai-compliance.audit';

    protected $description = 'Run all automated compliance checks now';

    public function handle(CheckRunner $runner): int
    {
        $results = $runner->run();

        if ($results === []) {
            $this->components->warn('no checks ran; is the checklist seeded? (laranail::ai-compliance.install)');

            return self::FAILURE;
        }

        $failed = 0;

        foreach ($results as $result) {
            $this->components->twoColumnDetail(
                sprintf('%s <fg=gray>(%s)</>', $result['key'], $result['status']),
                $result['message'],
            );

            if ($result['status'] === 'fail') {
                $failed++;
            }
        }

        if ($failed > 0) {
            $this->components->error(sprintf('%d check(s) failing', $failed));

            return self::FAILURE;
        }

        $this->components->info(sprintf('%d checks ran, none failing', count($results)));

        return self::SUCCESS;
    }
}
