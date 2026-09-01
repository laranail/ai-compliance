<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Database\Seeders;

use Illuminate\Database\Seeder;
use Simtabi\Laranail\AiCompliance\Checklist\ChecklistDefinitions;
use Simtabi\Laranail\AiCompliance\Models\ChecklistItem;

/**
 * Seeds every checklist item from spec sections 4-10, keyed and idempotent:
 * a fresh install's dashboard is the full checklist with everything at
 * review. Definition text updates on re-run; status and evidence are never
 * touched.
 *
 * Run with: php artisan db:seed --class="Simtabi\Laranail\AiCompliance\Database\Seeders\ChecklistSeeder"
 */
final class ChecklistSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ChecklistDefinitions::all() as $definition) {
            $existing = ChecklistItem::query()
                ->forDefaultTenant()
                ->where('key', $definition['key'])
                ->first();

            $attributes = [
                'section' => $definition['section'],
                'label' => $definition['label'],
                'description' => $definition['description'],
                'evidence_type' => $definition['evidence_type'],
                'staleness_months' => $definition['staleness_months'],
                'applies_when' => $definition['applies_when'],
            ];

            if ($existing instanceof ChecklistItem) {
                $existing->update($attributes);

                continue;
            }

            ChecklistItem::query()->create([
                'tenant_id' => '',
                'key' => $definition['key'],
                'status' => 'review',
                ...$attributes,
            ]);
        }
    }
}
