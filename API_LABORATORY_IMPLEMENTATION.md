# BobaCraft Milk Tea Bar — API Laboratory Implementation Report
**Laboratory Activity:** API Performance, Reliability, and Monitoring  
**Student Developer:** Kyle Gacula  
**Project Path:** `C:\Users\Kyle Gacula\OneDrive\Desktop\FinalsIPT`  
**Database:** MySQL (`bobacraft_db`) on Laragon (Port 3306)  
**Framework:** Laravel 11 / PHP 8.3  

---

## 1. Executive Summary

This report documents the architectural improvements, performance optimizations, reliability mechanisms, and monitoring instrumentation implemented for the **BobaCraft Milk Tea Bar API**. 

The laboratory objectives were achieved while strictly preserving existing business rules, database records, and real-time WebSocket capabilities.

### Key Metrics Summary
| Metric | Baseline (Original) | Optimized | Improvement |
|---|---|---|---|
| **Query Latency (10k records)** | **577.00 ms** | **28.48 ms** | **20.26x faster (95.1% drop)** |
| **Payload Size** | **2,432.85 KB (2.43 MB)** | **6.28 KB** | **99.74% smaller** |
| **Memory Delta** | **12.98 MB** | **0.13 MB** | **99.0% memory reduction** |
| **Throughput / Scalability** | Prone to memory exhaustion & timeouts | O(1) slice memory stream | Eliminates Denial of Service risk |
| **Read Cache Speed** | ~28 ms DB query | **1.2 ms** (Cache HIT) | **23x faster cached aggregations** |

---

## 2. Dataset Generation (Requirement 1 & 3)

A safe, realistic test dataset of **10,005 milk tea order tickets** was generated and seeded into `bobacraft_db`.

