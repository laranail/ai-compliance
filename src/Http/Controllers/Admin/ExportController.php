<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Simtabi\Laranail\AiCompliance\Exports\LogExports;

/**
 * The export endpoints (spec FR-5), behind the dedicated export gate: log
 * exports are the sensitive ability. Always pseudonymized over http;
 * identified exports exist only on the console command.
 */
final readonly class ExportController
{
    public function __construct(
        private LogExports $exports,
    ) {}

    public function consents(Request $request): JsonResponse|Response
    {
        /** @var array{format?: string, type?: string, status?: string, from?: string, to?: string} $filters */
        $filters = $request->validate([
            'format' => ['sometimes', 'string', Rule::in(['csv', 'json'])],
            'type' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['granted', 'denied', 'withdrawn'])],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $rows = $this->exports->consentRows($filters);

        return $this->respond($rows, $filters['format'] ?? 'csv', 'consent-log');
    }

    public function activity(Request $request): JsonResponse|Response
    {
        /** @var array{format?: string, type?: string, from?: string, to?: string} $filters */
        $filters = $request->validate([
            'format' => ['sometimes', 'string', Rule::in(['csv', 'json'])],
            'type' => ['sometimes', 'string'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $rows = $this->exports->activityRows($filters);

        return $this->respond($rows, $filters['format'] ?? 'csv', 'activity-log');
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     */
    private function respond(array $rows, string $format, string $name): JsonResponse|Response
    {
        if ($format === 'json') {
            return new JsonResponse(['data' => $rows]);
        }

        return new Response($this->exports->toCsv($rows), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => sprintf('attachment; filename="%s.csv"', $name),
        ]);
    }
}
