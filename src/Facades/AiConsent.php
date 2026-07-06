<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Facades;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Providers\PendingProviderCall;

/**
 * @method static ConsentRecord grant(Model|Authenticatable|string $subject, string $type, string $source = 'app')
 * @method static ConsentRecord deny(Model|Authenticatable|string $subject, string $type, string $source = 'app')
 * @method static ConsentRecord withdraw(Model|Authenticatable|string $subject, string $type, string $source = 'app')
 * @method static ConsentRecord record(Model|Authenticatable|string $subject, string $type, ConsentStatus $status, string $source = 'app')
 * @method static bool granted(Model|Authenticatable|string $subject, string $type)
 * @method static bool allows(Model|Authenticatable|string $subject, string $feature)
 * @method static array<string, array{status: string, recorded_at: string|null, policy_version: string|null}> stateFor(Model|Authenticatable|string $subject)
 * @method static list<string> reconsentFor(Model|Authenticatable|string $subject)
 * @method static ConsentRecord|null currentRecord(Model|Authenticatable|string $subject, string $type)
 * @method static void mergeGuest(string $guestKey, Model|Authenticatable $user)
 * @method static array{consents: list<array<string, mixed>>, activity: list<array<string, mixed>>} exportSubject(Model|Authenticatable|string $subject)
 * @method static void forgetSubject(Model|Authenticatable|string $subject)
 * @method static PendingProviderCall provider(string $name)
 *
 * @see ConsentManager
 */
final class AiConsent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ConsentManager::class;
    }
}
