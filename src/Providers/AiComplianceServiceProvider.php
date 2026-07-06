<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Override;
use Simtabi\Laranail\AiCompliance\Activity\ActivityChain;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
use Simtabi\Laranail\AiCompliance\AiCompliance;
use Simtabi\Laranail\AiCompliance\Checklist\Classification;
use Simtabi\Laranail\AiCompliance\Checks\Builtin\ActivityLogAliveCheck;
use Simtabi\Laranail\AiCompliance\Checks\Builtin\ConsentUiReachableCheck;
use Simtabi\Laranail\AiCompliance\Checks\Builtin\CrawlerSignalsCheck;
use Simtabi\Laranail\AiCompliance\Checks\Builtin\DataProtectionContactCheck;
use Simtabi\Laranail\AiCompliance\Checks\Builtin\DisclosureSurfacesCheck;
use Simtabi\Laranail\AiCompliance\Checks\Builtin\PolicyVersioningCheck;
use Simtabi\Laranail\AiCompliance\Checks\Builtin\ProviderRegistryCheck;
use Simtabi\Laranail\AiCompliance\Checks\Builtin\RetentionScheduledCheck;
use Simtabi\Laranail\AiCompliance\Checks\Builtin\VendorDueDiligenceCheck;
use Simtabi\Laranail\AiCompliance\Checks\CheckRunner;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Consent\ConsentTypes;
use Simtabi\Laranail\AiCompliance\Consent\GuestKeys;
use Simtabi\Laranail\AiCompliance\Console\Commands\AuditCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\ExportCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\FeatureCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\InstallCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\NotifyReconsentCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicyPublishCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicyShowCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicySyncCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PruneCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\ReportCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\VerifyChainCommand;
use Simtabi\Laranail\AiCompliance\Database\Seeders\ChecklistSeeder;
use Simtabi\Laranail\AiCompliance\Database\Seeders\InitialPolicySeeder;
use Simtabi\Laranail\AiCompliance\Events\CheckFailed;
use Simtabi\Laranail\AiCompliance\Events\ConsentRecorded;
use Simtabi\Laranail\AiCompliance\Events\ConsentWithdrawn;
use Simtabi\Laranail\AiCompliance\Events\PoliciesSynced;
use Simtabi\Laranail\AiCompliance\Events\PolicyPublished;
use Simtabi\Laranail\AiCompliance\Exports\LogExports;
use Simtabi\Laranail\AiCompliance\Features\FeatureGate;
use Simtabi\Laranail\AiCompliance\Http\Middleware\EnsureConsent;
use Simtabi\Laranail\AiCompliance\Http\Middleware\EnsureFeature;
use Simtabi\Laranail\AiCompliance\Listeners\FlushCompiledPolicyCache;
use Simtabi\Laranail\AiCompliance\Listeners\RecordConsentActivity;
use Simtabi\Laranail\AiCompliance\Listeners\SendCheckAlerts;
use Simtabi\Laranail\AiCompliance\Livewire\ConsentPreferences;
use Simtabi\Laranail\AiCompliance\Livewire\ReconsentPrompt;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;
use Simtabi\Laranail\AiCompliance\Payload\BootPayload;
use Simtabi\Laranail\AiCompliance\Policies\ConsentRecordPolicy;
use Simtabi\Laranail\AiCompliance\Policy\CompiledPolicyCache;
use Simtabi\Laranail\AiCompliance\Policy\PlaceholderRegistry;
use Simtabi\Laranail\AiCompliance\Policy\PolicyCompiler;
use Simtabi\Laranail\AiCompliance\Policy\PolicyFileLoader;
use Simtabi\Laranail\AiCompliance\Policy\PolicyRepository;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyPublisher;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicyStaleness;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;
use Simtabi\Laranail\AiCompliance\Reports\ComplianceReport;
use Simtabi\Laranail\AiCompliance\Support\DashboardStats;
use Simtabi\Laranail\AiCompliance\View\Components\ConsentGate;
use Simtabi\Laranail\Package\Tools\Enums\Cadence;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\Package\Tools\Support\Definitions\AboutSectionDefinition;
use Simtabi\Laranail\Package\Tools\Support\Definitions\AutoSeederDefinition;
use Simtabi\Laranail\Package\Tools\Support\Definitions\ScheduledCommandDefinition;

