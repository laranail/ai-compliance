<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;
use Simtabi\Laranail\AiCompliance\Database\Factories\ChecklistItemFactory;
use Simtabi\Laranail\AiCompliance\Enums\CheckStatus;

/**
 * One compliance checklist item. Auto items are kept honest by the check
 * runner; manual items carry human evidence and auto-degrade from ok to
 * review once their verification goes stale.
 *
 * @property int $id
 * @property string|null $tenant_id
 * @property string $key
 * @property string $section
 * @property string $label
 * @property string|null $description
 * @property array<string, string>|null $applies_when
 * @property CheckStatus $status
 * @property string $evidence_type
 * @property string|null $evidence_ref
 * @property CarbonImmutable|null $last_verified_at
 * @property string|null $verified_by
 * @property int $staleness_months
 */
class ChecklistItem extends Model
{
    /** @use HasFactory<ChecklistItemFactory> */
    use HasFactory;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable((string) config('laranail.ai-compliance.tables.checklist_items', 'ai_checklist_items'));
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'applies_when' => 'array',
            'status' => CheckStatus::class,
            'last_verified_at' => 'immutable_datetime',
        ];
    }

    public function isStale(): bool
    {
        return $this->status === CheckStatus::Ok
            && $this->last_verified_at !== null
            && $this->last_verified_at->addMonths($this->staleness_months)->isPast();
    }

    protected static function newFactory(): ChecklistItemFactory
    {
        return ChecklistItemFactory::new();
    }
}
