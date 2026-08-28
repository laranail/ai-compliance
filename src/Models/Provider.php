<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Models;

use Override;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Simtabi\Laranail\AiCompliance\Models\Concerns\BelongsToTenant;
use Simtabi\Laranail\AiCompliance\Database\Factories\ProviderFactory;

/**
 * One row in the ai provider/vendor registry: which model, from whom, under
 * what contract, and whether it trains on our data. Soft-deleted so
 * deactivated vendors stay referenceable from the activity log.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $name
 * @property string $vendor
 * @property string $model_name
 * @property string|null $model_version
 * @property string|null $endpoint_region
 * @property string $role
 * @property string|null $purpose
 * @property CarbonImmutable|null $dpa_signed_at
 * @property string $trains_on_our_data
 * @property string|null $training_summary_url
 * @property string|null $sub_processors_url
 * @property bool $marking_supported
 * @property string $due_diligence_status
 * @property string|null $owner
 */
class Provider extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ProviderFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable((string) config('laranail.ai-compliance.tables.providers', 'ai_providers'));
    }

    /**
     * A registry row is complete when the fields the due-diligence evidence
     * line requires are all filled.
     */
    public function isComplete(): bool
    {
        return $this->dpa_signed_at !== null
            && $this->endpoint_region !== null
            && $this->purpose !== null
            && $this->due_diligence_status === 'complete';
    }

    protected static function newFactory(): ProviderFactory
    {
        return ProviderFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'dpa_signed_at'     => 'immutable_datetime',
            'marking_supported' => 'boolean',
        ];
    }
}
