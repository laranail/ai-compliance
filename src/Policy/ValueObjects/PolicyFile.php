<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\ValueObjects;

use Simtabi\Laranail\AiCompliance\Enums\PolicyType;

/**
 * A policy markdown file as found on disk, before compilation. The slug and
 * type are derived from the path relative to the locale directory
 * (consent/ai_training.md -> consent.ai_training, disclosures/chat.md ->
 * disclosure.chat, transparency.md -> transparency).
 */
final readonly class PolicyFile
{
    public function __construct(
        public string $slug,
        public string $locale,
        public PolicyType $type,
        public string $relativePath,
        public string $absolutePath,
        public string $contents,
        public string $checksum,
    ) {}
}
