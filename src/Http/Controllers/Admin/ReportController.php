<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Http\Response;
use Simtabi\Laranail\AiCompliance\Reports\ComplianceReport;

/**
 * The point-in-time compliance report as print-ready html (spec FR-7).
 */
final readonly class ReportController
{
    public function __invoke(ComplianceReport $report): Response
    {
        return new Response($report->html(), 200, ['Content-Type' => 'text/html']);
    }
}
