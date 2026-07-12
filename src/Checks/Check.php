<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks;

/**
 * One automated compliance check. key() names the checklist item the result
 * is written to; host apps register more checks by tagging them with
 * 'ai-compliance.checks' in the container.
 */
interface Check
{
    public function key(): string;

    public function run(): CheckResult;
}
