# 🏎️ AutoFix Pro — Real-Time Service Bay Dispatch Control
## Technical Documentation: Real-Time Event-Driven Dashboard Architecture

**Course:** IPT 102 – Integrative Programming and Technologies 2  
**Module:** Module 7 – Real-Time Integrative Programming  
**Activity:** Lab Activity 7 – Building a Real-Time Event Dashboard Using WebSockets  
**Project Name:** AutoFix Pro: Real-Time Vehicle Service Bay & Repair Dispatch Tracker  
**Author / Submitted By:** Kyle Gacula & Partner  

---

## 1. Executive Summary & Purpose

Modern automotive repair facilities, service centers, and dealerships require continuous, immediate coordination among three key operational zones:
1. **The Front Desk Service Advisors** (who check in vehicles and assign repair orders).
2. **The Mechanic Bay Technicians** (who work on hydraulic lifts and diagnose engines).
3. **The Customer Waiting Lounge** (where vehicle owners track their car’s servicing status).

Traditional web applications rely on manual page refreshes or periodic HTTP polling (sending repetitive requests every few seconds), which introduces latency, consumes unnecessary server bandwidth, and leads to stale data display.

**AutoFix Pro** solves this by implementing an **Event-Driven, Real-Time Architecture** using **WebSockets**. When an advisor or mechanic updates the service stage of any vehicle, the update is broadcast across all open screens and terminals within milliseconds with **zero page reloads**.

---

## 2. Technologies & Tools Used

| Layer / Component | Technology | Version / Specifications | Role & Functionality |
|---|---|---|---|
| **Backend Framework** | **Laravel** | v11 / v12 (PHP 8.3) | Handles HTTP routing, REST API requests, business validation, and event orchestration. |
| **Relational Database** | **MySQL** | v8.4 (via Laragon) | Persists vehicle job orders, timestamps, customer details, and repair stages. |
| **ORM** | **Laravel Eloquent** | Built-in | Manages database transactions, status mutations, and query aggregation. |
| **WebSocket Server** | **Laravel Reverb** | v1.12 | First-party, high-performance WebSocket server engine running on port `8080`. |
| **Client Event Listener** | **Laravel Echo** | v2.4 | JavaScript library in the browser that connects to Reverb channels and dispatches DOM updates. |
| **WebSocket Client Protocol** | **Pusher JS Protocol** | Compatible socket framing | Powers the persistent, full-duplex TCP socket between the browser and Reverb. |
| **Frontend Styling** | **Tailwind CSS** | v4 (Industrial Dark Theme) | Custom garage aesthetics styled with Deep Charcoal (`#020617`), Racing Amber (`#F59E0B`), and Sky Blue. |
| **Asset Bundler** | **Vite** | v8.3 | Compiles modern JavaScript event listeners and styles with optimized assets. |
| **Testing Suite** | **PHPUnit / PEST** | Integrated with Laravel | Automated feature tests validating order endpoints, database mutations, and broadcast dispatches. |

---

## 3. How the Real-Time Event Dashboard Works

### 3.1 Architectural Workflow Diagram

```
       [ Client Terminal A: Service Advisor ]
                         │
                         ▼ (1) HTTP PATCH /orders/{id}
             [ Laravel OrderController ]
                         │
                         ▼ (2) Commit to MySQL Database
                  [ MySQL 8.4 ]
                         │
                         ▼ (3) Fire OrderStatusUpdated Event
           [ Laravel Broadcasting (Reverb Driver) ]
                         │
                         ▼ (4) WebSocket Frame over TCP:8080
          [ Laravel Reverb WebSocket Server ]
                         │
          ┌──────────────┴──────────────┐
          ▼ (5) Broadcast Channel 'orders'
[ Client Terminal B: Mechanic Bay ]   [ Client Terminal C: Customer Lounge ]
          │                                     │
          ▼                                     ▼
(6) Echo Intercepts Event             (6) Echo Intercepts Event
(7) Update Status Badge               (7) Update Status Badge
(8) Recalculate Metric Counters       (8) Recalculate Metric Counters
(9) Display Animated Toast Popup      (9) Display Animated Toast Popup
```

---

### 3.2 Step-by-Step Lifecycle of a Real-Time Event

#### Step 1: User Action & HTTP Request
- An advisor on **Terminal A** opens the status dropdown for job `JO-0001` (e.g., *Toyota Vios - ABC 1234*) and changes the stage from **Checked-In** to **On Lift / Bay Repair**.
- JavaScript sends an asynchronous `PATCH /orders/1` request to the Laravel backend carrying CSRF security verification and the requested status.

