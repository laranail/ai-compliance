<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Override;
use Simtabi\Laranail\AiCompliance\Database\Factories\ConsentRecordFactory;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;
use Simtabi\Laranail\AiCompliance\Models\Concerns\BelongsToTenant;

/**
 * One consent event, append-only by design: a change writes a new row,
 * never updates the old one, and the current state is the latest row per
 * (subject, type). The model throws on update/delete; the single sanctioned
 * mutation is DSR anonymization, which runs through the query builder in
 * ConsentManager::forgetSubject() on purpose.
 *
 * @property int $id
 * @property string $public_id
 * @property string $tenant_id
 * @property int $consent_type_id
 * @property string|null $subjectable_type
 * @property int|string|null $subjectable_id
 * @property string|null $guest_key
 * @property ConsentStatus $status
 * @property string $source
 * @property int|null $policy_version_id
 * @property string|null $policy_version
 * @property string|null $ip_hash
 * @property CarbonImmutable $recorded_at
 */
class ConsentRecord extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ConsentRecordFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable((string) config('laranail.ai-compliance.tables.consent_records', 'ai_consent_records'));
    }

    /**
     * @return BelongsTo<ConsentType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ConsentType::class, 'consent_type_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subjectable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<PolicyVersion, $this>
     */
    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'policy_version_id');
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            $record->public_id ??= (string) Str::ulid();
            $record->recorded_at ??= now()->toImmutable();
        });

        static::updating(function (): never {
            throw new LogicException('consent records are append-only; write a new row instead');
        });

        static::deleting(function (): never {
            throw new LogicException('consent records are append-only; anonymize via ConsentManager::forgetSubject() instead');
        });

        static::saving(function (self $record): void {
            $hasSubject = $record->subjectable_type !== null && $record->subjectable_id !== null;

            if ($hasSubject === ($record->guest_key !== null)) {
                throw new InvalidArgumentException('set exactly one of subject or guest_key on a consent record');
            }
        });
    }

    protected static function newFactory(): ConsentRecordFactory
    {
        return ConsentRecordFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'status' => ConsentStatus::class,
            'recorded_at' => 'immutable_datetime',
        ];
    }
}
