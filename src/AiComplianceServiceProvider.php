<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Override;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
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
use Simtabi\Laranail\AiCompliance\Console\Commands\InstallCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicyPublishCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicyShowCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicySyncCommand;
use Simtabi\Laranail\AiCompliance\Events\CheckFailed;
use Simtabi\Laranail\AiCompliance\Events\ConsentRecorded;
use Simtabi\Laranail\AiCompliance\Events\ConsentWithdrawn;
use Simtabi\Laranail\AiCompliance\Events\PoliciesSynced;
use Simtabi\Laranail\AiCompliance\Events\PolicyPublished;
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
use Simtabi\Laranail\AiCompliance\View\Components\ConsentGate;
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
        $this->app->singleton(ConsentTypes::class);
        $this->app->singleton(GuestKeys::class);
        $this->app->singleton(ActivityRecorder::class);
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

    #[Override]
    public function packageBooted(): void
    {
        $events = $this->app->make(Dispatcher::class);

        $events->listen(PolicyPublished::class, FlushCompiledPolicyCache::class);
        $events->listen(PoliciesSynced::class, FlushCompiledPolicyCache::class);
        $events->listen(ConsentRecorded::class, RecordConsentActivity::class);
        $events->listen(ConsentWithdrawn::class, RecordConsentActivity::class);
        $events->listen(CheckFailed::class, SendCheckAlerts::class);

        $this->app->make(Router::class)->aliasMiddleware('ai.consent', EnsureConsent::class);
        $this->app->make(Router::class)->aliasMiddleware('ai.feature', EnsureFeature::class);

        $this->registerScheduledChecks();

        Gate::policy(ConsentRecord::class, ConsentRecordPolicy::class);

        $this->registerMorphMap();
        $this->registerBladeComponents();
        $this->registerLivewireComponents();
    }

    /**
     * The automated checks run daily by default; set
     * laranail.ai-compliance.checks.schedule to null to opt out.
     */
    private function registerScheduledChecks(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $cadence = $this->app->make(ConfigRepository::class)->get('laranail.ai-compliance.checks.schedule', 'daily');

            if ($cadence === 'daily') {
                $schedule->command('laranail::ai-compliance.audit')->daily();
            }
        });
    }

    private function registerBladeComponents(): void
    {
        Blade::componentNamespace('Simtabi\\Laranail\\AiCompliance\\View\\Components', 'ai-compliance');

        // spec-shaped alias: <x-ai-compliance::gate> next to <x-ai-compliance::consent-gate>
        Blade::component('ai-compliance::gate', ConsentGate::class);
    }

    /**
     * The livewire components exist only when the host installed livewire
     * (a suggest dependency); the package boots cleanly without it.
     */
    private function registerLivewireComponents(): void
    {
        if (! class_exists(Livewire::class) || ! $this->app->bound('livewire')) {
            return;
        }

        if (! (bool) $this->app->make(ConfigRepository::class)->get('laranail.ai-compliance.livewire.enabled', true)) {
            return;
        }

        Livewire::component('ai-compliance.consent-preferences', ConsentPreferences::class);
        Livewire::component('ai-compliance.reconsent-prompt', ReconsentPrompt::class);
    }

    /**
     * Register the short 'user' morph alias (non-enforcing: hosts opting
     * into Relation::requireMorphMap() do so themselves — a package forcing
     * it globally would break unrelated host morphs).
     */
    private function registerMorphMap(): void
    {
        $config = $this->app->make(ConfigRepository::class);

        $userModel = $config->get('laranail.ai-compliance.user_model')
            ?? $config->get('auth.providers.users.model');

        /** @var array<string, class-string<Model>> $map */
        $map = [];

        if (is_string($userModel) && is_subclass_of($userModel, Model::class)) {
            $map['user'] = $userModel;
        }

        $extra = $config->get('laranail.ai-compliance.morph_map', []);

        foreach (is_array($extra) ? $extra : [] as $alias => $class) {
            if (is_string($alias) && is_string($class) && is_subclass_of($class, Model::class)) {
                $map[$alias] = $class;
            }
        }

        if ($map !== []) {
            Relation::morphMap($map);
        }
    }

    private function defaultLocale(): string
    {
        $default = $this->app->make(ConfigRepository::class)->get('laranail.ai-compliance.locales.default', 'en');

        return is_string($default) ? $default : 'en';
    }
}
