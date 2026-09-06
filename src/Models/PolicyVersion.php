<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Models;

use Override;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;
use Simtabi\Laranail\AiCompliance\Database\Factories\PolicyVersionFactory;

/**
 * One version of a policy document. At most one version per document is
 * published at any time; publishing supersedes the previous one in the same
 * transaction (see PolicyPublisher).
 *
 * @property int $id
 * @property int $policy_document_id
 * @property string $version
 * @property PolicyVersionStatus $status
 * @property CarbonImmutable|null $effective_at
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $superseded_at
 */
class PolicyVersion extends Model
{
    /** @use HasFactory<PolicyVersionFactory> */
    use HasFactory;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable((string) config('laranail.ai-compliance.tables.policy_versions', 'ai_policy_versions'));
    }

    /**
     * @return BelongsTo<PolicyDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(PolicyDocument::class, 'policy_document_id');
    }

    /**
     * @return HasMany<PolicyTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PolicyTranslation::class, 'policy_version_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function authorable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isDraft(): bool
    {
        return $this->status === PolicyVersionStatus::Draft;
    }

    protected static function newFactory(): PolicyVersionFactory
    {
        return PolicyVersionFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'status'        => PolicyVersionStatus::class,
            'effective_at'  => 'immutable_datetime',
            'published_at'  => 'immutable_datetime',
            'superseded_at' => 'immutable_datetime',
        ];
    }
}
