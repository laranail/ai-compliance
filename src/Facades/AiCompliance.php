<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Facades;

use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyContent;
use Simtabi\Laranail\AiCompliance\AiCompliance as AiComplianceManager;

/**
 * @method static PolicyContent|null policy(string $slug, string|null $locale = null)
 * @method static list<PolicyContent> policies(string|null $locale = null)
 * @method static array<string, mixed> bootPayload(string|null $locale = null)
 *
 * @see AiComplianceManager
 */
final class AiCompliance extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiComplianceManager::class;
    }
}
