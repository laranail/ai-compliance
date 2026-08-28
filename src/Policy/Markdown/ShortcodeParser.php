<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\Markdown;

use League\CommonMark\Parser\InlineParserContext;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\Inline\InlineParserInterface;

/**
 * Parses [[name key="value" other="value"]] into a ShortcodeNode. Names are
 * lowercase kebab-case; props are double-quoted key="value" pairs. Anything
 * that does not match the shape is left alone as literal text.
 */
final class ShortcodeParser implements InlineParserInterface
{
    private const string PROPS_PATTERN = '/([a-zA-Z][\w-]*)="([^"]*)"/';

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex('\[\[([a-z][a-z0-9-]*)((?:\s+[a-zA-Z][\w-]*="[^"]*")*)\s*\]\]');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $subMatches = $inlineContext->getSubMatches();
        $name = $subMatches[0] ?? '';

        if ($name === '') {
            return false;
        }

        $props = $this->parseProps($subMatches[1] ?? '');
        $fallback = $props['fallback'] ?? '';
        unset($props['fallback']);

        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());
        $inlineContext->getContainer()->appendChild(new ShortcodeNode($name, $props, $fallback));

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function parseProps(string $raw): array
    {
        if (preg_match_all(self::PROPS_PATTERN, $raw, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        $props = [];

        foreach ($matches as $match) {
            $props[$match[1]] = $match[2];
        }

        return $props;
    }
}
