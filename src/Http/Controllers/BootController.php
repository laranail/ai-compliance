<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Simtabi\Laranail\AiCompliance\Consent\GuestKeys;
use Simtabi\Laranail\AiCompliance\Payload\BootPayload;

/**
 * Serves the shared component contract to the JS core. Blade, Livewire, and
 * Filament consume the same payload in-process instead of over http. Guests
 * only get a key once they record a consent — boot never mints identity.
 */
final class BootController
{
    public function __invoke(Request $request, BootPayload $payload, GuestKeys $guestKeys): JsonResponse
    {
        $locale = $request->query('locale');

        return new JsonResponse($payload->toArray(
            locale: is_string($locale) ? $locale : null,
            user: $request->user(),
            guestKey: $request->user() !== null ? null : $guestKeys->current($request),
        ));
    }
}
