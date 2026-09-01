<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyContent;
use Simtabi\Laranail\AiCompliance\View\IslandRenderer;

/**
 * Renders one policy document: title, version, the compiled html with its
 * <ai-c> islands replaced server-side, and a not-yet-translated notice when
 * the locale fell back.
 */
final class Policy extends Component
{
    public function __construct(
        public string $slug,
        public ?string $locale = null,
        public bool $showTitle = true,
    ) {}

    public function render(): View
    {
        /** @var PolicyRepository $policies */
        $policies = app(PolicyRepository::class);

        $document = $policies->find($this->slug, $this->locale);

        return $this->view('laranail-ai-compliance::components.policy', [
            'document' => $document,
            'html' => $document instanceof PolicyContent
                ? app(IslandRenderer::class)->render($document->html)
                : null,
        ]);
    }
}
