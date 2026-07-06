<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * The admin kill switch per ai feature. Absence of a row means the feature
 * runs (subject to consent); a row records the explicit toggle and who threw
 * it.
 *
 * @property int $id
 * @property string|null $tenant_id
 * @property string $feature
 * @property bool $enabled
 * @property string|null $toggled_by
 * @property CarbonImmutable|null $toggled_at
 */
class FeatureState extends Model
{
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable((string) config('laranail.ai-compliance.tables.feature_states', 'ai_feature_states'));
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'toggled_at' => 'immutable_datetime',
        ];
    }
}