#### Step 2: Database Persistence & Validation
- In `app/Http/Controllers/OrderController.php`, the request is received.
- The controller validates that the status is valid (`Pending`, `Preparing`, `Ready`, or `Completed`).
- The database record is updated in the `orders` table in MySQL (`status = 'Preparing'`).

#### Step 3: Event Firing (`OrderStatusUpdated`)
- Immediately following the database commit, Laravel triggers the event:
  ```php
  broadcast(new OrderStatusUpdated($order));
  ```
- The `OrderStatusUpdated` class implements `Illuminate\Contracts\Broadcasting\ShouldBroadcastNow`.
- Because it uses `ShouldBroadcastNow`, the event bypasses background worker queue queues and is dispatched **immediately**.

#### Step 4: Broadcasting Payload Formation
- The event constructs a structured JSON payload containing:
  - `order`: Job Order ID, Customer/Vehicle details, updated status, badge color class, formatted timestamp.
  - `stats`: Real-time aggregated workshop counts (`total`, `pending`, `preparing`, `ready`).
- It designates the public broadcast channel:
  ```php
  public function broadcastOn(): array
  {
      return [new Channel('orders')];
  }
  ```
  with the event alias:
  ```php
  public function broadcastAs(): string
  {
      return 'order.status.updated';
  }
  ```

#### Step 5: Laravel Reverb Socket Distribution
- The **Laravel Reverb WebSocket server** running on `http://0.0.0.0:8080` catches the broadcast payload from the local Laravel application.
- It pushes a WebSocket message frame over the persistent connection to all connected browsers subscribed to the `orders` channel.

#### Step 6: Browser Interception via Laravel Echo
- In all connected client browsers, **Laravel Echo** is actively listening:
  ```javascript
  window.Echo.channel('orders')
      .listen('.order.status.updated', (event) => {
          updateOrderStatusUI(event.order);
          updateMetricsUI(event.stats);
          showToastNotification(event.order);
      });
  ```

#### Step 7: Instant UI Updates (Zero Page Refresh)
Without reloading the page, the DOM modifies:
1. **Badge Color & Text**: Changes from yellow *Checked-In* to blue *On Lift / Bay Repair*.
2. **Timestamp**: Updates the "Last Scanned" timestamp to the exact time of the change.
3. **Counter Metrics**: Increments the *On Lift / Repair* card while decrementing *Checked-In*.
4. **Toast Notification**: Displays an industrial-styled notification in the top-right corner informing staff and customers of the vehicle's progress.

---

## 4. Service Bay Lifecycle Mapping

| Backend Status | Workshop Display Label | Color Theme | Practical Automotive Meaning |
|---|---|---|---|
| `Pending` | **Checked-In** | Amber (`#F59E0B`) | Vehicle arrived and checked-in; waiting for an open hydraulic lift. |
| `Preparing` | **On Lift / Bay Repair** | Sky Blue (`#38BDF8`) | Actively undergoing PMS, mechanical repair, or OBD diagnostic scan. |
| `Ready` | **QC & Carwash** | Emerald (`#34D399`) | Mechanical work complete; final quality inspection & exterior wash. |
| `Completed` | **Ready for Pickup** | Violet / Indigo (`#818CF8`) | In customer releasing bay; final inspection signed and ready for release. |

---

## 5. Key Advantages of This Architecture

1. **Full-Duplex Communication**: Unlike HTTP polling where clients repeatedly ask the server for updates, WebSockets maintain an open, bidirectional connection where the server pushes data only when something actually changes.
2. **Low Latency & High Performance**: Updates propagate across clients in less than 50 milliseconds.
3. **Reduced Server Load**: Eliminates redundant database reads caused by polling timers.
4. **Resilient Connection Handling**: Laravel Echo includes built-in reconnect logic; if network connection fluctuates, it automatically reconnects and updates the visual status dot (`🟢 Live` / `🔴 Disconnected`).

---

## 6. How to Run & Verify

1. **Start MySQL**:
   - Ensure MySQL is running on port `3306` in Laragon (**Start All**).
2. **Start Web Server**:
   ```bash
   php artisan serve
   ```
3. **Start Reverb WebSocket Server**:
   ```bash
   php artisan reverb:start
   ```
4. **Open Dual Browser Windows**:
   - Open two side-by-side Chrome windows to `http://127.0.0.1:8000`.
   - Update a status in Window 1 and observe instantaneous real-time synchronization in Window 2!
