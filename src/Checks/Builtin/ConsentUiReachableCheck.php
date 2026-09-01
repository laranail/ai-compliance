<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks\Builtin;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Simtabi\Laranail\AiCompliance\Checks\Check;
use Simtabi\Laranail\AiCompliance\Checks\CheckResult;
use Simtabi\Laranail\AiCompliance\Consent\ConsentTypes;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyContent;

/**
 * Granular consent needs the write endpoint enabled, types configured, and
 * a consent text resolvable per type (users must see what they agree to).
 */
final readonly class ConsentUiReachableCheck implements Check
{
    public function __construct(
        private ConsentTypes $types,
        private PolicyRepository $policies,
        private ConfigRepository $config,
    ) {}

    public function key(): string
    {
        return 'consent.granular_types';
    }

    public function run(): CheckResult
    {
        if (! (bool) $this->config->get('laranail.ai-compliance.routes.enabled', true)) {
            return CheckResult::fail('the consumer routes are disabled; consent cannot be recorded');
        }

        $slugs = $this->types->slugs();

        if ($slugs === []) {
            return CheckResult::fail('no consent types are configured');
        }

        $missingTexts = array_values(array_filter(
            $slugs,
            fn (string $slug): bool => ! $this->policies->find('consent.'.$slug) instanceof PolicyContent,
        ));

        if ($missingTexts !== []) {
            return CheckResult::fail('no consent text resolves for: '.implode(', ', $missingTexts));
        }

        return CheckResult::ok(sprintf('%d granular consent types with resolvable texts and a reachable endpoint', count($slugs)));
    }
}
