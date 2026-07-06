<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance;

use Simtabi\Laranail\AiCompliance\Payload\BootPayload;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyContent;

/**
 * The package manager bound to the container and exposed through the
 * AiCompliance facade. Later milestones add the consent, checklist, and
 * provider surfaces here; the policy pipeline is the first.
 */
final readonly class AiCompliance
{
    public function __construct(
        private PolicyRepository $policies,
        private BootPayload $payload,
    ) {}

    public function policy(string $slug, ?string $locale = null): ?PolicyContent
    {
        return $this->policies->find($slug, $locale);
    }

    /**
     * @return list<PolicyContent>
     */
    public function policies(?string $locale = null): array
    {
        return $this->policies->all($locale);
    }

    /**
     * @return array<string, mixed>
     */
    public function bootPayload(?string $locale = null): array
    {
        return $this->payload->toArray($locale);
    }
}
