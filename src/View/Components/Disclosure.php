<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyContent;

/**
 * The ai disclosure line for a surface (chat, content, decision), rendered
 * before any model output ever reaches the user. The text comes from the
 * disclosure.{surface} policy document through the normal pipeline, so it is
 * versioned, translated, and editable like everything else.
 */
final class Disclosure extends Component
{
    public function __construct(
        public string $surface = 'chat',
        public ?string $locale = null,
    ) {}

    public function render(): View
    {
        /** @var PolicyRepository $policies */
        $policies = app(PolicyRepository::class);

        $disclosure = $policies->find('disclosure.' . $this->surface, $this->locale);

        return $this->view('laranail-ai-compliance::components.disclosure', [
            'disclosure' => $disclosure instanceof PolicyContent ? $disclosure : null,
        ]);
    }
}
