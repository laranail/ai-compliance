<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Simtabi\Laranail\AiCompliance\Checklist\Classification;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Database\Seeders\ChecklistSeeder;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Gate::define('ai-compliance:audit', fn (GenericUser $user): bool => (bool) ($user->audit ?? false));

    app(ChecklistSeeder::class)->run();
    app(PolicySync::class)->sync();
    Provider::factory()->dueDiligenceComplete()->create(['name' => 'report-provider']);
    app(Classification::class)->record(['consequential_decisions' => 'no'], 'tester');
    app(ConsentManager::class)->grant(makeUser(), 'ai_training');
});

it('serves the point-in-time report with every required block', function (): void {
    $html = $this->actingAs(new GenericUser(['id' => 1, 'audit' => true]))
        ->get('/ai-compliance/admin/report')
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('AI compliance report')
        ->toContain('Consents granted')                       // dashboard tiles
        ->toContain('ai_training')                             // consent stats by type
        ->toContain('consequential_decisions')                 // classification answers
        ->toContain('governance.provider_registry')            // checklist with keys
        ->toContain('report-provider')                         // provider registry
        ->toContain('transparency')                            // policy documents
        ->toContain('1.0');                                    // published versions
});

it('writes the report to a file from the command and logs the export', function (): void {
    $path = sys_get_temp_dir() . '/ai-compliance-test-report-' . bin2hex(random_bytes(4)) . '.html';

    $this->artisan('laranail::ai-compliance.report', ['--path' => $path])->assertSuccessful();

    expect((string) file_get_contents($path))->toContain('AI compliance report');

    expect(ActivityEvent::query()
        ->where('event_type', 'export')
        ->where('context->log', 'compliance_report')
        ->exists())->toBeTrue();

    unlink($path);
});
