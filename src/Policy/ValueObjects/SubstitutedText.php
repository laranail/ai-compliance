<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\ValueObjects;

/**
 * The result of placeholder substitution: the text with every configured
 * {{key}} replaced, and the distinct placeholders that remained unresolved.
 */
final readonly class SubstitutedText
{
    /**
     * @param list<string> $unresolved
     */
    public function __construct(
        public string $text,
        public array $unresolved,
    ) {}
}