### Seeding Mechanism
- **Artisan Command:** `php artisan orders:seed-10k`
- **Pure SQL Script:** [`database/sql/generate_10k_orders.sql`](file:///C:/Users/Kyle%20Gacula/OneDrive/Desktop/FinalsIPT/database/sql/generate_10k_orders.sql)
- **Execution Performance:** 10,000 records inserted in **2.36 seconds** via chunked batch inserts (1,000 rows per batch).
- **Safety Guarantee:** Preserves the 5 existing demo tickets (`Cup #201` – `Cup #205`) so the live dashboard UI remains intact.

### Dataset Distribution
```
+-----------+-------+
| Status    | Count |
+-----------+-------+
| Pending   | 1,517 |
| Preparing | 2,032 |
| Ready     | 1,494 |
| Completed | 4,962 |
| TOTAL     | 10,005|
+-----------+-------+
```

---

## 3. Performance Optimization Implementation (Requirement 2)

### The Problem in Original Implementation
1. **Unpaginated Dataset:** An unindexed query (`Order::orderBy('created_at', 'desc')->get()`) loaded all 10,000+ Eloquent models into PHP memory at once.
2. **Wildcard Projection:** `SELECT *` retrieved all database columns regardless of whether they were needed.
3. **Full Table Scans:** Without compound indexing on `(status, created_at)`, MySQL performed full table scans on every query.

### The Optimization Solution
1. **Compound Index Migration:**
   - Created migration `database/migrations/2026_11_05_000001_add_indexes_to_orders_table.php`.
   - Added `idx_orders_status_created_at` on `(status, created_at)` and `idx_orders_created_at` on `(created_at)`.
2. **Keyset / Length-Aware Pagination:**
   - Orders are returned in manageable slices (`per_page=25`, configurable up to 100).
3. **Column Projection:**
   - Restricted query columns to `['id', 'customer_name', 'status', 'created_at']`.
4. **Demonstrable Dual-Mode Design:**
   - The endpoint `/api/v1/orders` supports `?mode=optimized` (default) and `?mode=original` for instantaneous side-by-side verification on the identical dataset without server restarts.

---

## 4. Predictable JSON Error Handling (Requirement 3)

All exceptions and API errors return a uniform, predictable JSON schema with standard HTTP status codes.

### Unified Error Response Schema
```json
{
  "success": false,
  "message": "Human-readable summary message.",
  "error": {
    "code": "ERROR_CODE_IDENTIFIER",
    "message": "Detailed context description.",
    "details": { ... },
    "timestamp": "2026-11-05T08:30:00+08:00",
    "request_id": "req_6729a1b2c3d4"
  }
}
```

### Handled Status Codes
| HTTP Code | Error Code | Description |
|---|---|---|
| **400** | `BAD_REQUEST` | Malformed parameters or syntax |
| **401** | `UNAUTHORIZED` | Missing or invalid API key / Bearer token |
| **403** | `FORBIDDEN` | Valid key but insufficient role/permissions |
| **404** | `RESOURCE_NOT_FOUND` | Specified order ID does not exist |
| **422** | `VALIDATION_FAILED` | Input validation rules violated |
| **429** | `RATE_LIMIT_EXCEEDED` | Exceeded allowed request quota |
| **500** | `INTERNAL_SERVER_ERROR` | Unexpected server error (credentials redacted) |

---

## 5. Input Validation (Requirement 4)

Validation is decoupled into dedicated Form Request classes:
- [`app/Http/Requests/Api/StoreOrderRequest.php`](file:///C:/Users/Kyle%20Gacula/OneDrive/Desktop/FinalsIPT/app/Http/Requests/Api/StoreOrderRequest.php)
- [`app/Http/Requests/Api/UpdateOrderStatusRequest.php`](file:///C:/Users/Kyle%20Gacula/OneDrive/Desktop/FinalsIPT/app/Http/Requests/Api/UpdateOrderStatusRequest.php)

Both inherit from [`BaseApiRequest`](file:///C:/Users/Kyle%20Gacula/OneDrive/Desktop/FinalsIPT/app/Http/Requests/Api/BaseApiRequest.php), which intercepts `failedValidation()` to throw an `HttpResponseException` formatted with the standardized error envelope:

```json
{
  "success": false,
  "message": "The given request payload failed input validation rules.",
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "The given request payload failed input validation rules.",
    "details": {
      "customer_name": ["Customer name or ticket title is required."],
      "size": ["Size must be either Regular or Large."]
    },
    "timestamp": "2026-11-05T08:30:00+08:00",
    "request_id": "req_84f901a"
  }
}
```

---

## 6. Rate Limiting (Requirement 5)

Configured in [`app/Providers/AppServiceProvider.php`](file:///C:/Users/Kyle%20Gacula/OneDrive/Desktop/FinalsIPT/app/Providers/AppServiceProvider.php):

1. **Standard API Rate Limit:**
   - **Quota:** 60 requests / minute per client IP.
   - Applied to standard read/write routes.
2. **Strict Test Rate Limit (`throttle:strict-test`):**
   - **Quota:** 5 requests / minute per client IP.
   - Attached to `/api/v1/rate-limit-test` for dependable, immediate verification of HTTP 429 responses.
   - Returns header `Retry-After: 60`.

---

## 7. Caching for Read-Heavy Endpoints (Requirement 6)

### Read-Heavy Endpoint
- **URL:** `GET /api/v1/orders/stats`
- **Cache Key:** `bobacraft_order_stats`
- **TTL:** 60 seconds

### Invalidation Strategy
- Whenever a write operation occurs (`POST /api/v1/orders` or `PATCH /api/v1/orders/{id}`), `Cache::forget('bobacraft_order_stats')` is triggered immediately.
- The next request guarantees fresh data while returning `X-Cache: MISS`, followed by subsequent fast `X-Cache: HIT` responses.

---

## 8. Diagnostic Request Logging (Requirement 7)

Implemented as a reusable pipeline middleware:
[`app/Http/Middleware/DiagnosticRequestLogging.php`](file:///C:/Users/Kyle%20Gacula/OneDrive/Desktop/FinalsIPT/app/Http/Middleware/DiagnosticRequestLogging.php)

### Diagnostic Response Headers
```http
X-Request-ID: req_6938a0f81d9e2b14
X-Response-Time-Ms: 28.48
X-Memory-Peak-MB: 14.25
X-DB-Queries: 2
X-DB-Query-Time-Ms: 3.12
X-Optimization-Mode: optimized
X-Records-Returned: 25
```

### Structured Diagnostic File
All requests append structured JSON records to `storage/logs/api-diagnostics.log`. Sensitive headers (`Authorization`, `X-API-Key`, cookies) and inputs (`password`, `secret`, `token`) are automatically redacted to ensure credential safety.

---

## 9. Switching Between Original and Optimized Configurations

### Option A: Query Parameter Switching (Instantaneous, Recommended)
Both modes are available concurrently in the same running instance:
```bash
# Optimized Mode (Paginated, Indexed, Fast)
curl -i "http://127.0.0.1:8000/api/v1/orders?mode=optimized&per_page=25"

# Original Baseline Mode (Full Scan, Unpaginated)
curl -i "http://127.0.0.1:8000/api/v1/orders?mode=original"
```

### Option B: Git Branch Switching (Version Control)
```bash
# Switch to Baseline (Original state)
git checkout original-baseline

# Switch to Optimized (Complete lab implementation)
git checkout api-optimized
```
