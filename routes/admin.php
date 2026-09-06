<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\ExportController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\ReportController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\FeatureController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\ActivityController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\ProviderController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\ChecklistController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\DashboardController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\PolicyDraftController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\PolicyPreviewController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\ClassificationController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\PolicyDocumentController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\PolicyStalenessController;

if (! config('laranail.ai-compliance.admin_routes.enabled', true)) {
    return;
}

$prefix = config('laranail.ai-compliance.admin_routes.prefix', 'ai-compliance/admin');
$middleware = config('laranail.ai-compliance.admin_routes.middleware', ['web', 'auth']);

Route::prefix(is_string($prefix) ? $prefix : 'ai-compliance/admin')
    ->middleware(is_array($middleware) ? $middleware : ['web', 'auth'])
    ->name('laranail.ai-compliance.admin.')
    ->group(function (): void {
        Route::middleware('can:ai-compliance:audit')->group(function (): void {
            Route::get('dashboard', DashboardController::class)->name('dashboard');
            Route::get('policies', [PolicyDocumentController::class, 'index'])->name('policies.index');
            Route::get('policies/staleness', PolicyStalenessController::class)->name('policies.staleness');
            Route::get('policies/{slug}', [PolicyDocumentController::class, 'show'])->name('policies.show');
            Route::get('checklist', [ChecklistController::class, 'index'])->name('checklist.index');
            Route::get('classification', [ClassificationController::class, 'index'])->name('classification.index');
            Route::get('providers', [ProviderController::class, 'index'])->name('providers.index');
            Route::get('features', [FeatureController::class, 'index'])->name('features.index');
            Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');
            Route::get('activity/chain', [ActivityController::class, 'chain'])->name('activity.chain');
            Route::get('report', ReportController::class)->name('report');
        });

        // log exports are the sensitive ability and carry their own gate
        Route::middleware('can:ai-compliance:export')->group(function (): void {
            Route::get('exports/consents', [ExportController::class, 'consents'])->name('exports.consents');
            Route::get('exports/activity', [ExportController::class, 'activity'])->name('exports.activity');
        });

        Route::middleware('can:ai-compliance:manage')->group(function (): void {
            Route::post('policies/preview', PolicyPreviewController::class)->name('policies.preview');
            Route::post('policies/{slug}/draft', [PolicyDraftController::class, 'store'])->name('policies.draft.store');
            Route::put('policies/{slug}/draft/translations/{locale}', [PolicyDraftController::class, 'updateTranslation'])->name('policies.draft.translations.update');
            Route::post('policies/{slug}/draft/publish', [PolicyDraftController::class, 'publish'])->name('policies.draft.publish');
            Route::post('checklist/run', [ChecklistController::class, 'run'])->name('checklist.run');
            Route::post('checklist/{key}/evidence', [ChecklistController::class, 'verify'])->name('checklist.verify');
            Route::put('classification', [ClassificationController::class, 'store'])->name('classification.store');
            Route::post('providers', [ProviderController::class, 'store'])->name('providers.store');
            Route::put('providers/{provider}', [ProviderController::class, 'update'])->name('providers.update');
            Route::delete('providers/{provider}', [ProviderController::class, 'destroy'])->name('providers.destroy');
            Route::put('features/{feature}', [FeatureController::class, 'update'])->name('features.update');
        });
    });
