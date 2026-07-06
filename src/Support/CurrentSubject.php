<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Simtabi\Laranail\AiCompliance\Consent\GuestKeys;

/**
 * Resolves the consent subject of the current request: the authenticated
 * user, else the guest key from the cookie, else null (a brand-new visitor
 * who carries every default state and no identity).
 */
final readonly class CurrentSubject
{
    public function __construct(
        private AuthFactory $auth,
        private GuestKeys $guestKeys,
    ) {}

    public function resolve(?Request $request = null): Model|Authenticatable|string|null
    {
        $user = $this->auth->guard()->user();

        if ($user !== null) {
            return $user;
        }

        $request ??= request();

        return $this->guestKeys->current($request);
    }

    public function guestKey(?Request $request = null): ?string
    {
        if ($this->auth->guard()->user() !== null) {
            return null;
        }

        $request ??= request();

        return $this->guestKeys->current($request);
    }
}
