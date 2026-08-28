<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Database\Seeders\ChecklistSeeder;

uses(RefreshDatabase::class);

it('warns and fails when the checklist was never seeded', function (): void {
    $this->artisan('laranail::ai-compliance.audit')
        ->expectsOutputToContain('no checks ran')
        ->assertFailed();
});

it('exits non-zero while checks are failing and zero once they pass enough', function (): void {
    Http::fake(['*' => Http::response("User-agent: ClaudeBot\nDisallow: /")]);
    app(ChecklistSeeder::class)->run();

    // fresh install: empty registry + unset contact fail
    $this->artisan('laranail::ai-compliance.audit')
        ->expectsOutputToContain('governance.provider_registry')
        ->assertFailed();

    // make the failing checks pass
    config()->set('laranail.ai-compliance.placeholders.dpo_or_contact_name', 'Imani');
    Provider::factory()->dueDiligenceComplete()->create();
    ActivityEvent::factory()->create();

    $this->artisan('laranail::ai-compliance.audit')->assertSuccessful();
});
