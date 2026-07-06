<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Override;
use Simtabi\Laranail\AiCompliance\Database\Factories\ActivityEventFactory;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;

/**
 * One entry in the ai activity log. This milestone records consent changes
 * and dsr actions; the activity milestone completes the event-type coverage,
 * retention, and the hash chain.
 *
 * @property int $id
 * @property string $public_id
 * @property string|null $tenant_id
 * @property ActivityType $event_type
 * @property string|null $actorable_type
 * @property int|string|null $actorable_id
 * @property string|null $subjectable_type
 * @property int|string|null $subjectable_id
 * @property int|null $provider_id
 * @property array<string, mixed>|null $context
 * @property string|null $hash_prev
 * @property CarbonImmutable $recorded_at
 */
class ActivityEvent extends Model
{
    /** @use HasFactory<ActivityEventFactory> */
    use HasFactory;

    use MassPrunable;

    public $timestamps = false;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable((string) config('laranail.ai-compliance.tables.activity_events', 'ai_activity_events'));
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'event_type' => ActivityType::class,
            'context' => 'array',
            'recorded_at' => 'immutable_datetime',
        ];
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            $event->public_id ??= (string) Str::ulid();
            $event->recorded_at ??= now()->toImmutable();
        });
    }

    /**
     * Events older than the configured retention (days) are prunable; a null
     * retention keeps everything. Note the hash chain breaks by design when
     * chained events are pruned — retention and tamper evidence are both
     * policies, and the operator picks their balance.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        $days = config('laranail.ai-compliance.retention.activity_events');

        if (! is_int($days) || $days <= 0) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('recorded_at', '<', now()->subDays($days));
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function actorable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subjectable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): ActivityEventFactory
    {
        return ActivityEventFactory::new();
    }
}
