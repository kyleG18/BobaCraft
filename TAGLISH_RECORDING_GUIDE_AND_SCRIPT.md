# 🎬 BobaCraft API Lab — Detailed Recording Guide & Concise Taglish Script

*(Mobile-friendly guide para mabasa mo sa phone habang nagre-record sa PC.)*

---

## 🛠️ PART 1: Pre-Recording Setup (Gawin Bago Mag-Record)

### 1. Laragon
- Buksan ang **Laragon** ➔ click **Start All** (siguraduhing running ang **MySQL** on Port `3306`).

### 2. VS Code (`C:\Users\Kyle Gacula\OneDrive\Desktop\FinalsIPT`)
Magbukas ng **3 terminals** sa baba (`Terminal ➔ New Terminal`, tapos `+` icon):
- **Terminal 1:**
  ```powershell
  php artisan cache:clear
  php artisan serve
  ```
  *(Hintayin lumabas: `Server running on [http://127.0.0.1:8000]`)*
- **Terminal 2:**
  ```powershell
  php artisan reverb:start
  ```
  *(Hintayin lumabas: `Starting server on 0.0.0.0:8080`)*
- **Terminal 3:**
  - I-type ang `php artisan test` *(pero **HUWAG muna pindutin ang Enter**).*
- **Editor Tab:**
  - Buksan ang `storage/logs/api-diagnostics.log` at i-scroll sa pinakababa.

### 3. Postman
- Siguraduhing naka-expand lahat ng **6 folders** sa ilalim ng `BobaCraft Milk Tea Bar - API Laboratory`:
  - `1. Performance & Benchmark`
  - `2. Caching & Invalidation`
  - `3. Input Validation & Error Handling (422 / 404)`
  - `4. Authentication & Authorization (401 / 403)`
  - `5. Rate Limiting (429)`
  - `6. Diagnostic Request Logging`
- **PAALALA:** Huwag muna pindutin ang Folder 2 (`Caching`) at Folder 5 (`Rate Limiting`) bago mag-record para fresh ang `X-Cache: MISS` at hindi pa ubos ang 5 req/min limit.

---

## 🎙️ PART 2: Step-by-Step Demo & Taglish Script (~2:45 mins)

*(Pindutin ang **Start Recording** sa OBS o `Win + Shift + S` Video, tapos basahin ito habang nagki-click.)*

---

### STEP 1: Intro (0:00 – 0:15)
* **ACTION:** Ipakita sa screen ang **GitHub repo** (`kyleG18/BobaCraft`) o **VS Code**.
* **SAY:**
  > *"Good day po. Ako si Kyle Gacula, presenting the **BobaCraft Milk Tea Bar API** para sa **API Performance, Reliability, and Monitoring** assessment. Ipapakita ko ang baseline vs. optimized performance sa 10,005 records, error handling, rate limiting, caching, at diagnostic logging."*

---

### STEP 2: Baseline vs. Optimized (0:15 – 1:05)
* **ACTION (Postman ➔ Folder `1. Performance & Benchmark`):**
  1. Click **`GET Orders (Original Baseline Mode - Full Table Scan)`** ➔ **Send**.
     - *Ituro ang:* `200 OK`, Time (`~577 ms`), Size (`~2.43 MB`).
  2. Click **`GET Orders (Optimized Mode - Paginated & Indexed)`** ➔ **Send**.
     - *Ituro ang:* `200 OK`, Time (`~28 ms`), Size (`~6.28 KB`).
  3. Click **`GET Live Benchmark Telemetry (Side-by-Side)`** ➔ **Send**.
     - *Ituro ang:* `speedup_factor` at `payload_reduction_percent` sa JSON.
* **SAY:**
  > *"Sa baseline mode (`?mode=original`), nag-f-full table scan at `filesort` ito sa lahat ng 10,005 rows—inaabot ng **577 ms**, **2.43 MB** payload, at halos **13 MB** memory.*
  >
  > *Sa optimized mode (`?mode=optimized`), nag-add kami ng compound B-tree index sa `(status, created_at)`, column projection, at pagination na 25 rows per page.*
  >
  > *Resulta: bumaba ang latency to **28 ms** (20x faster) at lumiit ang payload ng **99.7%**, down to **6.28 KB**. Dito sa `/benchmark` endpoint, kita ang live side-by-side comparison."*

