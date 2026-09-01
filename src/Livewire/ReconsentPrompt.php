<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Support\CurrentSubject;

/**
 * Shows when the current subject's granted consents reference superseded
 * policy versions, offering a re-grant per affected type. Renders nothing
 * for subjects with nothing to re-confirm.
 */
final class ReconsentPrompt extends Component
{
    public function regrant(string $type): void
    {
        $subject = app(CurrentSubject::class)->resolve();

        if ($subject === null) {
            return;
        }

        app(ConsentManager::class)->grant($subject, $type, 'reconsent_prompt');

        $this->dispatch('ai-compliance:consent-changed', type: $type, status: 'granted');
    }

    #[On('ai-compliance:consent-changed')]
    public function refresh(): void
    {
        // re-render; the reconsent list is computed fresh in render()
    }

    public function render(): View
    {
        $subject = app(CurrentSubject::class)->resolve();

        return view('laranail-ai-compliance::livewire.reconsent-prompt', [
            'reconsent' => $subject !== null ? app(ConsentManager::class)->reconsentFor($subject) : [],
        ]);
    }
}
