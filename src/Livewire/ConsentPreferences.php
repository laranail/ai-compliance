<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Consent\GuestKeys;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;
use Simtabi\Laranail\AiCompliance\Payload\BootPayload;
use Simtabi\Laranail\AiCompliance\Support\CurrentSubject;

/**
 * Interactive consent preferences: each toggle writes an append-only record
 * for the current subject and re-renders from the fresh state, so the panel
 * always reflects exactly what the log says. Only registered when livewire
 * is installed.
 */
final class ConsentPreferences extends Component
{
    public ?string $locale = null;

    public function toggle(string $type, string $status): void
    {
        $consentStatus = ConsentStatus::tryFrom($status);

        if ($consentStatus === null) {
            return;
        }

        $subject = app(CurrentSubject::class)->resolve()
            ?? app(GuestKeys::class)->issue(request());

        app(ConsentManager::class)->record($subject, $type, $consentStatus, 'livewire');

        $this->dispatch('ai-compliance:consent-changed', type: $type, status: $status);
    }

    public function render(): View
    {
        /** @var array<string, mixed> $payload */
        $payload = app(BootPayload::class)->toArray(
            locale: $this->locale,
            user: auth()->user(),
            guestKey: app(CurrentSubject::class)->guestKey(),
        );

        /** @var array<string, mixed> $consent */
        $consent = is_array($payload['consent']) ? $payload['consent'] : [];

        return view('ai-compliance::livewire.consent-preferences', [
            'types' => is_array($consent['types'] ?? null) ? $consent['types'] : [],
            'state' => is_array($consent['state'] ?? null) ? $consent['state'] : [],
            'reconsent' => is_array($consent['reconsent'] ?? null) ? $consent['reconsent'] : [],
            'strings' => is_array($payload['strings']) ? $payload['strings'] : [],
        ]);
    }
}
