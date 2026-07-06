<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Simtabi\Laranail\AiCompliance\AiComplianceServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [AiComplianceServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('laranail.ai-compliance.placeholders', [
            'company' => 'Acme',
            'product' => 'Acme App',
            'contact_email' => 'privacy@acme.test',
            'privacy_url' => 'https://acme.test/privacy',
            'settings_path' => '/settings/ai',
        ]);
    }
}
