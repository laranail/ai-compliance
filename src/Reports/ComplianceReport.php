<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Reports;

use Simtabi\Laranail\AiCompliance\Models\Provider;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Support\DashboardStats;
use Simtabi\Laranail\AiCompliance\Checklist\Classification;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;

/**
 * The point-in-time compliance report (spec FR-7): dashboard statistics,
 * the checklist with evidence, the provider registry, classification
 * answers, and every policy document's published version — the artifact an
 * operator hands to an auditor. Rendered as print-ready html; pdf is the
 * host's printer.
 */
final readonly class ComplianceReport
{
    public function __construct(
        private DashboardStats $stats,
        private Classification $classification,
        private ViewFactory $views,
        private ActivityRecorder $activity,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $checklist = ChecklistItem::query()
            ->forDefaultTenant()
            ->orderBy('id')
            ->get()
            ->groupBy('section');

        $providers = Provider::query()->orderBy('name')->get();

        $documents = PolicyDocument::query()
            ->forDefaultTenant()
            ->with('publishedVersion')
            ->orderBy('slug')
            ->get();

        return [
            'generated_at'   => now()->toIso8601String(),
            'tiles'          => $this->stats->tiles(),
            'classification' => $this->classification->answers(),
            'checklist'      => $checklist,
            'providers'      => $providers,
            'documents'      => $documents,
        ];
    }

    public function html(): string
    {
        $html = $this->views->make('laranail-ai-compliance::report', $this->data())->render();

        $this->activity->record(ActivityType::Export, context: [
            'log'    => 'compliance_report',
            'format' => 'html',
        ]);

        return $html;
    }
}
