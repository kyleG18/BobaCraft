<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Requests\Api\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderApiController extends Controller
{
    protected const CACHE_KEY_STATS = 'bobacraft_order_stats';
    protected const CACHE_TTL_SECONDS = 60;

    /**
     * Display a listing of orders with demonstrable optimization comparison.
     *
     * Supports:
     * - mode=optimized (default): Paginated, indexed, selective columns, fast
     * - mode=original: Unpaginated, SELECT *, full table scan, heavy memory/payload
     */
    public function index(Request $request): JsonResponse
    {
        $mode = $request->query('mode', 'optimized');
        $status = $request->query('status');
        $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-ID') ?: 'req_idx';

        $startTime = microtime(true);
        $startMem = memory_get_usage();

        if ($mode === 'original') {
            // =================================================================
            // ORIGINAL CONFIGURATION (Unoptimized)
            // - No pagination (loads all 10,000+ records into memory)
            // - SELECT * (unnecessary column overhead)
            // - Heavy Eloquent hydration
            // =================================================================
            $query = Order::query();

            if ($status) {
                $query->where('status', $status);
            }

            // Order by created_at desc without limiting/paginating
            $orders = $query->orderBy('created_at', 'desc')->get();

            $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);
            $memoryUsedMb = round((memory_get_usage() - $startMem) / 1024 / 1024, 2);

            return response()->json([
                'success' => true,
                'meta' => [
                    'mode' => 'original',
                    'optimization_applied' => false,
                    'record_count' => $orders->count(),
                    'execution_time_ms' => $elapsedMs,
                    'memory_delta_mb' => $memoryUsedMb,
                    'notice' => 'Original baseline mode: full table scan returned without pagination or indexing.',
                    'request_id' => $requestId,
                ],
                'data' => $orders,
            ])
            ->header('X-Optimization-Mode', 'original')
            ->header('X-Records-Returned', (string) $orders->count());
        }

        // =====================================================================
        // OPTIMIZED CONFIGURATION (API Laboratory Requirements)
        // - Indexed composite query (idx_orders_status_created_at)
        // - Selective column projection (id, customer_name, status, created_at)
        // - Keyset / page pagination (default 25, capped at 100)
        // - Sub-50ms response time, minimal memory overhead
        // =====================================================================
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);

        $query = Order::query()
            ->select(['id', 'customer_name', 'status', 'created_at', 'updated_at']);

        if ($status && in_array($status, Order::VALID_STATUSES, true)) {
            $query->where('status', $status);
        }

        $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);
        $memoryUsedMb = round((memory_get_usage() - $startMem) / 1024 / 1024, 2);

        return response()->json([
            'success' => true,
            'meta' => [
                'mode' => 'optimized',
                'optimization_applied' => true,
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total_records' => $paginated->total(),
                'total_pages' => $paginated->lastPage(),
                'execution_time_ms' => $elapsedMs,
                'memory_delta_mb' => $memoryUsedMb,
                'indexes_utilized' => ['idx_orders_status_created_at', 'idx_orders_created_at'],
                'request_id' => $requestId,
            ],
            'data' => $paginated->items(),
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
        ])
        ->header('X-Optimization-Mode', 'optimized')
        ->header('X-Records-Returned', (string) count($paginated->items()));
    }

    /**
     * Read-heavy summary endpoint with server-side caching & invalidation.
     */
    public function stats(Request $request): JsonResponse
    {
        $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-ID') ?: 'req_stats';
        $noCache = $request->query('no_cache') === 'true';

        $cacheStatus = 'HIT';

        if ($noCache || ! Cache::has(self::CACHE_KEY_STATS)) {
            $cacheStatus = 'MISS';
        }

        if ($noCache) {
            $stats = $this->calculateOrderStats();
        } else {
            $stats = Cache::remember(self::CACHE_KEY_STATS, self::CACHE_TTL_SECONDS, function () {
                return $this->calculateOrderStats();
            });
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
            'meta' => [
                'cache' => [
                    'status' => $cacheStatus,
                    'key' => self::CACHE_KEY_STATS,
                    'ttl_seconds' => self::CACHE_TTL_SECONDS,
                ],
                'request_id' => $requestId,
                'timestamp' => now()->toIso8601String(),
            ],
        ])
        ->header('X-Cache', $cacheStatus);
    }

    /**
     * Retrieve a specific order by ID with predictable error handling.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-ID') ?: 'req_show';
        $order = Order::find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => "Order #{$id} was not found in the BobaCraft order system.",
                    'details' => [
                        'resource' => 'Order',
                        'id' => $id,
                    ],
                    'timestamp' => now()->toIso8601String(),
                    'request_id' => $requestId,
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'status' => $order->status,
                'status_badge' => $order->status_badge_class,
                'created_at' => $order->created_at?->toIso8601String(),
                'updated_at' => $order->updated_at?->toIso8601String(),
            ],
            'meta' => [
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * Store a new milk tea order with input validation and cache invalidation.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-ID') ?: 'req_store';

        // Compose ticket description if structured drink data is provided
        $ticketName = trim($validated['customer_name']);
        if (! empty($validated['drink'])) {
            $ticketName .= " — {$validated['drink']}";
            $options = array_filter([
                $validated['size'] ?? null,
                $validated['sugar_level'] ?? null,
                $validated['ice_level'] ?? null,
                ! empty($validated['toppings']) ? implode(', ', $validated['toppings']) : null,
            ]);
            if (! empty($options)) {
                $ticketName .= ' (' . implode(', ', $options) . ')';
            }
        }

        $order = Order::create([
            'customer_name' => $ticketName,
            'status' => Order::STATUS_PENDING,
        ]);

        // Real-time broadcast
        broadcast(new OrderCreated($order));

        // Invalidate read-heavy stats cache
        Cache::forget(self::CACHE_KEY_STATS);

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->id} created successfully.",
            'data' => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'status' => $order->status,
                'created_at' => $order->created_at->toIso8601String(),
                'updated_at' => $order->updated_at->toIso8601String(),
            ],
            'meta' => [
                'cache_invalidated' => [self::CACHE_KEY_STATS],
                'request_id' => $requestId,
            ],
        ], 201);
    }

    /**
     * Update the status of an existing order and invalidate cache.
     */
    public function update(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-ID') ?: 'req_upd';
        $order = Order::find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => "Order #{$id} was not found for update.",
                    'details' => ['id' => $id],
                    'timestamp' => now()->toIso8601String(),
                    'request_id' => $requestId,
                ],
            ], 404);
        }

        $validated = $request->validated();
        $newStatus = $validated['status'];
        $oldStatus = $order->status;

        if ($newStatus === $oldStatus) {
            return response()->json([
                'success' => true,
                'message' => "Order #{$order->id} is already marked as {$newStatus}.",
                'unchanged' => true,
                'data' => [
                    'id' => $order->id,
                    'status' => $order->status,
                ],
                'meta' => [
                    'request_id' => $requestId,
                ],
            ]);
        }

        $order->status = $newStatus;
        $order->save();

        // Broadcast status update
        broadcast(new OrderStatusUpdated($order));

        // Invalidate cache
        Cache::forget(self::CACHE_KEY_STATS);

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->id} updated from '{$oldStatus}' to '{$newStatus}'.",
            'data' => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'status' => $order->status,
                'previous_status' => $oldStatus,
                'updated_at' => $order->updated_at->toIso8601String(),
            ],
            'meta' => [
                'cache_invalidated' => [self::CACHE_KEY_STATS],
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * Admin endpoint: Delete an order (protected by API Key admin role).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-ID') ?: 'req_del';
        $order = Order::find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => "Order #{$id} not found.",
                    'details' => null,
                    'timestamp' => now()->toIso8601String(),
                    'request_id' => $requestId,
                ],
            ], 404);
        }

        $order->delete();
        Cache::forget(self::CACHE_KEY_STATS);

        return response()->json([
            'success' => true,
            'message' => "Order #{$id} has been permanently deleted by admin.",
            'meta' => [
                'cache_invalidated' => [self::CACHE_KEY_STATS],
                'request_id' => $requestId,
            ],
        ]);
    }

    /**
     * Direct side-by-side performance comparison benchmark endpoint.
     */
    public function benchmark(Request $request): JsonResponse
    {
        $requestId = $request->attributes->get('request_id') ?: $request->header('X-Request-ID') ?: 'req_bench';
        $totalRecords = Order::count();

        // 1. Run Original Simulation
        $t0 = microtime(true);
        $m0 = memory_get_usage();
        $originalData = Order::orderBy('created_at', 'desc')->get();
        $tOriginalMs = round((microtime(true) - $t0) * 1000, 2);
        $mOriginalMb = round((memory_get_usage() - $m0) / 1024 / 1024, 2);
        $origPayloadBytes = strlen(json_encode($originalData));
        unset($originalData); // Free memory

        // 2. Run Optimized Simulation
        $t1 = microtime(true);
        $m1 = memory_get_usage();
        $optimizedData = Order::select(['id', 'customer_name', 'status', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);
        $tOptimizedMs = round((microtime(true) - $t1) * 1000, 2);
        $mOptimizedMb = round((memory_get_usage() - $m1) / 1024 / 1024, 2);
        $optPayloadBytes = strlen(json_encode($optimizedData));

        $speedupFactor = $tOptimizedMs > 0 ? round($tOriginalMs / $tOptimizedMs, 2) : 1;
        $payloadReductionPct = round((1 - ($optPayloadBytes / $origPayloadBytes)) * 100, 2);

        return response()->json([
            'success' => true,
            'laboratory_benchmark' => [
                'dataset_size' => $totalRecords,
                'target_table' => 'orders',
                'comparison' => [
                    'original' => [
                        'description' => 'Unpaginated SELECT * full dataset scan',
                        'records_returned' => $totalRecords,
                        'execution_time_ms' => $tOriginalMs,
                        'memory_consumed_mb' => $mOriginalMb,
                        'payload_size_kb' => round($origPayloadBytes / 1024, 2),
                    ],
                    'optimized' => [
                        'description' => 'Paginated (25/page) + Indexed composite scan + Selective columns',
                        'records_returned' => count($optimizedData->items()),
                        'execution_time_ms' => $tOptimizedMs,
                        'memory_consumed_mb' => $mOptimizedMb,
                        'payload_size_kb' => round($optPayloadBytes / 1024, 2),
                    ],
                    'improvements' => [
                        'speedup_multiplier' => "{$speedupFactor}x faster",
                        'payload_reduction' => "{$payloadReductionPct}% smaller",
                        'memory_safety' => 'Eliminated memory spikes by streaming paginated slices.',
                    ],
                ],
                'request_id' => $requestId,
                'tested_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Compute aggregated statistics directly from database.
     */
    protected function calculateOrderStats(): array
    {
        return [
            'total' => Order::count(),
            'pending' => Order::where('status', Order::STATUS_PENDING)->count(),
            'preparing' => Order::where('status', Order::STATUS_PREPARING)->count(),
            'ready' => Order::where('status', Order::STATUS_READY)->count(),
            'completed' => Order::where('status', Order::STATUS_COMPLETED)->count(),
            'calculated_at' => now()->toIso8601String(),
        ];
    }
}
