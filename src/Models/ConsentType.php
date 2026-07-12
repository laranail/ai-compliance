<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Simtabi\Laranail\AiCompliance\Database\Factories\ConsentTypeFactory;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;
use Simtabi\Laranail\AiCompliance\Enums\LegalBasis;

/**
 * One granular consent switch (ai_training, ai_chatbot, ...). The row is
 * the foreign-key anchor for consent records and the canonical name for
 * exports; display labels live in the translation files.
 *
 * @property int $id
 * @property string $slug
 * @property string $label
 * @property string|null $description
 * @property LegalBasis $legal_basis
 * @property ConsentStatus $default_state
 * @property bool $active
 */
class ConsentType extends Model
{
    /** @use HasFactory<ConsentTypeFactory> */
    use HasFactory;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable((string) config('laranail.ai-compliance.tables.consent_types', 'ai_consent_types'));
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'legal_basis' => LegalBasis::class,
            'default_state' => ConsentStatus::class,
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ConsentRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(ConsentRecord::class, 'consent_type_id');
    }

    protected static function newFactory(): ConsentTypeFactory
    {
        return ConsentTypeFactory::new();
    }
}
