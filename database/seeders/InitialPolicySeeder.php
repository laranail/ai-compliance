<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Seeders;

use Illuminate\Database\Seeder;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;

/**
 * Thin wrapper over the policy file sync: every shipped (or app-published)
 * policy markdown file becomes a document row with a published version 1.0
 * whose translations carry the file's content and checksums verbatim.
 * Idempotent — re-running applies the normal sync rules.
 *
 * Run with: php artisan db:seed --class="Simtabi\Laranail\AiCompliance\Database\Seeders\InitialPolicySeeder"
 */
final class InitialPolicySeeder extends Seeder
{
    public function __construct(
        private readonly PolicySync $sync,
    ) {}

    public function run(): void
    {
        $this->sync->sync();
    }
}
