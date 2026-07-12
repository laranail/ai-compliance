<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\Markdown;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * An inline [[name key="value"]] shortcode parsed out of policy markdown.
 * The fallback prop is what non-hydrating surfaces (plain html, terminal,
 * unknown components) show instead of the interactive island.
 */
final class ShortcodeNode extends AbstractInline
{
    /**
     * @param  array<string, string>  $props
     */
    public function __construct(
        public readonly string $name,
        public readonly array $props,
        public readonly string $fallback,
    ) {
        parent::__construct();
    }
}
