================================================================================
BOBACRAFT MILK TEA BAR API — ULTRA-DETAILED RECORDING GUIDE & TAGLISH SCRIPT
================================================================================
Activity : API Performance, Reliability, and Monitoring Assessment
Presenter: Kyle Gacula
Repo     : https://github.com/kyleG18/BobaCraft.git
Duration : ~2 minutes 45 seconds to 3 minutes


================================================================================
PART 1: PRE-RECORDING SETUP (GAWIN ITO BAGO MAG-RECORD)
================================================================================

[STEP 1] I-START ANG LARAGON (DATABASE)
1. Sa Windows taskbar o Desktop, buksan ang "Laragon".
2. I-click ang "Start All" button sa baba (o siguraduhing may green check /
   "MySQL" running on Port 3306).
3. I-minimize ang Laragon window (huwag i-close).


[STEP 2] I-OPEN ANG PROJECT AT 3 TERMINALS SA VS CODE
1. Buksan ang Visual Studio Code (VS Code).
2. Sa top-left menu: click "File" -> "Open Folder..."
3. Piliin ang folder: C:\Users\Kyle Gacula\OneDrive\Desktop\FinalsIPT
4. Buksan ang Terminal sa baba ng VS Code:
   - Click "Terminal" sa top menu -> "New Terminal" (o pindutin Ctrl + ` ).

5. TERMINAL 1 (API Server):
   - I-type ito at pindutin ang Enter (para malinis ang cache bago mag-record):
       php artisan cache:clear
   - Pagkatapos, i-type ito at pindutin ang Enter:
       php artisan serve
   - Hintaying lumabas ang: "Server running on [http://127.0.0.1:8000]"
   - HUWAG ISARA ang terminal na ito.

6. TERMINAL 2 (WebSocket Server - kailangan ito para gumana ang POST Create Order):
   - Sa kanang bahagi ng Terminal panel, i-click ang "+" (New Terminal) icon.
   - I-type ito at pindutin ang Enter:
       php artisan reverb:start
   - Hintaying lumabas ang: "Starting server on 0.0.0.0:8080"
   - HUWAG DIN ISARA ang terminal na ito.

7. TERMINAL 3 (Para sa Automated Tests sa dulo ng video):
   - I-click ulit ang "+" (New Terminal) icon sa kanan.
   - I-type ang:
       php artisan test
   - PAALALA: HUWAG MUNA PINDUTIN ANG ENTER. I-ready lang siya diyan para isang
     Enter na lang mamaya habang nagre-record.

8. I-OPEN ANG DIAGNOSTIC LOG FILE SA VS CODE:
   - Sa left sidebar (Explorer) ng VS Code, buksan ang folders:
       storage -> logs -> api-diagnostics.log
   - I-scroll ito hanggang sa pinakababa (para makita agad ang bagong logs mamaya).


[STEP 3] I-CHECK ANG POSTMAN LAYOUT
1. Buksan ang Postman app (dapat imported na yung collection mo).
2. Sa left sidebar, siguraduhing naka-expand (pindutin ang ">" arrow) ang LAHAT
   ng 6 folders sa ilalim ng "BobaCraft Milk Tea Bar - API Laboratory":
     [ ] 1. Performance & Benchmark
     [ ] 2. Caching & Invalidation
     [ ] 3. Input Validation & Error Handling (422 / 404)
     [ ] 4. Authentication & Authorization (401 / 403)
     [ ] 5. Rate Limiting (429)
     [ ] 6. Diagnostic Request Logging
3. Siguraduhing nakasara ang AI panel sa kanan para maluwag at malaki ang screen.
4. Pindutin ang "Ctrl" + "+" ng 1 o 2 beses para malaki at malinaw sa video ang
   Status codes (200, 422, 429), Time (ms), at Size (KB/MB).
5. PAALALA: Huwag munang pindutin ang Folder 2 (Caching) at Folder 5 (Rate Limit)
   bago mag-record! (Kung napindot mo na ang Rate Limit, maghintay muna ng
   60 seconds bago mag-record. Kung napindot mo ang Caching, mag-run ulit ng
   "php artisan cache:clear" sa Terminal 3 bago itype ang "php artisan test".)


[STEP 4] I-SETUP ANG SCREEN RECORDER
Option A — OBS Studio (Recommended):
1. Buksan ang OBS Studio.
2. Sa "Sources" box sa baba -> click "+" -> piliin "Display Capture" -> "OK".
3. Magsalita sa mic at tingnan kung gumagalaw ang green bar sa "Mic/Aux".
4. Kapag ready ka na, click "Start Recording".

Option B — Windows Built-in Recorder:
1. Pindutin ang "Win + Shift + S".
2. Sa toolbar sa taas, i-click ang Video (camera icon).
3. I-drag ang box sa buong screen mo.
4. Siguraduhing hindi naka-mute ang microphone icon sa toolbar.
5. Click "Start".


================================================================================
PART 2: EXACT CLICK-BY-CLICK DEMO & CONCISE TAGLISH SCRIPT
================================================================================

--------------------------------------------------------------------------------
SCENE 1: INTRO (0:00 - 0:15)
--------------------------------------------------------------------------------
[ANO ANG NASA SCREEN]
- Nakabukas ang VS Code (nakikita ang BobaCraft project files at api-diagnostics.log).

[ANO ANG BABASAHIN]
"Good day po. Ako si Kyle Gacula, presenting the BobaCraft Milk Tea Bar API para
sa API Performance, Reliability, and Monitoring assessment. Ipapakita ko ang
baseline vs. optimized performance sa 10,005 records, error handling, rate
limiting, caching, at diagnostic logging."


--------------------------------------------------------------------------------
SCENE 2: BASELINE VS. OPTIMIZED PERFORMANCE (0:15 - 1:05)
--------------------------------------------------------------------------------
[ANO ANG GAGAWIN SA POSTMAN -> Folder "1. Performance & Benchmark"]
1. Lumipat sa Postman window.
2. Sa Folder "1. Performance & Benchmark":
   a) I-click ang "GET Orders (Original Baseline Mode - Full Table Scan)".
   b) I-click ang blue "Send" button sa kanan.
   c) Hintaying mag-load (~0.5 sec).
   d) I-turo ng mouse cursor ang resulta sa kanang gitna ng Postman:
        - Status: 200 OK
        - Time: ~577 ms (o katulad na mataas na ms)
        - Size: ~2.43 MB
   e) I-scroll saglit pababa ang JSON body para makita ng prof na lahat ng 10,005
      records ay binato nang sabay-sabay.

3. Next, sa parehong Folder 1:
   a) I-click ang "GET Orders (Optimized Mode - Paginated & Indexed)".
   b) I-click ang blue "Send" button.
   c) I-turo ng mouse cursor ang resulta:
        - Status: 200 OK
        - Time: ~28 ms (sobrang bilis!)
        - Size: ~6.28 KB
   d) Ituro sa JSON body yung "per_page": 25 at "total": 10005.

4. Next, sa parehong Folder 1:
   a) I-click ang "GET Live Benchmark Telemetry (Side-by-Side)".
   b) I-click ang blue "Send" button.
   c) Ituro sa JSON body yung "speedup_factor" (~20x) at
      "payload_reduction_percent" (~99.7%).

[ANO ANG BABASAHIN HABANG NAGKI-CLICK]
"Sa baseline mode (?mode=original), nag-f-full table scan at filesort ito sa
lahat ng 10,005 rows—inaabot ng 577 ms, 2.43 MB payload, at halos 13 MB memory.

Sa optimized mode (?mode=optimized), nag-add kami ng compound B-tree index sa
(status, created_at), column projection, at pagination na 25 rows per page.

Resulta: bumaba ang latency to 28 ms (20x faster) at lumiit ang payload ng
99.7%, down to 6.28 KB. Dito sa /benchmark endpoint, kita ang live
side-by-side comparison."


--------------------------------------------------------------------------------
SCENE 3: RELIABILITY & ERROR HANDLING (1:05 - 1:45)
--------------------------------------------------------------------------------
[ANO ANG GAGAWIN SA POSTMAN -> Folders "3" at "4"]
1. Sa Folder "3. Input Validation & Error Handling (422 / 404)":
   a) Click "POST Order (Invalid Payload - 422)" -> click "Send".
      - Ituro ang Status: "422 Unprocessable Content" at yung JSON body sa baba
        ("error.code": "VALIDATION_FAILED", walang stack trace).
   b) Click "GET Order (Non-Existent ID - 404)" -> click "Send".
      - Ituro ang Status: "404 Not Found" ("error.code": "RESOURCE_NOT_FOUND").

2. Sa Folder "4. Authentication & Authorization (401 / 403)":
   a) Click "GET Admin Verify (Missing Key - 401)" -> click "Send".
      - Ituro ang Status: "401 Unauthorized".
   b) Click "GET Admin Verify (Kiosk Key - 403 Forbidden)" -> click "Send".
      - Ituro ang Status: "403 Forbidden".

[ANO ANG BABASAHIN HABANG NAGKI-CLICK]
"Para sa reliability, uniform ang JSON error format namin at walang nali-leak na
stack trace o credentials:
- Invalid input -> 422 Unprocessable Content
- Missing order -> 404 Not Found
- No API key -> 401 Unauthorized
- Wrong role key -> 403 Forbidden
Lahat may standard error.code, timestamp, at request_id."


--------------------------------------------------------------------------------
SCENE 4: RATE LIMITING & CACHING (1:45 - 2:20)
--------------------------------------------------------------------------------
[ANO ANG GAGAWIN SA POSTMAN -> Folders "5" at "2"]
1. Sa Folder "5. Rate Limiting (429)":
   a) Click "GET Rate Limit Test (Trigger 429)".
   b) Pindutin ang "Send" button nang sunod-sunod at mabilis 6 TIMES:
      - Click 1..5 -> makikita mong "200 OK".
      - Click 6 -> lalabas ang "429 Too Many Requests"!
   c) Sa response section sa baba, i-click ang "Headers" tab (katabi ng "Body")
      at ituro ng mouse ang "Retry-After" header.

2. Sa Folder "2. Caching & Invalidation":
   a) Click "GET Order Statistics (Read-Heavy Cache)".
   b) Click "Send" (1st click).
   c) Sa response section sa baba, i-click ang "Headers" tab -> ituro ng mouse
      ang "X-Cache: MISS" at tingnan ang Time (~25-60 ms).
   d) Pindutin ulit ang "Send" (2nd click).
   e) Ituro ng mouse ang "X-Cache: HIT" at ang Time (bumaba sa ~1-3 ms!).
   f) (Optional bonus proof): Click "POST Create Order (Triggers Cache Invalidation)"
      -> click "Send" ("201 Created"), tapos balik sa "GET Order Statistics" ->
      click "Send" para makitang bumalik sa "X-Cache: MISS".

[ANO ANG BABASAHIN HABANG NAGKI-CLICK]
"Sa rate limiting, may 5 req/min limit sa test route—pagdating sa 6th request,
nagre-return ng 429 Too Many Requests with Retry-After header.

Sa caching ng /orders/stats, unang request ay X-Cache: MISS, tapos sa susunod
ay X-Cache: HIT in 1.2 ms—at automatic nag-i-invalidate kapag may bagong order
o status update."


--------------------------------------------------------------------------------
SCENE 5: LOGS, AUTOMATED TESTS & OUTRO (2:20 - 2:55)
--------------------------------------------------------------------------------
[ANO ANG GAGAWIN SA VS CODE]
1. Lumipat sa VS Code window.
2. Sa Editor, i-click ang tab ng "api-diagnostics.log" at i-scroll sa pinakababa
   para makita ang mga bagong JSON log lines na kaka-generate lang mula sa
   Postman requests mo.
3. I-turo ng mouse ang fields sa isang JSON log line:
   - timestamp, request_id, method, path, status, duration_ms, db_query_count.
   (Sabihin na wala diyan ang API keys at passwords.)
4. Sa baba, i-click ang Terminal 3 (kung saan nakatype na ang "php artisan test")
   at pindutin ang "Enter".
5. Hintaying lumabas ang lahat ng green checkmarks:
     "Tests: 20 passed (102 assertions)"
6. Pindutin ang "Stop Recording" sa OBS o Windows recorder.

[ANO ANG BABASAHIN]
"Para sa monitoring, naka-log sa api-diagnostics.log ang bawat request with
timestamp, request_id, status, duration, at DB query count—while nire-redact
ang tokens at passwords.

Lastly, sa php artisan test, all 20 tests and 102 assertions are passing.

Nasa GitHub na rin ang Postman collection at full 9-section report. Maraming
salamat po!"


================================================================================
PART 3: TROUBLESHOOTING HABANG NAGRE-RECORD
================================================================================
- Nag-500 Error ba ang POST Create Order?
  -> Tingnan ang Terminal 2 sa VS Code. Baka hindi naka-start ang:
     php artisan reverb:start
- Nag-429 agad sa 1st click ng Rate Limit Test?
  -> Maghintay lang ng 60 seconds para mag-reset ang 1-minute window, tapos
     ulitin ang Scene 4.
- Lumabas na X-Cache: HIT agad sa 1st click ng Stats?
  -> I-run sa terminal ang "php artisan cache:clear" bago i-record ang Scene 4,
     o i-send muna ang "POST Create Order" para ma-invalidate ang cache.
================================================================================
