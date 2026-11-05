<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ApiLaboratoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Successful paginated & optimized order listing.
     */
    public function test_api_orders_index_returns_paginated_optimized_response(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Order::create([
                'customer_name' => "Cup #$i — Boba Milk Tea",
                'status' => $i % 2 === 0 ? Order::STATUS_PENDING : Order::STATUS_READY,
            ]);
        }

        $response = $this->getJson('/api/v1/orders?per_page=10&page=1');

        $response->assertStatus(200);
        $response->assertHeader('X-Optimization-Mode', 'optimized');
        $response->assertHeader('X-Records-Returned', '10');
        $response->assertHeader('X-Request-ID');
        $response->assertHeader('X-Response-Time-Ms');
        $response->assertJson([
            'success' => true,
            'meta' => [
                'mode' => 'optimized',
                'optimization_applied' => true,
                'per_page' => 10,
                'total_records' => 30,
            ],
        ]);
        $this->assertCount(10, $response->json('data'));
    }

    /**
     * Test 2: Demonstrable original mode comparison vs optimized mode.
     */
    public function test_api_orders_index_original_mode_returns_unpaginated_full_dataset(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            Order::create([
                'customer_name' => "Cup #$i — Taro Milk Tea",
                'status' => Order::STATUS_PENDING,
            ]);
        }

        $response = $this->getJson('/api/v1/orders?mode=original');

        $response->assertStatus(200);
        $response->assertHeader('X-Optimization-Mode', 'original');
        $response->assertHeader('X-Records-Returned', '15');
        $response->assertJson([
            'success' => true,
            'meta' => [
                'mode' => 'original',
                'optimization_applied' => false,
                'record_count' => 15,
            ],
        ]);
        $this->assertCount(15, $response->json('data'));
    }

    /**
     * Test 3: Read-heavy cached statistics endpoint and cache hit/miss behavior.
     */
    public function test_api_orders_stats_caching_and_hit_miss_behavior(): void
    {
        Cache::flush();

        Order::create(['customer_name' => 'Cup #1', 'status' => Order::STATUS_PENDING]);
        Order::create(['customer_name' => 'Cup #2', 'status' => Order::STATUS_PREPARING]);

        // First call: Cache MISS
        $response1 = $this->getJson('/api/v1/orders/stats');
        $response1->assertStatus(200);
        $response1->assertHeader('X-Cache', 'MISS');
        $response1->assertJson([
            'success' => true,
            'data' => [
                'total' => 2,
                'pending' => 1,
                'preparing' => 1,
            ],
        ]);

        // Second call: Cache HIT
        $response2 = $this->getJson('/api/v1/orders/stats');
        $response2->assertStatus(200);
        $response2->assertHeader('X-Cache', 'HIT');
    }

    /**
     * Test 4: Cache invalidation upon order creation and status update.
     */
    public function test_cache_invalidation_when_creating_or_updating_order(): void
    {
        Cache::flush();
        Event::fake([OrderCreated::class, OrderStatusUpdated::class]);

        $order = Order::create(['customer_name' => 'Cup #100', 'status' => Order::STATUS_PENDING]);

        // Prime cache
        $this->getJson('/api/v1/orders/stats')->assertHeader('X-Cache', 'MISS');
        $this->getJson('/api/v1/orders/stats')->assertHeader('X-Cache', 'HIT');

        // Creating order invalidates cache
        $this->postJson('/api/v1/orders', [
            'customer_name' => 'Angela Reyes',
            'drink' => 'Brown Sugar Boba',
            'size' => 'Large',
            'sugar_level' => '50%',
        ])->assertStatus(201);

        // Next read must be a MISS (cache was successfully invalidated)
        $this->getJson('/api/v1/orders/stats')->assertHeader('X-Cache', 'MISS');

        // Prime cache again
        $this->getJson('/api/v1/orders/stats')->assertHeader('X-Cache', 'HIT');

        // Updating order invalidates cache
        $this->patchJson("/api/v1/orders/{$order->id}", [
            'status' => Order::STATUS_PREPARING,
        ])->assertStatus(200);

        // Next read must be a MISS again
        $this->getJson('/api/v1/orders/stats')->assertHeader('X-Cache', 'MISS');
    }

    /**
     * Test 5: Input validation with predictable JSON error schema (422).
     */
    public function test_order_creation_validation_failure_returns_predictable_json_error(): void
    {
        $response = $this->postJson('/api/v1/orders', [
            'customer_name' => '',
            'size' => 'MegaGiant', // Invalid size
            'sugar_level' => '200%', // Invalid sugar
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'message' => 'The given request payload failed input validation rules.',
            ],
        ]);
        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
                'details',
                'timestamp',
                'request_id',
            ],
        ]);
    }

    /**
     * Test 6: Missing resource returns predictable 404 JSON schema.
     */
    public function test_missing_order_returns_predictable_404_json_error(): void
    {
        $response = $this->getJson('/api/v1/orders/999999');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'RESOURCE_NOT_FOUND',
                'details' => [
                    'resource' => 'Order',
                    'id' => 999999,
                ],
            ],
        ]);
    }

    /**
     * Test 7: Authentication failure (missing API key) returns 401 Unauthorized JSON schema.
     */
    public function test_admin_route_without_api_key_returns_401_unauthorized(): void
    {
        $response = $this->deleteJson('/api/v1/admin/orders/1');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
            ],
        ]);
        $response->assertJsonStructure([
            'success',
            'error' => ['code', 'message', 'details', 'timestamp', 'request_id'],
        ]);
    }

    /**
     * Test 8: Authorization failure (insufficient role) returns 403 Forbidden JSON schema.
     */
    public function test_admin_route_with_non_admin_key_returns_403_forbidden(): void
    {
        // Pass a valid kiosk key which lacks admin role
        $response = $this->withHeaders([
            'X-API-Key' => 'bobacraft-customer-key-2026',
        ])->deleteJson('/api/v1/admin/orders/1');

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
            ],
        ]);
    }

    /**
     * Test 9: Admin authorized request succeeds with valid admin key.
     */
    public function test_admin_route_with_valid_admin_key_succeeds(): void
    {
        $order = Order::create(['customer_name' => 'Cup #999', 'status' => Order::STATUS_PENDING]);

        $response = $this->withHeaders([
            'X-API-Key' => 'bobacraft-admin-key-2026',
        ])->deleteJson("/api/v1/admin/orders/{$order->id}");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    /**
     * Test 10: Strict rate limiting returns HTTP 429 Too Many Requests.
     */
    public function test_strict_rate_limiting_triggers_429_too_many_requests(): void
    {
        // Strict limit is 5 requests per minute
        for ($i = 1; $i <= 5; $i++) {
            $res = $this->getJson('/api/v1/rate-limit-test');
            $res->assertStatus(200);
        }

        // 6th request triggers rate limit 429
        $throttled = $this->getJson('/api/v1/rate-limit-test');
        $throttled->assertStatus(429);
        $throttled->assertHeader('Retry-After');
        $throttled->assertJson([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
            ],
        ]);
    }

    /**
     * Test 11: Diagnostic request logging middleware adds diagnostic headers.
     */
    public function test_diagnostic_logging_headers_are_present_on_responses(): void
    {
        $response = $this->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
        $this->assertNotEmpty($response->headers->get('X-Response-Time-Ms'));
        $this->assertNotEmpty($response->headers->get('X-Memory-Peak-MB'));
        $this->assertNotEmpty($response->headers->get('X-DB-Queries'));

        // Verify diagnostic log file exists and is written
        $logPath = storage_path('logs/api-diagnostics.log');
        $this->assertFileExists($logPath);
        $logContent = file_get_contents($logPath);
        $this->assertStringContainsString($response->headers->get('X-Request-ID'), $logContent);
    }

    /**
     * Test 12: Sensitive credentials are never exposed in diagnostic logs or error payloads.
     */
    public function test_sensitive_headers_are_not_logged_in_diagnostics(): void
    {
        $sensitiveKey = 'bobacraft-admin-key-2026';

        $this->withHeaders([
            'X-API-Key' => $sensitiveKey,
            'Authorization' => "Bearer $sensitiveKey",
        ])->getJson('/api/v1/admin/verify');

        $logPath = storage_path('logs/api-diagnostics.log');
        $logContent = file_get_contents($logPath);

        // API Key and tokens must not be logged in plaintext
        $this->assertStringNotContainsString('"x-api-key"', strtolower($logContent));
        $this->assertStringNotContainsString('"authorization"', strtolower($logContent));
    }
}
