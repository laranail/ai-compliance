<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Simtabi\Laranail\AiCompliance\Database\Factories\PolicyTranslationFactory;

/**
 * The per-locale content of a policy version. `checksum` hashes the current
 * source markdown; `file_checksum` anchors what the shipped file contained
 * at import (file-drift signal); `origin_checksum` records the default-locale
 * checksum this translation was made from (translation-drift signal).
 *
 * @property int $id
 * @property int $policy_version_id
 * @property string $locale
 * @property string $title
 * @property string $source_markdown
 * @property string $compiled_html
 * @property array<string, mixed>|null $meta
 * @property string $checksum
 * @property string|null $file_checksum
 * @property string|null $origin_checksum
 */
class PolicyTranslation extends Model
{
    /** @use HasFactory<PolicyTranslationFactory> */
    use HasFactory;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable((string) config('laranail.ai-compliance.tables.policy_translations', 'ai_policy_translations'));
    }

    /**
     * @return BelongsTo<PolicyVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'policy_version_id');
    }

    /**
     * Whether an admin changed this translation after it was imported from
     * its shipped file. Hand-edited translations are never overwritten by a
     * sync; they are flagged instead.
     */
    public function isHandEdited(): bool
    {
        return $this->file_checksum !== null && $this->checksum !== $this->file_checksum;
    }

    protected static function newFactory(): PolicyTranslationFactory
    {
        return PolicyTranslationFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}
