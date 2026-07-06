<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\AiCompliance\Http\Controllers\BootController;
use Simtabi\Laranail\AiCompliance\Http\Controllers\PolicyController;

if (! config('laranail.ai-compliance.routes.enabled', true)) {
    return;
}

$prefix = config('laranail.ai-compliance.routes.prefix', 'ai-compliance');
$middleware = config('laranail.ai-compliance.routes.middleware', ['web']);
$rateLimit = config('laranail.ai-compliance.routes.rate_limit', '60,1');

Route::prefix(is_string($prefix) ? $prefix : 'ai-compliance')
    ->middleware([
        ...(is_array($middleware) ? $middleware : ['web']),
        'throttle:' . (is_string($rateLimit) ? $rateLimit : '60,1'),
    ])
    ->name('laranail.ai-compliance.')
    ->group(function (): void {
        Route::get('boot', BootController::class)->name('boot');
        Route::get('policies/{slug}', [PolicyController::class, 'show'])->name('policy');
    });
