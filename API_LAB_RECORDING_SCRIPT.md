# 🎬 BobaCraft — API Laboratory Video Presentation & Recording Script

**Activity:** API Performance, Reliability, and Monitoring Assessment  
**Application:** BobaCraft Milk Tea Bar API  
**Student Presenter:** Kyle Gacula  
**Target Video Duration:** 3 to 5 Minutes  
**Repository:** [https://github.com/kyleG18/BobaCraft.git](https://github.com/kyleG18/BobaCraft.git)  

---

## 🛠️ Pre-Recording Setup Checklist (Do This Before Starting OBS / Screen Recorder)

### 1. Ensure MySQL is Running
- Open **Laragon** and verify MySQL is running (Green light on Port 3306).

### 2. Launch Local Servers
Open two terminal windows (in VS Code or Windows Terminal):
- **Terminal 1 (Laravel HTTP Server):**
  ```powershell
  Set-Location "C:\Users\Kyle Gacula\OneDrive\Desktop\FinalsIPT"
  php artisan serve
  ```
  *(Confirms: `Server running on [http://127.0.0.1:8000]`)*

- **Terminal 2 (Laravel Reverb WebSockets):**
  ```powershell
  Set-Location "C:\Users\Kyle Gacula\OneDrive\Desktop\FinalsIPT"
  php artisan reverb:start
  ```
  *(Confirms: `Reverb server started on 0.0.0.0:8080`)*

### 3. Open Postman
- Open **Postman**.
- Make sure `BobaCraft API Laboratory` collection is imported (from `C:\Users\Kyle Gacula\OneDrive\Desktop\FinalsIPT\BobaCraft_API_Laboratory.postman_collection.json`).

### 4. Open Google Chrome with 3 Ready Tabs
- **Tab 1:** `http://127.0.0.1:8000/api/v1/orders/benchmark` *(Live Side-by-Side Engine)*
- **Tab 2:** `http://127.0.0.1:8000/api/v1/orders?mode=original` *(Baseline Unoptimized)*
- **Tab 3:** `http://127.0.0.1:8000/api/v1/orders?mode=optimized` *(Optimized)*

### 5. Open VS Code with Split View Ready
- File 1: `storage/logs/api-diagnostics.log` (Scroll to bottom for fresh logs).
- A clean terminal at the bottom ready to type `php artisan test`.

---

## ⏱️ Video Recording Timeline (3:30 to 4:30 Minutes Total)

```
0:00 ── 0:35  [Scene 1] Introduction & Objective
0:35 ── 1:40  [Scene 2] Step 1 & 2: Baseline vs. Optimized Demonstration
1:40 ── 2:40  [Scene 3] Step 3: Reliability & Predictable Error Responses (401, 403, 404, 422)
2:40 ── 3:35  [Scene 4] Step 4: Rate Limiting (429) & Caching Lifecycle
3:35 ── 4:15  [Scene 5] Step 5: Observability, Diagnostics Log & Automated Tests
4:15 ── 4:40  [Scene 6] Conclusion & Sign-Off
```

---

## 🎙️ Verbatim Script & Visual Actions

---

### [Scene 1] Introduction & Objective (0:00 – 0:35)

* **What to Show on Screen:**  
  Show your browser open to the BobaCraft API Laboratory report or GitHub repo `https://github.com/kyleG18/BobaCraft`. Have your webcam or mic clear.
* **What to Speak:**  
  > *"Good day, instructor and classmates. My name is Kyle Gacula, and today I am presenting my laboratory assessment on **API Performance, Reliability, and Monitoring** using our **BobaCraft Milk Tea Bar API**.*  
  > *In this demonstration, I will show how we scaled our database to over 10,000 realistic milk tea orders, identified the critical performance bottlenecks, applied database indexing, pagination, and caching optimizations, enforced predictable error handling and rate limits, and established structured diagnostic observability without using k6."*

---

### [Scene 2] Baseline vs. Optimized Demonstration (0:35 – 1:40)

* **What to Show on Screen:**  
  1. Switch to **Tab 2** (`http://127.0.0.1:8000/api/v1/orders?mode=original`) and press **Refresh**. Point to the browser dev tools or Postman showing the huge response size and ~570ms load time.
  2. Switch to **Tab 3** (`http://127.0.0.1:8000/api/v1/orders?mode=optimized`) and press **Refresh**. Point to how fast it loads (under 30ms) and how compact the JSON is.
  3. Switch to **Tab 1** (`http://127.0.0.1:8000/api/v1/orders/benchmark`).
* **What to Speak:**  
  > *"First, let's examine our baseline performance. To simulate heavy enterprise traffic, we seeded 10,005 milk tea order tickets into MySQL.*  
  > *In our unoptimized baseline mode (`?mode=original`), the API executes an unindexed `SELECT * FROM orders` without pagination. As you can see, loading all 10,005 records takes approximately **577 milliseconds**, transfers a massive **2.43 Megabyte** JSON payload over the wire, and consumes nearly 13 megabytes of PHP memory per request. In MySQL `EXPLAIN`, this caused a full table scan and a memory filesort.*  
  > *Now, look at our optimized mode (`?mode=optimized`). We applied three targeted optimizations: a compound B-tree index on `(status, created_at)`, selective column projection, and length-aware pagination of 25 records per slice.*  
  > *Instantly, response latency drops from **577 ms down to just 28 ms**—that is a **20.26-fold speedup**, while payload size drops by **99.7%** from 2.4 MB down to only 6.2 KB! Here on our live benchmark endpoint, you can see the before-and-after metrics calculated side-by-side in real time."*

---

### [Scene 3] Reliability & Predictable Error Responses (1:40 – 2:40)

* **What to Show on Screen:**  
  Switch to **Postman**, folder `06 Security & Error Handling` and `03 Mutation & Invalidation`. Run requests and highlight the green status code and JSON responses:
  1. Click `POST Create Order - Validation Failure` ➔ Send. Show **422 Unprocessable Content**.
  2. Click `GET Single Order - Not Found` ➔ Send. Show **404 Not Found**.
  3. Click `DELETE Admin Order - Missing Token` ➔ Send. Show **401 Unauthorized**.
  4. Click `DELETE Admin Order - Kiosk Token` ➔ Send. Show **403 Forbidden**.
* **What to Speak:**  
  > *"Next, let's look at Reliability and Consistent Error Handling. A production API must fail predictably and securely without exposing database queries, file paths, or stack traces.*  
  > *When we submit a request missing required fields, the API returns **HTTP 422 Unprocessable Content** with a standardized error envelope identifying the exact field errors.*  
  > *When querying a non-existent order, it returns **HTTP 404 Not Found** with code `RESOURCE_NOT_FOUND`.*  
  > *For security, administrative routes require authorization: omitting an API key returns **HTTP 401 Unauthorized**, while using a customer kiosk key returns **HTTP 403 Forbidden** due to insufficient role permissions. Notice that all errors follow the exact same predictable JSON schema: `success`, `message`, `error.code`, `timestamp`, and a unique `request_id` for tracing."*

---

### [Scene 4] Rate Limiting & Caching Lifecycle (2:40 – 3:35)

* **What to Show on Screen:**  
  1. In Postman, go to `05 Rate Limiting & Throttling` ➔ `GET Rate Limit Test - Burst`. Click **Send** rapidly 6 times. On the 6th click, highlight **429 Too Many Requests** and the **Headers** tab showing `Retry-After: 60`.
  2. Go to `04 Caching Strategy` ➔ `GET Order Stats - Initial Read`. Click Send. Point to header `X-Cache: MISS`.
  3. Click Send immediately a second time. Point to header `X-Cache: HIT` and latency of **1.2 ms**.
  4. (Optional) In `03 Mutation & Invalidation`, send `POST Create Order - Valid (201 Created)`. Go back to Stats and show `X-Cache: MISS` (proving cache invalidation).
* **What to Speak:**  
  > *"In Step 4, we configured Rate Limiting and Caching strategies.*  
  > *To protect against volumetric denial-of-service, we configured rate limiting policies in Laravel's `AppServiceProvider`. On this strict test route with a limit of 5 requests per minute, sending a rapid burst causes request number 6 to trigger **HTTP 429 Too Many Requests**, returning an explicit `Retry-After: 60` header.*  
  > *For read-heavy endpoints like order statistics, we implemented response caching with a 60-second TTL. The initial call shows `X-Cache: MISS` and queries the database. Subsequent calls return `X-Cache: HIT` in just **1.2 milliseconds** directly from cache—a 23-times speedup! And whenever a barista creates or updates a drink order, the cache is automatically invalidated to guarantee data freshness."*

---

### [Scene 5] Observability, Diagnostics Log & Automated Tests (3:35 – 4:15)

* **What to Show on Screen:**  
  1. Switch to **VS Code** with `storage/logs/api-diagnostics.log`. Point out the JSON fields: `timestamp`, `request_id`, `method`, `path`, `status`, `duration_ms`, `db_query_count`.
  2. Point out that `Authorization` and `X-API-Key` headers are completely scrubbed.
  3. In the VS Code terminal, run: `php artisan test` and watch the 20 tests pass.
* **What to Speak:**  
  > *"For observability, our custom `DiagnosticRequestLogging` middleware records every incoming request into `api-diagnostics.log`. Each entry captures the exact timestamp, unique request ID, HTTP method, route, status code, execution duration in milliseconds, memory delta, and database query count. Notice that sensitive client credentials like authorization tokens and passwords are automatically redacted.*  
  > *To ensure long-term stability and regression prevention, we have a comprehensive automated test suite. Running `php artisan test` executes all 20 feature and unit tests, achieving **100% pass rate across 102 assertions** in under 3 seconds."*

---

### [Scene 6] Conclusion & Sign-Off (4:15 – 4:40)

* **What to Show on Screen:**  
  Switch back to the GitHub repository page (`https://github.com/kyleG18/BobaCraft`) or the root of VS Code showing `LABORATORY_REPORT.md` and `BobaCraft_API_Laboratory.postman_collection.json`.
* **What to Speak:**  
  > *"In conclusion, through measured database indexing, pagination, response caching, rate limiting, and structured logging, we transformed the BobaCraft API from a slow full-table-scanning prototype into a resilient, production-ready system with a 95% latency reduction and predictable error boundaries.*  
  > *All documentation, Postman collection exports, and the formal 9-section laboratory report are fully published on our GitHub repository. Thank you for listening!"*

---

## 💡 Quick Tips for a Smooth Recording

1. **Resolution:** Record in 1080p (1920x1080) so text in Postman and VS Code is crisp and readable.
2. **Zoom in:** In Postman, press `Ctrl` + `+` once or twice so your status codes and response headers are clearly visible to viewers.
3. **No Fluff:** Stick to the script times—keeping it around 4 minutes demonstrates confidence, preparation, and mastery of the rubric.
4. **Reverb reminder:** Keep `php artisan reverb:start` running in Terminal 2 while recording so live event broadcasting succeeds seamlessly!
