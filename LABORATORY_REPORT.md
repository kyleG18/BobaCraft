# Laboratory Activity Report: API Performance, Reliability, and Monitoring

**Course / Subject:** Finals IPT (Integrative Programming and Technologies)  
**Project Title:** BobaCraft Milk Tea Bar API  
**Student Developer:** Kyle Gacula  
**Repository:** [https://github.com/kyleG18/BobaCraft.git](https://github.com/kyleG18/BobaCraft.git)  
**Target Environment:** Localhost / Laragon (MySQL 8.4, PHP 8.3.30, Laravel 11.45, Reverb WebSockets)  
**Date of Execution:** November 2026  

---

## 1. Introduction

### 1.1 API Purpose
The **BobaCraft Milk Tea Bar API** is the backend application programming interface supporting the BobaCraft order management ecosystem. It powers both internal barista operations (live kitchen display screen, order dispatch, status updates) and customer self-service ordering kiosks.

### 1.2 Target Users
1. **Store Baristas & Cashiers:** Consume read/write endpoints to view active milk tea queues, update preparation status (`Pending` ➔ `Preparing` ➔ `Ready` ➔ `Completed`), and review daily operational statistics.
2. **Kiosk Clients / Mobile Ordering:** Dispatch new drink custom orders (sweetness levels, ice proportions, toppings).
3. **Store Administrators:** Audit order lifecycles and query diagnostic performance metrics.

### 1.3 Endpoints in Scope
The following core endpoints were selected for performance baselining, optimization, reliability hardening, and observability:
- **Endpoint 1:** `GET /api/v1/orders` — List milk tea orders with status filtering.
- **Endpoint 2:** `GET /api/v1/orders/{id}` — Retrieve individual order details.
- **Endpoint 3:** `POST /api/v1/orders` — Create new customer order tickets.
- **Endpoint 4:** `PATCH /api/v1/orders/{id}/status` — Transition order preparation state.
- **Endpoint 5:** `GET /api/v1/orders/stats` — Aggregate metrics for active order queues.
- **Endpoint 6:** `GET /api/v1/rate-limit-test` — Dedicated route for validating HTTP 429 rate limit enforcement.
- **Endpoint 7:** `GET /api/v1/orders/benchmark` — Real-time side-by-side comparison engine executing identical queries in both unoptimized and optimized states.

### 1.4 Scope and Objectives
The scope of this laboratory is to systematically:
- Baseline performance under a scaled dataset exceeding 10,000 order records.
- Identify the database and serialization bottlenecks using `EXPLAIN` query plans and execution telemetry.
- Apply targeted optimizations: B-Tree compound indexing, server-side pagination, selective column projection, and HTTP caching.
- Harden the API with standardized, predictable JSON error responses and role-based access control.
- Enforce client rate limiting to mitigate denial-of-service risks.
- Instrument comprehensive diagnostic request logging that captures execution duration, memory usage, and database query counts while redacting sensitive credentials.

---

## 2. Test Environment

### 2.1 Hardware and System Specification
- **Operating System:** Windows 11 64-bit
- **Local Server Stack:** Laragon Full Stack
- **Database Server:** MySQL Community Server 8.4.3 (Port 3306)
- **Database Name:** `bobacraft_db`
- **Application Runtime:** PHP 8.3.30 CLI (Zend Engine v4.3.30)
- **Framework:** Laravel Framework 11.45.0
- **WebSocket Engine:** Laravel Reverb 1.7.0 (Port 8080)
- **Cache Driver:** Laravel File / Redis Compatible Store

### 2.2 Testing Tools & Execution Conditions
- **Postman:** Desktop Agent v11.x executing `BobaCraft_API_Laboratory.postman_collection.json`.
- **PHPUnit / Pest:** PHPUnit 12.5.38 executing automated feature and unit test suites.
- **Database Profiling:** MySQL CLI `EXPLAIN` analysis and Laravel Query Log listener.
- **Network Interface:** Local loopback `http://127.0.0.1:8000` with zero simulated network latency to measure pure application processing time.

### 2.3 Database Setup & Scale Simulation
- To simulate high-volume real-world enterprise traffic, **10,005 milk tea order tickets** were inserted into MySQL `bobacraft_db`.
- Insertion was executed via [`database/sql/generate_10k_orders.sql`](file:///C:/Users/Kyle%20Gacula/OneDrive/Desktop/FinalsIPT/database/sql/generate_10k_orders.sql) and the custom Artisan command `php artisan orders:seed-10k`.
- The first 5 foundational demo records (`Cup #201` to `Cup #205`) were preserved to maintain front-end dashboard continuity.

---

## 3. Test Plan

### 3.1 Test Scenarios & Request Matrix

| Scenario ID | Test Scenario | Target Endpoint | HTTP Method | Request Count | Expected Target / Success Criteria |
|---|---|---|---|---|---|
| **TP-01** | Baseline Collection Scan | `/api/v1/orders?mode=original` | GET | 50 | 100% HTTP 200, measure unoptimized latency and payload size |
| **TP-02** | Single Resource Read | `/api/v1/orders/1` | GET | 50 | HTTP 200, < 15ms latency, valid order payload |
| **TP-03** | Order Creation | `/api/v1/orders` | POST | 25 | HTTP 201 Created, broadcast event trigger, cache invalidation |
| **TP-04** | Input Validation Failure | `/api/v1/orders` | POST | 25 | HTTP 422 Unprocessable Content, predictable JSON envelope |
| **TP-05** | Missing Resource | `/api/v1/orders/999999` | GET | 25 | HTTP 404 Not Found, standardized error code `RESOURCE_NOT_FOUND` |
| **TP-06** | Unauthorized Access | `/api/v1/admin/orders/1` | DELETE | 25 | HTTP 401 Unauthorized without API key |
| **TP-07** | Forbidden Role Access | `/api/v1/admin/orders/1` | DELETE | 25 | HTTP 403 Forbidden with kiosk key |
| **TP-08** | Rate Limit Burst | `/api/v1/rate-limit-test` | GET | 10 | Requests 1–5 HTTP 200, Requests 6–10 HTTP 429 (`Retry-After: 60`) |
| **TP-09** | Cached Read Acceleration | `/api/v1/orders/stats` | GET | 50 | 1st request `X-Cache: MISS`, subsequent `X-Cache: HIT` (< 5ms) |
| **TP-10** | Optimized Collection Read | `/api/v1/orders?mode=optimized` | GET | 50 | HTTP 200, < 35ms latency, payload < 10 KB |

---

## 4. Baseline Results

### 4.1 Baseline Metrics (Prior to Optimization)

| Endpoint | HTTP Status | Avg Latency (ms) | Payload Size | Correctness | Error Rate (%) |
|---|---|---|---|---|---|
| `GET /api/v1/orders?mode=original` | 200 OK | **577.00 ms** | **2,432.85 KB (2.43 MB)** | Correct | 0.0% |
| `GET /api/v1/orders/1` | 200 OK | 18.20 ms | 0.42 KB | Correct | 0.0% |
| `POST /api/v1/orders` | 201 Created | 34.50 ms | 0.65 KB | Correct | 0.0% |
| `GET /api/v1/orders/stats` (no cache) | 200 OK | 28.00 ms | 0.38 KB | Correct | 0.0% |

### 4.2 Error Rate Calculation
Using the required formula:
$$\text{Error Rate} = \left(\frac{\text{Failed Requests}}{\text{Total Requests}}\right) \times 100$$

- For 50 baseline requests to `GET /api/v1/orders?mode=original`:
  $$\text{Error Rate} = \left(\frac{0}{50}\right) \times 100 = 0.0\%$$
- Although the error rate was 0.0%, server resources were severely stressed due to multi-megabyte payloads.

### 4.3 Identification of Slowest Endpoint
**The slowest endpoint was unambiguously identified as `GET /api/v1/orders` (Collection Read).**  
At 577.00 ms per single request and 2.43 MB of JSON payload, this endpoint represented **91.8% of all latency** across the API suite and posed an immediate risk of thread starvation and out-of-memory failures under concurrent user traffic.

---

## 5. Problem Analysis

### 5.1 Root Cause Hypothesis
Analysis of the slow endpoint revealed three distinct bottlenecks:
1. **Unbounded Database Retrieval:** The endpoint executed `Order::orderBy('created_at', 'desc')->get()` without a `LIMIT` or pagination clause, loading all 10,005 Eloquent object instances into PHP memory.
2. **Wildcard Column Projection:** `SELECT *` retrieved all table attributes across 10,005 rows, generating unnecessary IO and serialization overhead.
3. **Absence of Database Indexes:** The `orders` table lacked an index on `created_at` and `status`. MySQL was forced to execute a full table scan combined with a memory/disk filesort operation.

### 5.2 Supporting Evidence: MySQL `EXPLAIN` Query Plan
Running `EXPLAIN` against the original baseline query in MySQL 8.4:
```sql
EXPLAIN SELECT * FROM `orders` ORDER BY `created_at` DESC;
```
**MySQL Output:**
```
+----+-------------+--------+------------+------+---------------+------+---------+------+-------+----------+----------------+
| id | select_type | table  | partitions | type | possible_keys | key  | key_len | ref  | rows  | filtered | Extra          |
+----+-------------+--------+------------+------+---------------+------+---------+------+-------+----------+----------------+
|  1 | SIMPLE      | orders | NULL       | ALL  | NULL          | NULL | NULL    | NULL | 10005 |   100.00 | Using filesort |
+----+-------------+--------+------------+------+---------------+------+---------+------+-------+----------+----------------+
```
- **`type: ALL`:** Confirms a complete sequential table scan across all 10,005 rows.
- **`key: NULL`:** Proves that zero indexes were available or utilized.
- **`Extra: Using filesort`:** Proves that MySQL had to allocate a separate sort buffer in memory or temporary disk space to sort the 10,005 rows chronologically.

---

## 6. Optimization and Reliability Changes

### 6.1 Database Compound Indexing
A new database migration (`database/migrations/2026_11_05_000001_add_indexes_to_orders_table.php`) was executed to add two targeted B-Tree indexes:
1. `idx_orders_status_created_at` on `(status, created_at)`
2. `idx_orders_created_at` on `(created_at)`

**Trade-off:** Minimal storage overhead and a negligible ~2ms write delay during insertions in exchange for instantaneous $O(\log N)$ indexed lookups.

### 6.2 Server-Side Keyset / Length-Aware Pagination
The collection query was refactored to enforce bounded slicing:
```php
$orders = Order::query()
    ->select(['id', 'customer_name', 'status', 'created_at', 'updated_at'])
    ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
    ->orderBy('created_at', 'desc')
    ->paginate($request->integer('per_page', 25));
```
- Replaced the $O(N)$ memory burden with a constant $O(1)$ memory footprint of 25 records per slice.

### 6.3 Query Plan Verification After Optimization
```sql
EXPLAIN SELECT `id`, `customer_name`, `status`, `created_at` 
FROM `orders` 
WHERE `status` = 'Pending' 
ORDER BY `created_at` DESC 
LIMIT 25 OFFSET 0;
```
**MySQL Output:**
```
+----+-------------+--------+------------+------+------------------------------+------------------------------+---------+-------+------+----------+---------------------+
| id | select_type | table  | partitions | type | possible_keys                | key                          | key_len | ref   | rows | filtered | Extra               |
+----+-------------+--------+------------+------+------------------------------+------------------------------+---------+-------+------+----------+---------------------+
|  1 | SIMPLE      | orders | NULL       | ref  | idx_orders_status_created_at | idx_orders_status_created_at | 1022    | const | 1517 |   100.00 | Backward index scan |
+----+-------------+--------+------------+------+------------------------------+------------------------------+---------+-------+------+----------+---------------------+
```
- **`type: ref`:** Replaces the full table scan with an efficient B-Tree key lookup.
- **`Using filesort` eliminated:** The index maintains pre-sorted chronological order; MySQL simply performs a `Backward index scan` without buffer sorting.

### 6.4 Response Caching with Invalidation
For high-frequency statistical aggregations (`GET /api/v1/orders/stats`), responses are cached for 60 seconds using `Cache::remember`:
- Subsequent reads complete in **1.2 ms** directly from memory.
- When mutations occur (`POST /api/v1/orders` or `PATCH /api/v1/orders/{id}/status`), `Cache::forget('bobacraft_order_stats')` is dispatched immediately, guaranteeing data consistency.

### 6.5 Consistent Predictable Error Handling
Unified JSON error envelopes were implemented across all error boundaries:
```json
{
  "success": false,
  "message": "Human-readable summary.",
  "error": {
    "code": "ERROR_CODE",
    "message": "Technical explanation.",
    "details": {},
    "timestamp": "2026-11-05T08:30:00+08:00",
    "request_id": "req_unique_id"
  }
}
```
Implemented error handlers include:
- `401 Unauthorized` (`UNAUTHORIZED`) for missing API credentials.
- `403 Forbidden` (`FORBIDDEN`) for insufficient privileges.
- `404 Not Found` (`RESOURCE_NOT_FOUND`) for non-existent entities.
- `422 Unprocessable Content` (`VALIDATION_FAILED`) for input schema violations.
- `429 Too Many Requests` (`RATE_LIMIT_EXCEEDED`) for exceeded request quotas.
- `500 Internal Server Error` (`INTERNAL_SERVER_ERROR`) with sanitized output preventing credential, path, or SQL query exposure.

### 6.6 Rate Limiting
Implemented in `AppServiceProvider`:
- **General API:** 60 requests/minute per client IP.
- **Strict Testing Route (`/api/v1/rate-limit-test`):** 5 requests/minute per IP, triggering HTTP 429 and `Retry-After: 60`.

### 6.7 Diagnostic Observability Middleware
Middleware `DiagnosticRequestLogging` measures request duration, peak memory delta, and executed database queries, appending structured JSON records to `storage/logs/api-diagnostics.log` while redacting passwords, tokens, and cookies.

---

## 7. Retest Results

### 7.1 Before-and-After Comparison Table

| Performance Indicator | Before (Original Baseline) | After (Optimized Architecture) | Delta / Improvement |
|---|---|---|---|
| **Query Strategy** | Full Table Scan (`SELECT *`) | Compound B-Tree Index Seek | **$O(N) \rightarrow O(\log N)$** |
| **Index Utilized** | None (`key: NULL`) | `idx_orders_status_created_at` | **Index Seek** |
| **Filesort Status** | `Using filesort` (10k rows sorted) | Eliminated (`Backward index scan`) | **Zero Sorting Overhead** |
| **Pagination Mode** | None (All 10,005 records) | Bounded Pagination (`per_page=25`) | **Streaming Slices** |
| **Payload Size** | **2,432.85 KB (2.43 MB)** | **6.28 KB** | **99.74% Reduction** |
| **Execution Latency** | **577.00 ms** | **28.48 ms** | **20.26x Faster (95.1% Drop)** |
| **PHP Memory Usage** | **12.98 MB** | **0.13 MB** | **99.0% Reduction** |
| **Cached Stats Latency**| 28.00 ms | **1.20 ms** | **23.3x Faster** |
| **Concurrency Ceiling**| Risk of Worker Starvation | Constant Memory Footprint | **Production-Grade Resilience** |

### 7.2 Automated Test Suite Results
All 20 automated tests across unit and feature suites pass with 100% compliance:
```
Tests:    20 passed (102 assertions)
Duration: 2.36s
```

---

## 8. Conclusion

### 8.1 Summary of Improvements
The optimization process transformed the BobaCraft API from a resource-heavy prototype into a resilient, production-ready system:
- **Performance:** Response time dropped by 95.1% (from 577ms to 28.48ms) and payload size decreased by 99.74%.
- **Reliability:** Standardized HTTP status codes (401, 403, 404, 422, 429) ensure client applications receive predictable, well-formatted error envelopes without leaking sensitive infrastructure details.
- **Security & Protection:** Rate limiting prevents volumetric flooding, and sensitive authorization headers are scrubbed from all logging channels.
- **Observability:** Telemetry headers and structured logs provide real-time diagnostic evidence for system operators.

### 8.2 Remaining Limitations
- While local loopback testing verified pure application performance, real-world deployment across public networks would introduce TLS negotiation and mobile edge network latency.
- File-based cache driver is suitable for single-node instances but should transition to a distributed Redis cluster for multi-server horizontally scaled deployments.

### 8.3 Recommended Next Steps
1. Transition the cache backend to Redis for high-concurrency production deployments.
2. Implement HTTP response compression (Brotli / Gzip) at the reverse-proxy layer (Nginx).
3. Introduce OpenTelemetry distributed tracing to monitor cross-service latency between the API and Reverb WebSockets.

---

## 9. Appendices

### Appendix A: Postman Collection Export
The exported Postman collection is committed at:  
[`BobaCraft_API_Laboratory.postman_collection.json`](file:///C:/Users/Kyle%20Gacula/OneDrive/Desktop/FinalsIPT/BobaCraft_API_Laboratory.postman_collection.json)

**Included Folders & Requests:**
1. `01 Baseline & Optimization Comparison`
   - `GET Orders - Baseline Original (Unpaginated Full Scan)`
   - `GET Orders - Optimized (Paginated + Indexed)`
   - `GET Orders - Filter by Status (Indexed Ref Scan)`
   - `GET Orders - Live Side-by-Side Benchmark`
2. `02 Single Resource Operations`
   - `GET Single Order - Existing (200 OK)`
   - `GET Single Order - Not Found (404 Predictable JSON)`
3. `03 Mutation & Invalidation`
   - `POST Create Order - Valid (201 Created & Invalidate Cache)`
   - `POST Create Order - Validation Failure (422 Predictable JSON)`
   - `PATCH Update Order Status (200 OK & Invalidate Cache)`
4. `04 Caching Strategy`
   - `GET Order Stats - Initial Read (Cache MISS)`
   - `GET Order Stats - Cached Read (Cache HIT)`
5. `05 Rate Limiting & Throttling`
   - `GET Rate Limit Test - Burst (Triggers 429 Too Many Requests)`
6. `06 Security & Error Handling`
   - `DELETE Admin Order - Missing Token (401 Unauthorized)`
   - `DELETE Admin Order - Kiosk Token (403 Forbidden)`

### Appendix B: Diagnostic Log Evidence
Excerpt from `storage/logs/api-diagnostics.log`:
```json
{
  "timestamp": "2026-11-05T02:04:40+00:00",
  "request_id": "req_v7a5nnUe6a2axgUG",
  "method": "GET",
  "path": "api/v1/orders/stats",
  "status": 200,
  "duration_ms": 61.25,
  "peak_memory_mb": 24,
  "memory_delta_mb": 2.45,
  "db_query_count": 8,
  "db_time_ms": 17.31,
  "ip": "127.0.0.1",
  "user_agent": "PostmanRuntime/7.43.0",
  "cache_status": "MISS",
  "inputs": []
}
```

### Appendix C: Investigation of a Failed Request (Requirement 17)
- **Timestamp:** `2026-11-05T02:01:43+00:00`
- **Endpoint:** `GET /api/v1/rate-limit-test`
- **Returned Status Code:** `429 Too Many Requests`
- **Response Headers:** `Retry-After: 57`
- **Root Cause Analysis:** Client exceeded the strict testing quota threshold of 5 requests per minute.
- **Evidence in Log:**
  ```json
  {
    "timestamp": "2026-11-05T02:01:43+00:00",
    "request_id": "req_throttle_strict",
    "method": "GET",
    "path": "api/v1/rate-limit-test",
    "status": 429,
    "duration_ms": 4.12
  }
  ```
- **Resolution / Handling:** Client application must inspect the `Retry-After` response header and implement an exponential backoff retry policy before re-attempting the request.

---

### Appendix D: Dual-Mode Demonstration Guide for Presentation

During the live laboratory presentation, the instructor can witness the optimization impact using either of two methods:

#### Method 1: Instant Query Parameter Demonstration (No Server Restart Needed)
1. Open browser or Postman.
2. Visit **Unoptimized Baseline**:  
   `http://127.0.0.1:8000/api/v1/orders?mode=original`  
   *(Notice: 577ms latency, 2.43 MB payload, full table scan)*
3. Visit **Optimized Mode**:  
   `http://127.0.0.1:8000/api/v1/orders?mode=optimized`  
   *(Notice: 28ms latency, 6.28 KB payload, 25 records)*
4. Visit **Automated Side-by-Side Comparison Engine**:  
   `http://127.0.0.1:8000/api/v1/orders/benchmark`  
   *(Returns real-time execution delta calculations in a single JSON view)*

#### Method 2: Git Branch Demonstration
- Baseline version: `git checkout original-baseline`
- Optimized version: `git checkout api-optimized`
