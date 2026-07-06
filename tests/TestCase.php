<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Simtabi\Laranail\AiCompliance\AiComplianceServiceProvider;
use Simtabi\Laranail\DatabaseTools\Providers\DatabaseToolsServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DatabaseToolsServiceProvider::class, // registers the configuredMorphs schema macros
            AiComplianceServiceProvider::class,
        ];
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

        // admin gate tests exercise 403s directly; the auth middleware would
        // redirect guests to a login route the skeleton does not have
        $app['config']->set('laranail.ai-compliance.admin_routes.middleware', ['web']);
    }
}
