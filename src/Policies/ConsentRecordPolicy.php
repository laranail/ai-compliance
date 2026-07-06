<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policies;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorization for the consent log's admin surfaces. Reads map to the
 * audit gate, imports to the manage gate, exports to their own gate because
 * log exports are the sensitive ability. There are no update or delete
 * abilities at all: consent records are append-only.
 */
final readonly class ConsentRecordPolicy
{
    public function __construct(
        private Gate $gate,
    ) {}

    public function viewAny(Authenticatable $user): bool
    {
        return $this->gate->forUser($user)->allows('ai-compliance:audit');
    }

    public function view(Authenticatable $user): bool
    {
        return $this->gate->forUser($user)->allows('ai-compliance:audit');
    }

    public function create(Authenticatable $user): bool
    {
        // admin-side imports only; end users write through the consumer api
        return $this->gate->forUser($user)->allows('ai-compliance:manage');
    }

    public function export(Authenticatable $user): bool
    {
        return $this->gate->forUser($user)->allows('ai-compliance:export');
    }

    public function update(): bool
    {
        return false;
        // append-only, matches the model guard
    }

    public function delete(): bool
    {
        return false;
    }
}
