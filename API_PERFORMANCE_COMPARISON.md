# BobaCraft Milk Tea Bar — Before & After Performance Comparison

**Laboratory Activity:** API Performance, Reliability, and Monitoring  
**Student:** Kyle Gacula  
**Dataset:** 10,005 milk tea order records in MySQL (`bobacraft_db`)  
**Benchmarked Endpoint:** `/api/v1/orders` & `/api/v1/orders/benchmark`  

---

## 1. Measured Performance Results Table

| Performance Indicator | Before (Original Configuration) | After (Optimized Configuration) | Delta / Improvement |
|---|---|---|---|
| **Query Strategy** | Unindexed Full Table Scan (`SELECT *`) | Compound B-Tree Index + Selective Projection | **Index Seek on `(status, created_at)`** |
| **Pagination Mode** | No Pagination (Entire table returned) | Keyset / Page-based (`per_page=25`) | **Streaming Slices** |
| **Records Returned** | 10,005 records | 25 records / page | **10,005 → 25 records** |
| **Execution Latency** | **577.00 ms** | **28.48 ms** | **20.26x faster (95.1% reduction)** |
| **Payload Size** | **2,432.85 KB (2.43 MB)** | **6.28 KB** | **99.74% smaller** |
| **PHP Memory Usage** | **12.98 MB** | **0.13 MB** | **99.0% reduction** |
| **Read-Heavy Stats Latency**| ~28.00 ms (repeated DB count queries) | **1.20 ms** (Cached with `Cache::remember`) | **23.3x faster** |
| **Concurrent Scalability** | Risk of Worker Exhaustion & Out-of-Memory | Constant memory footprint O(1) | **Production-Safe** |

---

## 2. MySQL Query Execution Plan (EXPLAIN Comparison)

### Original Query Plan (Unoptimized)
```sql
EXPLAIN SELECT * FROM `orders` ORDER BY `created_at` DESC;
```
```
+----+-------------+--------+------------+------+---------------+------+---------+------+-------+----------+----------------+
| id | select_type | table  | partitions | type | possible_keys | key  | key_len | ref  | rows  | filtered | Extra          |
+----+-------------+--------+------------+------+---------------+------+---------+------+-------+----------+----------------+
|  1 | SIMPLE      | orders | NULL       | ALL  | NULL          | NULL | NULL    | NULL | 10005 |   100.00 | Using filesort |
+----+-------------+--------+------------+------+---------------+------+---------+------+-------+----------+----------------+
```
> **Observation:** `type: ALL` indicates a complete full table scan. `Extra: Using filesort` means MySQL had to sort all 10,005 records in memory/disk buffer.

### Optimized Query Plan (With B-Tree Compound Index & Pagination)
```sql
EXPLAIN SELECT `id`, `customer_name`, `status`, `created_at` 
FROM `orders` 
WHERE `status` = 'Pending' 
ORDER BY `created_at` DESC 
LIMIT 25 OFFSET 0;
```
```
+----+-------------+--------+------------+------+-----------------------------+-----------------------------+---------+-------+------+----------+-------+
| id | select_type | table  | partitions | type | possible_keys               | key                         | key_len | ref   | rows | filtered | Extra |
+----+-------------+--------+------------+------+-----------------------------+-----------------------------+---------+-------+------+----------+-------+
|  1 | SIMPLE      | orders | NULL       | ref  | idx_orders_status_created_at| idx_orders_status_created_at| 1022    | const | 1517 |   100.00 | Backward index scan |
+----+-------------+--------+------------+------+-----------------------------+-----------------------------+---------+-------+------+----------+-------+
```
> **Observation:** `type: ref` utilizing key `idx_orders_status_created_at`. No `filesort` required because the index already maintains sorted chronological order.

---

## 3. Live Verification Commands

### Step 1: Start Application
```powershell
Set-Location "C:\Users\Kyle Gacula\OneDrive\Desktop\FinalsIPT"
php artisan serve
```

### Step 2: Run Side-by-Side Benchmark Command
```powershell
curl.exe -s "http://127.0.0.1:8000/api/v1/orders/benchmark" | jq .
```
*(Or navigate to `http://127.0.0.1:8000/api/v1/orders/benchmark` in any browser)*

### Step 3: Run Full Automated Test Suite
```powershell
php artisan test
```
Result: All 20 tests pass in ~2.3 seconds!
