<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Console\Commands;

use Simtabi\Laranail\AiCompliance\Reports\ComplianceReport;
use Simtabi\Laranail\Package\Tools\Commands\Command;

/**
 * Writes the point-in-time compliance report to an html file — the artifact
 * an operator attaches to an audit. Print to pdf with the browser or any
 * html-to-pdf tool.
 */
final class ReportCommand extends Command
{
    protected $signature = 'laranail::ai-compliance.report
                            {--path= : output file (defaults to a dated name in the working directory)}';

    protected $description = 'Generate the point-in-time compliance report (html)';

    public function handle(ComplianceReport $report): int
    {
        $path = $this->stringOption('path');

        if ($path === '') {
            $path = sprintf('ai-compliance-report-%s.html', now()->format('Ymd-His'));
        }

        file_put_contents($path, $report->html());

        $this->components->info('report written to '.$path);

        return self::SUCCESS;
    }
}
