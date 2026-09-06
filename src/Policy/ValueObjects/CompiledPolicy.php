<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\ValueObjects;

/**
 * The output of compiling one policy file: html with shortcodes resolved to
 * <ai-c> elements but {{placeholders}} left intact (substitution happens at
 * serve time), plus the parsed frontmatter and the source checksum.
 */
final readonly class CompiledPolicy
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $html,
        public array $meta,
        public string $checksum,
    ) {}

    public function title(): ?string
    {
        $title = $this->meta['title'] ?? null;

        return is_string($title) ? $title : null;
    }

    public function short(): ?string
    {
        $short = $this->meta['short'] ?? null;

        return is_string($short) ? $short : null;
    }
}
