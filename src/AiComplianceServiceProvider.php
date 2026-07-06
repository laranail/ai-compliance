<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Override;
use Simtabi\Laranail\AiCompliance\Console\Commands\InstallCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicyPublishCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicyShowCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicySyncCommand;
use Simtabi\Laranail\AiCompliance\Events\PoliciesSynced;
use Simtabi\Laranail\AiCompliance\Events\PolicyPublished;
use Simtabi\Laranail\AiCompliance\Listeners\FlushCompiledPolicyCache;
use Simtabi\Laranail\AiCompliance\Payload\BootPayload;
use Simtabi\Laranail\AiCompliance\Policy\CompiledPolicyCache;
use Simtabi\Laranail\AiCompliance\Policy\PlaceholderRegistry;
use Simtabi\Laranail\AiCompliance\Policy\PolicyCompiler;
use Simtabi\Laranail\AiCompliance\Policy\PolicyFileLoader;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyPublisher;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyStaleness;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

/**
 * Entry point for laranail/ai-compliance. configurePackage() declares the
 * package surface (namespaced config, translations, routes, the publishable
 * policy markdown); packageRegistered() wires the policy pipeline
 * singletons. Database, consent, and check subsystems arrive in later
 * milestones per .claude/design/PLAN.md.
 */
final class AiComplianceServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        // vendor/package name -> config merges under config('laranail.ai-compliance.*')
        $package
            ->name('laranail/ai-compliance')
            ->setPublishTagId('ai-compliance')
            ->hasConfigFile()
            ->hasTranslations('ai-compliance')
            ->hasRoutes('api', 'admin')
            ->discoversMigrations()
            ->runsMigrations()
            ->publishDirectory('resources/policies', resource_path('policies/ai-compliance'), 'policies')
            ->hasCommands([
                InstallCommand::class,
                PolicyShowCommand::class,
                PolicySyncCommand::class,
                PolicyPublishCommand::class,
            ])
            ->hasAboutSection('AI Compliance', fn (): array => [
                'Contract' => (string) BootPayload::CONTRACT,
                'Policy documents (default locale)' => (string) count(
                    $this->app->make(PolicyFileLoader::class)->all($this->defaultLocale()),
                ),
            ]);
    }

    #[Override]
    public function packageRegistered(): void
    {
        $this->app->singleton(PolicyFileLoader::class, static fn (Application $app): PolicyFileLoader => new PolicyFileLoader(
            $app->make(ConfigRepository::class),
            dirname(__DIR__) . '/resources/policies',
        ));

        $this->app->singleton(PolicyCompiler::class);
        $this->app->singleton(PlaceholderRegistry::class);
        $this->app->singleton(CompiledPolicyCache::class);
        $this->app->singleton(PolicyRepository::class);
        $this->app->singleton(PolicySync::class);
        $this->app->singleton(PolicyPublisher::class);
        $this->app->singleton(PolicyStaleness::class);
        $this->app->singleton(BootPayload::class);
        $this->app->singleton(AiCompliance::class);
    }

    #[Override]
    public function packageBooted(): void
    {
        $events = $this->app->make(Dispatcher::class);

        $events->listen(PolicyPublished::class, FlushCompiledPolicyCache::class);
        $events->listen(PoliciesSynced::class, FlushCompiledPolicyCache::class);
    }

    private function defaultLocale(): string
    {
        $default = $this->app->make(ConfigRepository::class)->get('laranail.ai-compliance.locales.default', 'en');

        return is_string($default) ? $default : 'en';
    }
}
