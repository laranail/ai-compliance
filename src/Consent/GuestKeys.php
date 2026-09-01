<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Consent;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Server-issued pseudonymous identity for consent before login. The key is
 * an opaque random value in a long-lived cookie; the js never mints
 * identity, and login merges guest state into the user (source guest_merge).
 */
final readonly class GuestKeys
{
    private const string PREFIX = 'g_';

    public function __construct(
        private ConfigRepository $config,
        private CookieJar $cookies,
    ) {}

    /**
     * The guest key already on the request, if any.
     */
    public function current(Request $request): ?string
    {
        $value = $request->cookies->get($this->cookieName());

        return is_string($value) && str_starts_with($value, self::PREFIX) ? $value : null;
    }

    /**
     * The request's guest key, minting one (and queueing its cookie) when
     * absent.
     */
    public function issue(Request $request): string
    {
        $existing = $this->current($request);

        if ($existing !== null) {
            return $existing;
        }

        $key = self::PREFIX.Str::random(40);

        $this->cookies->queue($this->cookies->make(
            name: $this->cookieName(),
            value: $key,
            minutes: $this->lifetimeDays() * 24 * 60,
            httpOnly: true,
        ));

        return $key;
    }

    private function cookieName(): string
    {
        $name = $this->config->get('laranail.ai-compliance.guest.cookie', 'laranail_ai_compliance_guest');

        return is_string($name) ? $name : 'laranail_ai_compliance_guest';
    }

    private function lifetimeDays(): int
    {
        $days = $this->config->get('laranail.ai-compliance.guest.lifetime_days', 365);

        return is_int($days) ? $days : 365;
    }
}
