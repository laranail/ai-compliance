<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Models;

use Override;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Simtabi\Laranail\AiCompliance\Enums\PolicyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Simtabi\Laranail\AiCompliance\Enums\PolicyVersionStatus;
use Simtabi\Laranail\AiCompliance\Models\Concerns\BelongsToTenant;
use Simtabi\Laranail\AiCompliance\Database\Factories\PolicyDocumentFactory;

/**
 * One logical policy document per tenant: a transparency page, a consent
 * text, or a disclosure line. Content lives on versions/translations; this
 * row is the stable identity a slug resolves to.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $slug
 * @property PolicyType $type
 * @property string|null $surface
 * @property string|null $consent_type_slug
 * @property string|null $source_path
 * @property string $default_locale
 * @property bool $active
 */
class PolicyDocument extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<PolicyDocumentFactory> */
    use HasFactory;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable((string) config('laranail.ai-compliance.tables.policy_documents', 'ai_policy_documents'));
    }

    /**
     * @return HasMany<PolicyVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PolicyVersion::class, 'policy_document_id');
    }

    /**
     * @return HasOne<PolicyVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(PolicyVersion::class, 'policy_document_id')->latestOfMany();
    }

    /**
     * @return HasOne<PolicyVersion, $this>
     */
    public function publishedVersion(): HasOne
    {
        return $this->hasOne(PolicyVersion::class, 'policy_document_id')
            ->where('status', PolicyVersionStatus::Published->value);
    }

    /**
     * The single open draft, if any (the package keeps at most one draft per
     * document — sync and the editing api both reuse it).
     *
     * @return HasOne<PolicyVersion, $this>
     */
    public function draftVersion(): HasOne
    {
        return $this->hasOne(PolicyVersion::class, 'policy_document_id')
            ->where('status', PolicyVersionStatus::Draft->value)
            ->latestOfMany();
    }

    protected static function newFactory(): PolicyDocumentFactory
    {
        return PolicyDocumentFactory::new();
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'type'   => PolicyType::class,
            'active' => 'boolean',
        ];
    }
}
