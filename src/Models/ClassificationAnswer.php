<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Override;
use Simtabi\Laranail\AiCompliance\Models\Concerns\BelongsToTenant;

/**
 * One answer to the project intake classification (spec section 2). The
 * answers switch checklist sections on or off and are themselves evidence.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $question_key
 * @property string $answer
 * @property string $answered_by
 * @property CarbonImmutable $answered_at
 */
class ClassificationAnswer extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable((string) config('laranail.ai-compliance.tables.classification_answers', 'ai_classification_answers'));
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'answered_at' => 'immutable_datetime',
        ];
    }
}
