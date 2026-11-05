<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DiagnosticRequestLogging
{
    /**
     * Handle an incoming request and record diagnostic metrics.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Generate or adopt Request ID
        $requestId = $request->header('X-Request-ID') ?: 'req_' . Str::random(16);
        $request->attributes->set('request_id', $requestId);

        // Enable query log for performance measurement
        DB::enableQueryLog();

        /** @var Response $response */
        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $peakMemoryMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $memoryDeltaMb = round((memory_get_usage() - $startMemory) / 1024 / 1024, 2);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $queryTimeMs = round(array_sum(array_column($queries, 'time')), 2);

        // Attach diagnostic headers to the response
        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Response-Time-Ms', (string) $durationMs);
        $response->headers->set('X-Memory-Peak-MB', (string) $peakMemoryMb);
        $response->headers->set('X-DB-Queries', (string) $queryCount);
        $response->headers->set('X-DB-Query-Time-Ms', (string) $queryTimeMs);

        // Sanitize headers and inputs (prevent sensitive credential exposure)
        $sanitizedHeaders = $request->headers->all();
        unset(
            $sanitizedHeaders['authorization'],
            $sanitizedHeaders['x-api-key'],
            $sanitizedHeaders['cookie'],
            $sanitizedHeaders['php-auth-pw']
        );

        $sanitizedInput = $request->except(['password', 'password_confirmation', 'token', 'api_key', 'secret']);

        // Structured diagnostic record
        $diagnosticData = [
            'timestamp' => now()->toIso8601String(),
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'peak_memory_mb' => $peakMemoryMb,
            'memory_delta_mb' => $memoryDeltaMb,
            'db_query_count' => $queryCount,
            'db_time_ms' => $queryTimeMs,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'cache_status' => $response->headers->get('X-Cache', 'N/A'),
            'inputs' => $sanitizedInput,
        ];

        // Write structured diagnostic log to storage/logs/api-diagnostics.log
        $logPath = storage_path('logs/api-diagnostics.log');
        @file_put_contents(
            $logPath,
            json_encode($diagnosticData, JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        return $response;
    }
}
