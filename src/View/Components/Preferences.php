<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Simtabi\Laranail\AiCompliance\Payload\BootPayload;
use Simtabi\Laranail\AiCompliance\Support\CurrentSubject;

/**
 * The consent preferences panel shell: one toggle per configured consent
 * type with its translated label and short text, posting plain forms to the
 * consents endpoint so it works without any javascript. The livewire and js
 * components render the same payload interactively.
 */
final class Preferences extends Component
{
    public function __construct(
        public ?string $locale = null,
    ) {}

    public function render(): View
    {
        $subject = app(CurrentSubject::class);

        /** @var array<string, mixed> $payload */
        $payload = app(BootPayload::class)->toArray(
            locale: $this->locale,
            user: auth()->user(),
            guestKey: $subject->guestKey(),
        );

        /** @var array<string, mixed> $consent */
        $consent = is_array($payload['consent']) ? $payload['consent'] : [];

        return $this->view('ai-compliance::components.preferences', [
            'types' => is_array($consent['types'] ?? null) ? $consent['types'] : [],
            'state' => is_array($consent['state'] ?? null) ? $consent['state'] : [],
            'reconsent' => is_array($consent['reconsent'] ?? null) ? $consent['reconsent'] : [],
            'strings' => is_array($payload['strings']) ? $payload['strings'] : [],
        ]);
    }
}
