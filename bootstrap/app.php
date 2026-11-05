<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.log' => \App\Http\Middleware\DiagnosticRequestLogging::class,
            'api.key' => \App\Http\Middleware\VerifyApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Custom predictable JSON error format for API requests
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-ID') ?: 'req_err';

            // 0. Explicit HTTP Response Exceptions (e.g., custom rate limiter responses or FormRequest responses)
            if ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                return $e->getResponse();
            }

            // 1. Validation Exception (422) - compatible with Laravel assertions and API laboratory schema
            if ($e instanceof ValidationException) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'The given request payload failed input validation rules.',
                    'errors' => $e->errors(),
                    'error' => [
                        'code' => 'VALIDATION_FAILED',
                        'message' => 'The given request payload failed input validation rules.',
                        'details' => $e->errors(),
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $requestId,
                    ],
                ], 422);
            }

            // 2. Not Found Exceptions (404)
            if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'The requested endpoint or resource was not found.',
                    'error' => [
                        'code' => 'RESOURCE_NOT_FOUND',
                        'message' => 'The requested endpoint or resource was not found.',
                        'details' => [
                            'path' => $request->path(),
                        ],
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $requestId,
                    ],
                ], 404);
            }

            // 3. Rate Limit / Throttled (429)
            if ($e instanceof ThrottleRequestsException) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Too many requests. Please wait before attempting this action again.',
                    'error' => [
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'message' => 'Too many requests. Please wait before attempting this action again.',
                        'details' => [
                            'retry_after_seconds' => (int) $retryAfter,
                        ],
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $requestId,
                    ],
                ], 429, $e->getHeaders());
            }

            // 4. Unauthorized / Forbidden (401 / 403)
            if ($e instanceof UnauthorizedHttpException) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Authentication credentials missing or invalid.',
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => $e->getMessage() ?: 'Authentication credentials missing or invalid.',
                        'details' => null,
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $requestId,
                    ],
                ], 401);
            }

            if ($e instanceof AccessDeniedHttpException) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'You do not have permission to access this resource.',
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => $e->getMessage() ?: 'You do not have permission to access this resource.',
                        'details' => null,
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $requestId,
                    ],
                ], 403);
            }

            // 5. Generic HTTP Exceptions
            if ($e instanceof HttpException) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'An HTTP error occurred.',
                    'error' => [
                        'code' => 'HTTP_ERROR_' . $e->getStatusCode(),
                        'message' => $e->getMessage() ?: 'An HTTP error occurred.',
                        'details' => null,
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $requestId,
                    ],
                ], $e->getStatusCode());
            }

            // 6. Generic Internal Server Error (500)
            $status = 500;
            return new JsonResponse([
                'success' => false,
                'message' => 'An unexpected internal server error occurred.',
                'error' => [
                    'code' => 'INTERNAL_SERVER_ERROR',
                    'message' => config('app.debug') && ! app()->environment('production')
                        ? $e->getMessage()
                        : 'An unexpected internal server error occurred. Please contact the administrator.',
                    'details' => config('app.debug') && ! app()->environment('production')
                        ? [
                            'exception' => get_class($e),
                            'file' => basename($e->getFile()),
                            'line' => $e->getLine(),
                        ]
                        : null,
                    'timestamp' => now()->toIso8601String(),
                    'request_id' => $requestId,
                ],
            ], $status);
        });
    })->create();
