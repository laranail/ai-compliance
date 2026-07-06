<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\PolicyDocumentController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\PolicyDraftController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\Admin\PolicyPreviewController;
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
            Route::get('policies', [PolicyDocumentController::class, 'index'])->name('policies.index');
            Route::get('policies/staleness', PolicyStalenessController::class)->name('policies.staleness');
            Route::get('policies/{slug}', [PolicyDocumentController::class, 'show'])->name('policies.show');
        });

        Route::middleware('can:ai-compliance:manage')->group(function (): void {
            Route::post('policies/preview', PolicyPreviewController::class)->name('policies.preview');
            Route::post('policies/{slug}/draft', [PolicyDraftController::class, 'store'])->name('policies.draft.store');
            Route::put('policies/{slug}/draft/translations/{locale}', [PolicyDraftController::class, 'updateTranslation'])->name('policies.draft.translations.update');
            Route::post('policies/{slug}/draft/publish', [PolicyDraftController::class, 'publish'])->name('policies.draft.publish');
        });
    });
