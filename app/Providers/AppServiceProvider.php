<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Global API rate limiter: 60 requests/minute per client IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-ID') ?: 'req_throttle';
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'RATE_LIMIT_EXCEEDED',
                            'message' => 'API rate limit exceeded. Too many requests have been submitted.',
                            'details' => [
                                'limit' => 60,
                                'window' => '1 minute',
                                'retry_after_seconds' => $headers['Retry-After'] ?? 60,
                            ],
                            'timestamp' => now()->toIso8601String(),
                            'request_id' => $requestId,
                        ],
                    ], 429, $headers);
                });
        });

        // Strict rate limiter for laboratory demonstration: 5 requests/minute per IP
        RateLimiter::for('strict-test', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-ID') ?: 'req_throttle_strict';
                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'RATE_LIMIT_EXCEEDED',
                            'message' => 'Strict rate limit quota reached (5 requests per minute). Please wait before retrying.',
                            'details' => [
                                'limit' => 5,
                                'window' => '1 minute',
                                'retry_after_seconds' => $headers['Retry-After'] ?? 60,
                            ],
                            'timestamp' => now()->toIso8601String(),
                            'request_id' => $requestId,
                        ],
                    ], 429, $headers);
                });
        });
    }
}
