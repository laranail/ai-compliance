<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\Markdown;

use Psr\Log\LoggerInterface;
use League\CommonMark\Util\Xml;
use League\CommonMark\Node\Node;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;

/**
 * Renders a ShortcodeNode to the neutral <ai-c> custom element every UI
 * stack binds to: Blade/Livewire/Filament replace the node server-side, the
 * JS core hydrates it in the browser, and everything else shows the fallback
 * text. Shortcodes outside the registered vocabulary render only their
 * fallback and log a warning, so a typo can never blank a legal document.
 *
 * @see ShortcodeParser
 */
final readonly class ShortcodeRenderer implements NodeRendererInterface
{
    /**
     * @param list<string> $registered
     */
    public function __construct(
        private array $registered,
        private LoggerInterface $logger,
    ) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement|string
    {
        assert($node instanceof ShortcodeNode);

        if (! in_array($node->name, $this->registered, true)) {
            $this->logger->warning('ai-compliance: unknown policy shortcode, rendering fallback text', [
                'shortcode' => $node->name,
            ]);

            return Xml::escape($node->fallback);
        }

        return new HtmlElement('ai-c', [
            'data-component' => $node->name,
            'data-props'     => json_encode($node->props, JSON_THROW_ON_ERROR),
        ], Xml::escape($node->fallback));
    }
}
