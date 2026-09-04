<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Tenant scoping over a NOT NULL tenant_id column. The single-tenant
 * default is the empty-string sentinel, not null: composite unique
 * indexes over a nullable column never fire (sql NULLs do not collide),
 * so the sentinel is what makes (tenant_id, key)-style constraints
 * actually constrain.
 */
trait BelongsToTenant
{
    public const string DEFAULT_TENANT = '';

    /**
     * @param Builder<static> $query
     */
    public function scopeForDefaultTenant(Builder $query): void
    {
        $query->where($query->qualifyColumn('tenant_id'), self::DEFAULT_TENANT);
    }
}
