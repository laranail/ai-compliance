<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks\Builtin;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Simtabi\Laranail\AiCompliance\Checks\Check;
use Simtabi\Laranail\AiCompliance\Checks\CheckResult;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyContent;

/**
 * The first-contact disclosure is only verifiable when every configured
 * surface resolves a disclosure document and the consumer routes serve them.
 */
final readonly class DisclosureSurfacesCheck implements Check
{
    public function __construct(
        private PolicyRepository $policies,
        private ConfigRepository $config,
    ) {}

    public function key(): string
    {
        return 'transparency.first_contact_disclosure';
    }

    public function run(): CheckResult
    {
        if (! (bool) $this->config->get('laranail.ai-compliance.routes.enabled', true)) {
            return CheckResult::fail('the consumer routes are disabled; disclosure surfaces cannot be served');
        }

        $surfaces = $this->config->get('laranail.ai-compliance.disclosure_surfaces', []);
        $missing = [];

        foreach (is_array($surfaces) ? $surfaces : [] as $surface) {
            if (is_string($surface) && ! $this->policies->find('disclosure.'.$surface) instanceof PolicyContent) {
                $missing[] = $surface;
            }
        }

        if ($missing !== []) {
            return CheckResult::fail('no disclosure document resolves for: '.implode(', ', $missing));
        }

        return CheckResult::ok('every configured surface serves a disclosure document');
    }
}
