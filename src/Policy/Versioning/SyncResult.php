<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\Versioning;

/**
 * What one policy file sync did, per (slug, locale): imported documents get
 * a published 1.0; changed-but-untouched translations become drafts;
 * hand-edited translations are flagged and never overwritten.
 */
final class SyncResult
{
    /** @var list<string> "slug (locale)" entries */
    public private(set) array $imported = [];

    /** @var list<string> */
    public private(set) array $drafted = [];

    /** @var list<string> */
    public private(set) array $flagged = [];

    /** @var list<string> */
    public private(set) array $unchanged = [];

    public function recordImported(string $slug, string $locale): void
    {
        $this->imported[] = sprintf('%s (%s)', $slug, $locale);
    }

    public function recordDrafted(string $slug, string $locale): void
    {
        $this->drafted[] = sprintf('%s (%s)', $slug, $locale);
    }

    public function recordFlagged(string $slug, string $locale): void
    {
        $this->flagged[] = sprintf('%s (%s)', $slug, $locale);
    }

    public function recordUnchanged(string $slug, string $locale): void
    {
        $this->unchanged[] = sprintf('%s (%s)', $slug, $locale);
    }
}