/**
 * Entry point for laranail/ai-compliance. The whole package surface is
 * declared fluently in configurePackage() — resources, commands, event
 * listeners, middleware aliases, the consent-record policy, the morph
 * map, scheduled commands, blade and livewire components, and the
 * db:seed-time seeders. packageRegistered() wires the service singletons.
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
            ->hasViews('ai-compliance')
            ->hasTranslations('ai-compliance')
            ->hasRoutes('api', 'admin')
            ->discoversMigrations()
            ->runsMigrations()
            ->publishDirectory('resources/policies', resource_path('policies/ai-compliance'), 'policies')
            ->hasCommands([
                InstallCommand::class,
                AuditCommand::class,
                PolicyShowCommand::class,
                PolicySyncCommand::class,
                PolicyPublishCommand::class,
                PruneCommand::class,
                VerifyChainCommand::class,
                ExportCommand::class,
                FeatureCommand::class,
                ReportCommand::class,
                NotifyReconsentCommand::class,
            ])
            ->hasAboutSection(
                AboutSectionDefinition::make('AI Compliance')
                    // the contract is a constant; the document count stays a
                    // per-field lazy closure, resolved only when about runs
                    ->field('Contract', (string) BootPayload::CONTRACT)
                    ->field('Policy documents (default locale)', fn (): string => (string) count(
                        $this->app->make(PolicyFileLoader::class)->all($this->defaultLocale()),
                    )),
            )
            ->registerEventListeners([
                PolicyPublished::class => FlushCompiledPolicyCache::class,
                PoliciesSynced::class => FlushCompiledPolicyCache::class,
                ConsentRecorded::class => RecordConsentActivity::class,
                ConsentWithdrawn::class => RecordConsentActivity::class,
                CheckFailed::class => SendCheckAlerts::class,
            ])
            ->registerRouteMiddlewares([
                'ai.consent' => EnsureConsent::class,
                'ai.feature' => EnsureFeature::class,
            ])
            ->registerPolicies([ConsentRecord::class => ConsentRecordPolicy::class])
            // non-enforcing 'user' alias so stored *_type columns survive a
            // host renaming its user class; morph_map adds host aliases
            ->registerMorphMapFromConfig('laranail.ai-compliance.morph_map', 'laranail.ai-compliance.user_model')
            ->registerScheduledCommands([
                // cadence from config: any Cadence value, frequency string, or
                // raw cron; missing key falls back to daily, explicit null = off
                ScheduledCommandDefinition::make('laranail::ai-compliance.audit')
                    ->cadenceFromConfig('laranail.ai-compliance.checks.schedule', Cadence::Daily),
                // exact contract: retention configured (not null) => prune runs
                ScheduledCommandDefinition::make('laranail::ai-compliance.prune')
                    ->cadenceFromConfig('laranail.ai-compliance.checks.schedule', Cadence::Daily)
                    ->whenConfigNotNull('laranail.ai-compliance.retention.activity_events'),
            ])
            ->hasBladeComponentNamespace('Simtabi\\Laranail\\AiCompliance\\View\\Components', 'ai-compliance')
            // spec-shaped alias: <x-ai-compliance::gate> next to consent-gate
            ->hasBladeComponentAlias('ai-compliance::gate', ConsentGate::class)
            ->withoutLivewireNamespacePrefix()
            ->hasLivewireComponents([
                'ai-compliance.consent-preferences' => ConsentPreferences::class,
                'ai-compliance.reconsent-prompt' => ReconsentPrompt::class,
            ], whenConfig: 'laranail.ai-compliance.livewire.enabled')
            ->hasPackageSeeders(
                AutoSeederDefinition::make('laranail/ai-compliance')
                    ->seeders([ChecklistSeeder::class, InitialPolicySeeder::class])
                    ->inNamespace('Simtabi\\Laranail\\AiCompliance\\Database\\Seeders')
                    ->whenConfig('laranail.ai-compliance.seeders.auto'),
            );
    }

    #[Override]
    public function packageRegistered(): void
    {
        $shippedPolicies = $this->package->basePath('/resources/policies');

        $this->app->singleton(PolicyFileLoader::class, static fn (Application $app): PolicyFileLoader => new PolicyFileLoader(
            $app->make(ConfigRepository::class),
            $shippedPolicies,
        ));

        $this->app->singleton(PolicyCompiler::class);
        $this->app->singleton(PlaceholderRegistry::class);
        $this->app->singleton(CompiledPolicyCache::class);
        $this->app->singleton(PolicyRepository::class);
        $this->app->singleton(PolicySync::class);
        $this->app->singleton(PolicyPublisher::class);
        $this->app->singleton(PolicyStaleness::class);
        $this->app->singleton(ConsentTypes::class);
        $this->app->singleton(GuestKeys::class);
        $this->app->singleton(ActivityChain::class);
        $this->app->singleton(ActivityRecorder::class);
        $this->app->singleton(ProviderCalls::class);
        $this->app->singleton(LogExports::class);
        $this->app->singleton(ComplianceReport::class);
        $this->app->singleton(DashboardStats::class);
        $this->app->singleton(FeatureGate::class);
        $this->app->singleton(ConsentManager::class);
        $this->app->singleton(Classification::class);
        $this->app->singleton(BootPayload::class);
        $this->app->singleton(AiCompliance::class);

        $this->app->singleton(CheckRunner::class, static fn (Application $app): CheckRunner => new CheckRunner(
            $app,
            $app->make(Dispatcher::class),
            [
                DisclosureSurfacesCheck::class,
                CrawlerSignalsCheck::class,
                ProviderRegistryCheck::class,
                VendorDueDiligenceCheck::class,
                ActivityLogAliveCheck::class,
                DataProtectionContactCheck::class,
                ConsentUiReachableCheck::class,
                RetentionScheduledCheck::class,
                PolicyVersioningCheck::class,
            ],
        ));
    }

    private function defaultLocale(): string
    {
        $default = $this->app->make(ConfigRepository::class)->get('laranail.ai-compliance.locales.default', 'en');

        return is_string($default) ? $default : 'en';
    }
}
