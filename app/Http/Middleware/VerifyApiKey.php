<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Known test keys for laboratory demonstrations.
     */
    protected const VALID_KEYS = [
        'bobacraft-admin-key-2026' => ['role' => 'admin', 'name' => 'Store Manager'],
        'bobacraft-barista-key-2026' => ['role' => 'barista', 'name' => 'Barista Station'],
        'bobacraft-customer-key-2026' => ['role' => 'kiosk', 'name' => 'Self-Service Kiosk'],
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $requiredRole
     */
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        $apiKey = $request->header('X-API-Key');

        // Also accept Authorization: Bearer <token>
        if (! $apiKey && $authHeader = $request->header('Authorization')) {
            if (str_starts_with($authHeader, 'Bearer ')) {
                $apiKey = substr($authHeader, 7);
            }
        }

        $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-ID') ?: 'req_auth';

        if (! $apiKey || ! array_key_exists($apiKey, self::VALID_KEYS)) {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'API authentication failed. A valid X-API-Key or Bearer token header is required.',
                    'details' => [
                        'hint' => 'Provide a valid header: X-API-Key: bobacraft-admin-key-2026',
                    ],
                    'timestamp' => now()->toIso8601String(),
                    'request_id' => $requestId,
                ],
            ], 401);
        }

        $client = self::VALID_KEYS[$apiKey];
        $request->attributes->set('api_client', $client);

        if ($requiredRole && $client['role'] !== $requiredRole && $client['role'] !== 'admin') {
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => "Forbidden: The authenticated key '{$client['name']}' lacks the '{$requiredRole}' role.",
                    'details' => [
                        'required_role' => $requiredRole,
                        'current_role' => $client['role'],
                    ],
                    'timestamp' => now()->toIso8601String(),
                    'request_id' => $requestId,
                ],
            ], 403);
        }

        return $next($request);
    }
}
