<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks\Builtin;

use Simtabi\Laranail\AiCompliance\Checks\Check;
use Simtabi\Laranail\AiCompliance\Checks\CheckResult;
use Simtabi\Laranail\AiCompliance\Models\PolicyDocument;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyStaleness;

/**
 * Policy versioning is in place when the documents are imported (published
 * versions exist for consent rows to reference), nothing has drifted, and no
 * served document still carries unresolved placeholders.
 */
final readonly class PolicyVersioningCheck implements Check
{
    public function __construct(
        private PolicyStaleness $staleness,
        private PolicyRepository $policies,
    ) {}

    public function key(): string
    {
        return 'governance.policy_versioning';
    }

    public function run(): CheckResult
    {
        if (! PolicyDocument::query()->forDefaultTenant()->exists()) {
            return CheckResult::review('policy documents are not imported yet; run laranail::ai-compliance.policy.sync');
        }

        $issues = [];

        $stale = $this->staleness->report();

        if ($stale !== []) {
            $issues[] = sprintf('%d staleness signals (see the staleness report)', count($stale));
        }

        $unresolved = [];

        foreach ($this->policies->all() as $content) {
            foreach ($content->unresolvedPlaceholders as $placeholder) {
                $unresolved[$placeholder] = true;
            }
        }

        if ($unresolved !== []) {
            $issues[] = sprintf('%d unresolved placeholders in served policies', count($unresolved));
        }

        if ($issues !== []) {
            return CheckResult::review(implode('; ', $issues));
        }

        return CheckResult::ok('documents imported and versioned; no drift, no unresolved placeholders');
    }
}
