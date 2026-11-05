<?php

use App\Http\Controllers\Api\OrderApiController;
use App\Http\Middleware\DiagnosticRequestLogging;
use App\Http\Middleware\VerifyApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - BobaCraft Milk Tea Bar
|--------------------------------------------------------------------------
|
| Laboratory Activity: API Performance, Reliability, and Monitoring
| All endpoints under /api/v1 inherit diagnostic logging, predictable
| JSON error schemas, rate limiting, and performance optimizations.
|
*/

Route::prefix('v1')->middleware([DiagnosticRequestLogging::class])->group(function () {

    // =========================================================================
    // Read-Heavy & Optimizable Endpoints
    // =========================================================================
    
    // Core listing endpoint (supports ?mode=optimized and ?mode=original for comparison)
    Route::get('/orders', [OrderApiController::class, 'index'])->name('api.orders.index');

    // Read-heavy cached statistics endpoint (with X-Cache: HIT / MISS)
    Route::get('/orders/stats', [OrderApiController::class, 'stats'])->name('api.orders.stats');

    // Performance telemetry benchmark endpoint (runs side-by-side comparison on 10k records)
    Route::get('/orders/benchmark', [OrderApiController::class, 'benchmark'])->name('api.orders.benchmark');

    // Single order lookup (demonstrates 404 predictable JSON error handling)
    Route::get('/orders/{id}', [OrderApiController::class, 'show'])->whereNumber('id')->name('api.orders.show');

    // Create new order (demonstrates 422 input validation & cache invalidation)
    Route::post('/orders', [OrderApiController::class, 'store'])->name('api.orders.store');

    // Update order status (demonstrates 422 validation, real-time broadcast & cache invalidation)
    Route::patch('/orders/{id}', [OrderApiController::class, 'update'])->whereNumber('id')->name('api.orders.update');

    // =========================================================================
    // Rate Limiting Verification Endpoint
    // =========================================================================
    // Strict rate limiter (5 requests / min) to reliably verify HTTP 429 responses
    Route::middleware('throttle:strict-test')->get('/rate-limit-test', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Request within allowed rate limit quota.',
            'meta' => [
                'limit' => 5,
                'ip' => $request->ip(),
                'request_id' => $request->attributes->get('request_id'),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    })->name('api.rate_limit_test');

    // =========================================================================
    // Authentication & Authorization Protected Routes (401 / 403 Verification)
    // =========================================================================
    Route::middleware([VerifyApiKey::class . ':admin'])->prefix('admin')->group(function () {
        Route::delete('/orders/{id}', [OrderApiController::class, 'destroy'])
            ->whereNumber('id')
            ->name('api.admin.orders.destroy');
            
        Route::get('/verify', function (Request $request) {
            return response()->json([
                'success' => true,
                'message' => 'Admin authorization verified successfully.',
                'authenticated_as' => $request->attributes->get('api_client'),
            ]);
        })->name('api.admin.verify');
    });
});
