<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks\Builtin;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Simtabi\Laranail\AiCompliance\Checks\Check;
use Simtabi\Laranail\AiCompliance\Checks\CheckResult;

/**
 * An accountable owner must be named and reachable: the contact placeholders
 * feed the published policies, so empty values mean the notices point
 * nowhere.
 */
final readonly class DataProtectionContactCheck implements Check
{
    public function __construct(
        private ConfigRepository $config,
    ) {}

    public function key(): string
    {
        return 'governance.accountable_owner';
    }

    public function run(): CheckResult
    {
        $placeholders = $this->config->get('laranail.ai-compliance.placeholders', []);
        $placeholders = is_array($placeholders) ? $placeholders : [];

        $missing = [];

        foreach (['contact_email', 'dpo_or_contact_name'] as $key) {
            $value = $placeholders[$key] ?? null;

            if (! is_string($value) || $value === '') {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            return CheckResult::fail('data-protection contact placeholders are unset: '.implode(', ', $missing));
        }

        return CheckResult::ok('the accountable contact is configured and feeds the published policies');
    }
}
