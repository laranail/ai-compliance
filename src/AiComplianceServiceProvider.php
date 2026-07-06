<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Override;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Consent\ConsentTypes;
use Simtabi\Laranail\AiCompliance\Consent\GuestKeys;
use Simtabi\Laranail\AiCompliance\Console\Commands\InstallCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicyPublishCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicyShowCommand;
use Simtabi\Laranail\AiCompliance\Console\Commands\PolicySyncCommand;
use Simtabi\Laranail\AiCompliance\Events\ConsentRecorded;
use Simtabi\Laranail\AiCompliance\Events\ConsentWithdrawn;
use Simtabi\Laranail\AiCompliance\Events\PoliciesSynced;
use Simtabi\Laranail\AiCompliance\Events\PolicyPublished;
use Simtabi\Laranail\AiCompliance\Http\Middleware\EnsureConsent;
use Simtabi\Laranail\AiCompliance\Listeners\FlushCompiledPolicyCache;
use Simtabi\Laranail\AiCompliance\Listeners\RecordConsentActivity;
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
        $this->app->singleton(ConsentTypes::class);
        $this->app->singleton(GuestKeys::class);
        $this->app->singleton(ActivityRecorder::class);
        $this->app->singleton(ConsentManager::class);
        $this->app->singleton(BootPayload::class);
        $this->app->singleton(AiCompliance::class);
    }

    #[Override]
    public function packageBooted(): void
    {
        $events = $this->app->make(Dispatcher::class);

        $events->listen(PolicyPublished::class, FlushCompiledPolicyCache::class);
        $events->listen(PoliciesSynced::class, FlushCompiledPolicyCache::class);
        $events->listen(ConsentRecorded::class, RecordConsentActivity::class);
        $events->listen(ConsentWithdrawn::class, RecordConsentActivity::class);

        $this->app->make(Router::class)->aliasMiddleware('ai.consent', EnsureConsent::class);

        Gate::policy(ConsentRecord::class, ConsentRecordPolicy::class);

        $this->registerMorphMap();
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