---

### STEP 3: Error Handling & Security (1:05 – 1:45)
* **ACTION (Postman ➔ Folders `3` and `4`):**
  1. Folder `3`: Click **`POST Order (Invalid Payload - 422)`** ➔ **Send** *(ituro ang `422`)*.
  2. Folder `3`: Click **`GET Order (Non-Existent ID - 404)`** ➔ **Send** *(ituro ang `404`)*.
  3. Folder `4`: Click **`GET Admin Verify (Missing Key - 401)`** ➔ **Send** *(ituro ang `401`)*.
  4. Folder `4`: Click **`GET Admin Verify (Kiosk Key - 403 Forbidden)`** ➔ **Send** *(ituro ang `403`)*.
* **SAY:**
  > *"Para sa reliability, uniform ang JSON error format namin at walang nali-leak na stack trace o credentials:*
  > - *Invalid input ➔ **422 Unprocessable Content***
  > - *Missing order ➔ **404 Not Found***
  > - *No API key ➔ **401 Unauthorized***
  > - *Wrong role key ➔ **403 Forbidden***
  >
  > *Lahat may standard `error.code`, `timestamp`, at `request_id`."*

---

### STEP 4: Rate Limiting & Caching (1:45 – 2:20)
* **ACTION (Postman ➔ Folders `5` and `2`):**
  1. Folder `5`: Click **`GET Rate Limit Test (Trigger 429)`** ➔ pindutin ang **Send nang mabilis 6 times**.
     - Sa 6th click, ituro ang **`429 Too Many Requests`** at buksan ang **Headers** tab para ituro ang **`Retry-After`**.
  2. Folder `2`: Click **`GET Order Statistics (Read-Heavy Cache)`** ➔ **Send** (1st click).
     - Buksan ang **Headers** tab ➔ ituro ang **`X-Cache: MISS`**.
     - Pindutin ulit ang **Send** (2nd click) ➔ ituro ang **`X-Cache: HIT`** at tingnan ang Time (`~1-3 ms`).
* **SAY:**
  > *"Sa rate limiting, may 5 req/min limit sa test route—pagdating sa 6th request, nagre-return ng **429 Too Many Requests** with `Retry-After` header.*
  >
  > *Sa caching ng `/orders/stats`, unang request ay `X-Cache: MISS`, tapos sa susunod ay `X-Cache: HIT` in **1.2 ms**—at automatic nag-i-invalidate kapag may bagong order o status update."*

---

### STEP 5: Logs, Tests & Outro (2:20 – 2:55)
* **ACTION (VS Code):**
  1. Lumipat sa **VS Code** ➔ ipakita ang **`storage/logs/api-diagnostics.log`** (scroll sa pinakababa).
  2. Click **Terminal 3** (kung saan nakatype ang `php artisan test`) ➔ pindutin ang **Enter**.
  3. Hintaying lumabas ang **`20 passed (102 assertions)`**, tapos **Stop Recording**.
* **SAY:**
  > *"Para sa monitoring, naka-log sa `api-diagnostics.log` ang bawat request with timestamp, `request_id`, status, duration, at DB query count—while nire-redact ang tokens at passwords.*
  >
  > *Lastly, sa `php artisan test`, all **20 tests and 102 assertions** are passing.*
  >
  > *Nasa GitHub na rin ang Postman collection at full 9-section report. Maraming salamat po!"*

---

## 🆘 Quick Troubleshooting
- **Nag-`500` ba sa `POST`?** Check Terminal 2 — baka hindi running ang `php artisan reverb:start`.
- **`429` agad sa 1st click?** Maghintay ng 60 seconds bago ulitin ang Step 4.
- **`X-Cache: HIT` agad sa 1st click?** I-run ang `php artisan cache:clear` sa terminal.
