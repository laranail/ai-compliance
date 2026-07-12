<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Seeders;

use Illuminate\Database\Seeder;
use Simtabi\Laranail\AiCompliance\Consent\ConsentTypes;

/**
 * Inserts the configured consent types (the four defaults: training,
 * chatbot, recommendations, personalization) idempotently by slug.
 *
 * Run with: php artisan db:seed --class="Simtabi\Laranail\AiCompliance\Database\Seeders\ConsentTypeSeeder"
 */
final class ConsentTypeSeeder extends Seeder
{
    public function __construct(
        private readonly ConsentTypes $types,
    ) {}

    public function run(): void
    {
        $this->types->seedFromConfig();
    }
}
