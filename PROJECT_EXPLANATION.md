# 🧋 BobaCraft — Real-Time Milk Tea Bar & Drinks Dispatch Tracker
## System Documentation & Technical Laboratory Report

**Course:** IPT 102 – Integrative Programming and Technologies 2  
**Module:** Module 7 – Real-Time Integrative Programming  
**Activity:** Lab Activity 7 – Building a Real-Time Event Dashboard Using WebSockets  
**Project Title:** BobaCraft: Real-Time Milk Tea Barista Display System (KDS) & Order Dispatch  
**Database:** `bobacraft_db` (MySQL 8.4 via Laragon)  
**Submitted By:** Kyle Gacula & Partner  

---

## 1. Project Overview & Motivation

In fast-paced bubble tea lounges, boba cafes, and express beverage counters, rapid order turnover is critical. Customers customize every drink with distinct tea bases, sweetness levels, ice preferences, and toppings (such as brown sugar boba, grass jelly, egg pudding, and cream cheese foam).

Traditional ordering systems that rely on printed paper stickers or static web screens create communication bottlenecks between the **cashier order terminal**, the **tea brewing & shaker baristas**, the **cup sealing machine**, and the **customer lounge pickup display**. If a screen requires manual browser reloading, baristas either have to shout across a noisy shaker bar or risk having cold, diluted drinks sitting on the counter.

**BobaCraft** is an event-driven **Barista Kitchen Display System (KDS)** tailored specifically for craft milk tea shops. It coordinates customer drink orders through 4 live preparation stages:
$$\text{Order Queued / Brewing} \longrightarrow \text{Shaking \& Pearls} \longrightarrow \text{Sealed \& At Bar} \longrightarrow \text{Claimed / Enjoy!}$$

Powered by **bidirectional WebSockets** via **Laravel Reverb**, any bar station update instantly synchronizes across all counter terminals and customer pickup displays with **zero page reloads**.

---

## 2. Technologies & Tools Used

| Layer / Role | Technology | Description |
|---|---|---|
| **Backend Framework** | Laravel 11/12 (PHP 8.3) | Manages drink order business logic, Eloquent ORM, REST routing, and event broadcasting. |
| **Relational Database** | MySQL 8.4 (via Laragon) | Persists customer orders, drink customizations, timestamps, and stage logs (`bobacraft_db`). |
| **ORM** | Laravel Eloquent | Encapsulates database models, mass assignment security, and real-time status mutations. |
| **WebSocket Engine** | Laravel Reverb (v1.12) | High-throughput, first-party WebSocket server running locally on port `8080`. |
| **Frontend Event Client** | Laravel Echo | Client-side JavaScript library that subscribes to WebSocket channels and catches broadcasts. |
| **Transport Protocol** | Pusher-JS Protocol | Handles persistent TCP socket framing compatible with Laravel Reverb. |
| **Frontend Styling** | Tailwind CSS v4 + Dark Boba Aesthetic | Warm roasted boba brown (`#130F0C`), Brown Sugar Gold (`#F59E0B`), Taro Lavender (`#C084FC`), and Fresh Matcha Green (`#10B981`). |
| **Asset Bundler** | Vite v8.3 | Compiles JavaScript event listeners and modern stylesheet components into standalone production assets. |

---

## 3. System Architecture & Event Lifecycle

```
[Cashier POS Terminal A]
         │
         ▼ (1) HTTP PATCH /orders/{id}
[Laravel Router & OrderController]
         │
         ▼ (2) Persist Stage in MySQL
    [ bobacraft_db ]
         │
         ▼ (3) Fire OrderStatusUpdated Event
[Laravel Broadcasting System (reverb driver)]
         │
         ▼ (4) WebSocket Frame over TCP:8080
[Laravel Reverb WebSocket Server]
         │
         ├─────────────────────────────────────────┐
         ▼ (WebSocket Frame)                       ▼ (WebSocket Frame)
[Barista Shaker Station (Echo Listener)]  [Customer Pickup Display (Echo Listener)]
         │                                         │
         ▼                                         ▼
[Instant Status Badge Update]             [Instant Status Badge Update]
[Active Bar Counters Recalculated]        [Active Bar Counters Recalculated]
[Slide-In Toast Notification]             [Slide-In Toast Notification]
```

### Step-by-Step Architectural Flow:

1. **User Action**: A barista at the shaker counter selects a drink ticket (e.g. *Cup #201 — Brown Sugar Boba Fresh Milk*) and updates its stage from **Order Queued / Brewing** to **Shaking & Pearls**.
2. **Background Request**: Client-side JavaScript dispatches an asynchronous `PATCH` request with CSRF security verification to `/orders/{id}` without reloading the page.
3. **Controller Validation & Database Commit**: `OrderController` validates the stage, commits the change to MySQL (`bobacraft_db`), and refreshes the timestamp.
4. **Immediate Event Dispatch**: Laravel fires the `OrderStatusUpdated` event. Implementing `ShouldBroadcastNow` guarantees instant socket delivery without queue worker latency.
5. **Reverb WebSocket Fan-Out**: The Reverb WebSocket daemon (port 8080) broadcasts the event across the public `orders` channel.
6. **Laravel Echo Client Update**: In all connected browser windows (cashier and pickup screens), Laravel Echo intercepts the broadcast and updates the DOM, badge colors, timestamps, summary counters, and slide-in notifications within 50 milliseconds.

---

## 4. Milk Tea Order Lifecycle Mapping

| Backend Status | Tea Bar Display Label | Badge Theme | Meaning in Boba Bar Operations |
|---|---|---|---|
| `Pending` | **Order Queued / Brewing** | Brown Sugar Gold (`#F59E0B`) | Ticket printed; loose leaf tea brewing or espresso extracting. |
| `Preparing` | **Shaking & Pearls** | Taro Lavender (`#C084FC`) | Warm boba pearls scooped into cup; milk and ice shaken. |
| `Ready` | **Sealed & At Bar** | Fresh Matcha Green (`#10B981`) | Cup sealed with protective film; straw inserted; ready at counter. |
| `Completed` | **Claimed / Enjoy!** | Ice Sky Blue (`#7DD3FC`) | Handed to customer; ticket closed and archived. |

---

## 5. Verification & Testing

- **Automated Feature Tests**: 8 tests covering drink creation, status validation, database persistence, and broadcast dispatching passed (`php artisan test`).
- **Connection Health Indicator**: Visual pulse dot in the header monitors WebSocket connection states (`Tea Bar Online`, `Connecting...`, `Tea Bar Offline`).
- **Dual-Screen Synchronization**: Verified concurrently across dual side-by-side Chrome windows with instantaneous WebSocket synchronization.

---

## 6. How to Run in VS Code

1. Ensure **MySQL** is running in Laragon (Port 3306).
2. Open `C:\Users\Kyle Gacula\OneDrive\Desktop\FinalsIPT` in VS Code.
3. Open two terminal tabs (<kbd>Ctrl</kbd> + <kbd>`</kbd>):
   - **Terminal 1 (Web Server):**
     ```powershell
     php artisan serve
     ```
   - **Terminal 2 (WebSocket Server):**
     ```powershell
     php artisan reverb:start
     ```
4. Open `http://127.0.0.1:8000` in two side-by-side Chrome windows to test live boba order synchronization!
