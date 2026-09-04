<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\View\Components;

use InvalidArgumentException;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Support\CurrentSubject;

/**
 * Renders its slot only when the current subject may use the wrapped
 * feature: `feature="..."` checks the feature's configured consent
 * requirements, `consent="..."` checks one consent type directly. The
 * `fallback` named slot renders otherwise.
 */
final class ConsentGate extends Component
{
    public function __construct(
        public ?string $feature = null,
        public ?string $consent = null,
    ) {
        if ($this->feature === null && $this->consent === null) {
            throw new InvalidArgumentException('the gate component needs a feature or a consent attribute');
        }
    }

    public function render(): View
    {
        return $this->view('laranail-ai-compliance::components.gate', [
            'allowed' => $this->allowed(),
        ]);
    }

    private function allowed(): bool
    {
        $subject = app(CurrentSubject::class)->resolve();

        if ($subject === null) {
            return false;
        }

        /** @var ConsentManager $consent */
        $consent = app(ConsentManager::class);

        if ($this->feature !== null && ! $consent->allows($subject, $this->feature)) {
            return false;
        }

        return $this->consent === null || $consent->granted($subject, $this->consent);
    }
}
