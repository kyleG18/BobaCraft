# BobaCraft Milk Tea Bar — API Laboratory Test Plan & Execution Results

**Laboratory Title:** API Performance, Reliability, and Monitoring  
**Target Application:** BobaCraft Milk Tea Bar  
**Test Runner:** PHPUnit 12 / Laravel Feature Testing Suite / Postman Collection  
**Execution Date:** November 05, 2026  
**Overall Result:** **ALL 20 AUTOMATED TESTS PASSED (100% PASS RATE)**  

---

## 1. Test Environment Specification

| Parameter | Value |
|---|---|
| **Base URL** | `http://127.0.0.1:8000` |
| **API Version** | `v1` (`/api/v1/*`) |
| **Database Server** | MySQL 8.4.3 (Laragon, Port 3306) |
| **Target Database** | `bobacraft_db` |
| **Test Dataset Size** | **10,005 realistic milk tea orders** |
| **PHP Version** | PHP 8.3.30 (Win64) |
| **Test Framework** | PHPUnit 12.5.38 via `php artisan test` |

---

## 2. Test Execution Summary

```
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true                                                    0.02s  

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                        0.14s  

   PASS  Tests\Feature\OrderTest
  ✓ orders dashboard displays orders and statistics                      0.32s  
  ✓ can create new order with initial pending status                     0.24s  
  ✓ cannot create order with invalid data                                0.18s  
  ✓ can update order status and dispatches event                         0.21s  
  ✓ rejects unsupported or arbitrary status                              0.19s  
  ✓ does not dispatch duplicate event when status is unchanged           0.18s  

   PASS  Tests\Feature\ApiLaboratoryTest
  ✓ api orders index returns paginated optimized response                0.22s  
  ✓ api orders index original mode returns unpaginated full dataset      0.24s  
  ✓ api orders stats caching and hit miss behavior                       0.19s  
  ✓ cache invalidation when creating or updating order                   0.26s  
  ✓ order creation validation failure returns predictable json error     0.18s  
  ✓ missing order returns predictable 404 json error                     0.16s  
  ✓ admin route without api key returns 401 unauthorized                 0.15s  
  ✓ admin route with non admin key returns 403 forbidden                 0.17s  
  ✓ admin route with valid admin key succeeds                            0.20s  
  ✓ strict rate limiting triggers 429 too many requests                  0.19s  
  ✓ diagnostic logging headers are present on responses                  0.18s  
  ✓ sensitive headers are not logged in diagnostics                      0.17s  

  Tests:    20 passed (102 assertions)
  Duration: 2.36s
```

---

## 3. Detailed Test Matrix

### TC-01: Performance Optimization Verification
- **Endpoint:** `GET /api/v1/orders/benchmark`
- **Method:** Evaluates live side-by-side performance on 10,005 MySQL records.
- **Expected:** Optimized execution time is significantly lower (< 50ms) compared to original full scan (> 300ms).
- **Actual Measured:**
  - Original: **577.00 ms** (12.98 MB memory, 2,432.85 KB payload)
  - Optimized: **28.48 ms** (0.13 MB memory, 6.28 KB payload)
  - **Result:** **PASS (20.26x Speedup, 99.74% Payload Reduction)**

### TC-02: Predictable Input Validation Errors (HTTP 422)
- **Endpoint:** `POST /api/v1/orders`
- **Payload:** `{"customer_name": "", "size": "InvalidSize"}`
- **Expected:** HTTP 422 with predictable JSON error: `error.code = "VALIDATION_FAILED"` and detailed field errors.
- **Actual:** HTTP 422 returned with standardized error schema and field pointers.
- **Result:** **PASS**

### TC-03: Missing Resource Lookup (HTTP 404)
- **Endpoint:** `GET /api/v1/orders/999999`
- **Expected:** HTTP 404 with predictable JSON error: `error.code = "RESOURCE_NOT_FOUND"`.
- **Actual:** HTTP 404 returned without leaking internal paths or database errors.
- **Result:** **PASS**

### TC-04: Authentication Failure (HTTP 401)
- **Endpoint:** `DELETE /api/v1/admin/orders/1`
- **Headers:** *(No API Key provided)*
- **Expected:** HTTP 401 Unauthorized with `error.code = "UNAUTHORIZED"`.
- **Actual:** HTTP 401 returned with hint header requirement.
- **Result:** **PASS**

### TC-05: Authorization / RBAC Failure (HTTP 403)
- **Endpoint:** `DELETE /api/v1/admin/orders/1`
- **Headers:** `X-API-Key: bobacraft-customer-key-2026` *(Kiosk role)*
- **Expected:** HTTP 403 Forbidden with `error.code = "FORBIDDEN"`.
- **Actual:** HTTP 403 returned stating insufficient administrative role.
- **Result:** **PASS**

### TC-06: Strict Rate Limiting (HTTP 429)
- **Endpoint:** `GET /api/v1/rate-limit-test`
- **Execution:** 6 requests executed in rapid succession (Limit: 5 req/min).
- **Expected:** Requests 1–5 return 200 OK; Request 6 returns HTTP 429 with `Retry-After` header.
- **Actual:** Request 6 returned HTTP 429 with `Retry-After: 60` and `error.code = "RATE_LIMIT_EXCEEDED"`.
- **Result:** **PASS**

### TC-07: Read-Heavy Caching & Invalidation
- **Endpoint:** `GET /api/v1/orders/stats`
- **Expected:**
  1. First request returns `X-Cache: MISS`.
  2. Subsequent request returns `X-Cache: HIT`.
  3. POST new order invalidates cache; next read returns `X-Cache: MISS`.
- **Actual:** Verified in both unit tests and live HTTP testing.
- **Result:** **PASS**

### TC-08: Diagnostic Request Logging
- **Headers Verified:** `X-Request-ID`, `X-Response-Time-Ms`, `X-Memory-Peak-MB`, `X-DB-Queries`.
- **Log Location:** `storage/logs/api-diagnostics.log`.
- **Expected:** Every request writes a structured JSON line containing performance metrics.
- **Actual:** Verified structured log output with sanitized credentials.
- **Result:** **PASS**

### TC-09: Sensitive Credential Protection
- **Input:** Request submitted with `Authorization: Bearer <token>` and `X-API-Key`.
- **Expected:** Tokens and sensitive headers are omitted from logs and error stack traces.
- **Actual:** Diagnostic log verifies `authorization` and `x-api-key` are stripped prior to disk write.
- **Result:** **PASS**

---

## 4. Postman Collection Instructions

The complete test suite is exported in [`BobaCraft_API_Laboratory.postman_collection.json`](file:///C:/Users/Kyle%20Gacula/OneDrive/Desktop/FinalsIPT/BobaCraft_API_Laboratory.postman_collection.json).

### Steps to Run in Postman:
1. Open **Postman**.
2. Click **Import** (top left) and select `BobaCraft_API_Laboratory.postman_collection.json`.
3. Ensure the Laravel server is active (`php artisan serve` at `http://127.0.0.1:8000`).
4. Click **Run Collection** → Select all folders.
5. Watch all assertions pass with green checkmarks!
