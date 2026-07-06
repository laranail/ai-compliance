<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Console\Commands;

use Simtabi\Laranail\AiCompliance\Exports\LogExports;
use Simtabi\Laranail\Package\Tools\Commands\Command;

/**
 * Exports the consent or activity log to a file. Pseudonymized by default;
 * --identified emits raw subject references and is meant for statutory
 * requests only.
 */
final class ExportCommand extends Command
{
    protected $signature = 'laranail::ai-compliance.export
                            {log : consents or activity}
                            {--format=csv : csv or json}
                            {--type= : filter by consent type / event type}
                            {--status= : consent status filter}
                            {--from= : ISO date lower bound}
                            {--to= : ISO date upper bound}
                            {--identified : emit raw subject references instead of pseudonyms}
                            {--path= : output file (defaults next to the working directory)}';

    protected $description = 'Export the consent or activity log to csv or json';

    public function handle(LogExports $exports): int
    {
        $log = $this->argument('log');

        if (! in_array($log, ['consents', 'activity'], true)) {
            $this->error('log must be consents or activity');

            return self::FAILURE;
        }

        $filters = [
            'type' => $this->stringOption('type') !== '' ? $this->stringOption('type') : null,
            'status' => $this->stringOption('status') !== '' ? $this->stringOption('status') : null,
            'from' => $this->stringOption('from') !== '' ? $this->stringOption('from') : null,
            'to' => $this->stringOption('to') !== '' ? $this->stringOption('to') : null,
        ];

        $pseudonymize = ! (bool) $this->option('identified');

        $rows = $log === 'consents'
            ? $exports->consentRows($filters, $pseudonymize)
            : $exports->activityRows($filters, $pseudonymize);

        $format = $this->stringOption('format', 'csv') === 'json' ? 'json' : 'csv';

        $contents = $format === 'json'
            ? json_encode($rows, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n"
            : $exports->toCsv($rows);

        $path = $this->stringOption('path');

        if ($path === '') {
            $path = sprintf('%s-export-%s.%s', $log, now()->format('Ymd-His'), $format);
        }

        file_put_contents($path, $contents);

        $this->components->info(sprintf('%d rows exported to %s%s', count($rows), $path, $pseudonymize ? '' : ' (IDENTIFIED)'));

        return self::SUCCESS;
    }
}
