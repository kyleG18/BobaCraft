# ☕ Cozy Café — Real-Time Order Status Dashboard

**Course:** IPT 102 – Integrative Programming and Technologies 2  
**Module:** Module 7 – Real-Time Integrative Programming  
**Activity:** Lab Activity 7 – Building a Real-Time Event Dashboard Using WebSockets

---

## Project Overview

**Cozy Café** is a functional Laravel web application demonstrating real-time restaurant order management using Laravel Events, Broadcasting, Laravel Reverb (WebSocket server), and Laravel Echo (frontend client). When a staff member changes an order status in one browser window, all other connected windows update automatically — **no page refresh required**.

---

## Project Objectives

1. View current customer orders with ID, name, status, and timestamps
2. Create new orders via a modal form
3. Change order statuses through an inline dropdown
4. Receive automatic real-time updates in all connected browser windows
5. Monitor WebSocket connection status (Live / Connecting / Disconnected)
6. Demonstrate two-browser synchronization

---

## Architecture Flow

```
User Changes Order Status
→ Laravel Route (PATCH /orders/{id})
→ OrderController::update()
→ Database Update (orders table)
→ OrderStatusUpdated Event dispatched
→ Laravel Broadcasting (reverb driver)
→ Reverb WebSocket Server (port 8080)
→ Laravel Echo (frontend JavaScript)
→ All Connected Browser Dashboards
→ Automatic UI Update (badge, timestamp, stats — no reload)
```

### How HTTP and WebSockets Work Together

- **HTTP** handles normal requests: loading the dashboard, submitting forms, changing statuses via AJAX.
- **WebSockets** (via Reverb) maintain a persistent, open connection between the server and every browser viewing the dashboard.
- When an order is updated via HTTP, Laravel fires a broadcast event through Reverb. Every browser that has an open WebSocket connection receives the event instantly and updates its UI without needing another HTTP request.

---

## Technologies Used

| Category | Technology |
|---|---|
| Backend | Laravel 13 (PHP 8.3) |
| ORM | Eloquent |
| Database | MySQL 8.4 via Laragon |
| WebSocket Server | Laravel Reverb v1.12 |
| Frontend Listener | Laravel Echo + Pusher-js |
| Frontend Styling | Tailwind CSS v4 + Custom CSS |
| Build Tool | Vite |
| Testing | PHPUnit (via `php artisan test`) |
| Web Server | Apache (Laragon) |

---

## System Requirements

- PHP 8.3+
- Composer 2.x
- Node.js 18+ and npm
- MySQL 8.x (or MariaDB)
- Laravel-compatible web server (Laragon, XAMPP, or `php artisan serve`)

---

## Installation Instructions

### 1. Clone / Use the Project

```bash
# If using Git
git clone <repository-url> cozy-cafe
cd cozy-cafe

# Or just open the existing folder in C:\laragon\www\cozy-cafe
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` to match your local setup:

```dotenv
APP_NAME="Cozy Café"
APP_URL=http://cozy-cafe.test     # or http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cozy_cafe
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=sync

REVERB_APP_ID=cozy-cafe-app-id
REVERB_APP_KEY=cozycafeappkey12345
REVERB_APP_SECRET=cozycafeappsecret67890
REVERB_HOST="127.0.0.1"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 4. Create the Database

```sql
-- In MySQL / HeidiSQL / phpMyAdmin:
CREATE DATABASE IF NOT EXISTS cozy_cafe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Seed Sample Orders

```bash
php artisan db:seed
```

This inserts 5 sample orders for Juan Dela Cruz, Maria Santos, Angela Reyes, Mark Villanueva, and Sofia Mendoza across various statuses.

### 7. Install Frontend Dependencies

```bash
npm install
```

### 8. Build Frontend Assets

```bash
npm run build
```

For live development with HMR:

```bash
npm run dev
```

---

## Running the Application

### Terminal 1 — Laravel Web Server

```bash
# Using Laragon: ensure Apache is running (default at http://cozy-cafe.test)
# OR using built-in PHP server:
php artisan serve
# → http://localhost:8000
```

### Terminal 2 — Laravel Reverb WebSocket Server ⚡

```bash
php artisan reverb:start
# OR with debug output:
php artisan reverb:start --debug
```

You should see:
```
INFO  Starting server on 0.0.0.0:8080 (localhost).
```

> **Important:** Reverb must be running for real-time features to work. The connection indicator in the dashboard header will turn **green (Live)** when Echo successfully connects.

---

## Real-Time Demonstration (Two-Browser Test) 🎯

### Setup

1. Ensure **Apache** (or `php artisan serve`) is running
2. Ensure **Reverb** is running (`php artisan reverb:start`)
3. Ensure seed data is loaded (`php artisan db:seed`)

### Step-by-Step

**Step 1 — Browser A (Chrome)**
- Open `http://cozy-cafe.test` (or `http://localhost:8000`)
- Wait until the connection indicator shows **🟢 Live**
- Note the orders table — see Juan Dela Cruz (Pending), Sofia Mendoza (Pending)

**Step 2 — Browser B (Firefox or another Chrome tab)**
- Open the same URL in a separate window
- Wait for **🟢 Live** connection indicator
- Do NOT touch anything yet

**Step 3 — Change a Status in Browser A**
- Find "Juan Dela Cruz" (Order #1, Pending)
- Click the status dropdown → select **Preparing**
- Observe: the badge updates to blue "Preparing" in Browser A

**Step 4 — Watch Browser B (No Refresh!)**
- Browser B automatically shows Juan Dela Cruz's order as **Preparing**
- The summary card **Pending** count decreases by 1
- The **Preparing** count increases by 1
- A toast notification appears: "Order #1 (Juan Dela Cruz) is now Preparing."

**Step 5 — Repeat**
- In Browser A, change "Sofia Mendoza" to **Ready**
- Browser B updates automatically again

**Step 6 — Create a New Order**
- In Browser A, click **New Order** → enter "Maria Clara" → Create Order
- Browser B shows the new order appear in real-time without refresh

---

## Component Explanations

### Laravel Routes (`routes/web.php`)
Routes define URL endpoints. When a user visits the dashboard (`GET /`), submits a new order (`POST /orders`), or updates a status (`PATCH /orders/{id}`), the route maps the request to the correct controller method.

### OrderController (`app/Http/Controllers/OrderController.php`)
The controller handles the application logic: loading orders from the database, validating form input, saving changes, and triggering broadcast events. It acts as the bridge between the user's request and the application's data.

### Order Model (`app/Models/Order.php`)
The Eloquent model represents a database row in the `orders` table as a PHP object. It defines which fields can be mass-assigned (`fillable`) and provides status constants and badge helper methods.

### Database Migration (`database/migrations/..._create_orders_table.php`)
Migrations are version-controlled database schema changes. Running `php artisan migrate` creates the `orders` table with `id`, `customer_name`, `status`, `created_at`, and `updated_at` columns.

### OrderStatusUpdated Event (`app/Events/OrderStatusUpdated.php`)
This is the core real-time feature. When an order status changes, the controller fires (dispatches) this event. It implements `ShouldBroadcastNow`, which tells Laravel to send the event payload over WebSockets immediately (not via a queue).

### Broadcasting (`config/broadcasting.php`)
Laravel's broadcasting system serializes events and sends them to the configured driver. Here, the driver is `reverb`, which forwards the event to the Reverb WebSocket server.

### Laravel Reverb (`php artisan reverb:start`)
Reverb is a first-party Laravel WebSocket server. It maintains persistent connections to all browser clients. When it receives a broadcast event from Laravel, it pushes it to all connected browsers subscribed to the relevant channel.

### Laravel Echo (`resources/js/app.js`)
Echo is a JavaScript library that runs in the browser. It connects to Reverb via WebSocket, subscribes to channels, and calls your callback functions when events arrive. Think of it as the browser's "ears" for real-time events.

### WebSocket Connection
A WebSocket is a persistent, bidirectional communication channel between the browser and server. Unlike HTTP (where the browser must ask for data), WebSockets let the server push data to the browser at any time.

### Frontend Event Listener
In `app.js`, Echo listens on the `orders` channel for `order.status.updated` events. When received, JavaScript updates the DOM — the badge, timestamp, and summary cards — creating the real-time effect.

---

## Order Status Design

| Status | Badge Color | Meaning |
|---|---|---|
| **Pending** | Amber | Order received, awaiting preparation |
| **Preparing** | Blue | Barista is actively preparing |
| **Ready** | Green | Ready for customer pickup |
| **Completed** | Gray | Order fulfilled |

---

## Testing

### Run Automated Tests

```bash
php artisan test
```

**Tests implemented** (all 8 pass ✅):
- `test_orders_dashboard_displays_orders_and_statistics` — Dashboard loads and shows order data
- `test_can_create_new_order_with_initial_pending_status` — Order creation works + `OrderCreated` event dispatched
- `test_cannot_create_order_with_invalid_data` — Validation rejects empty name
- `test_can_update_order_status_and_dispatches_event` — Status update works + `OrderStatusUpdated` dispatched on `orders` channel with `order.status.updated` name
- `test_rejects_unsupported_or_arbitrary_status` — Invalid statuses (e.g. "ArchivedUnknown") return HTTP 422
- `test_does_not_dispatch_duplicate_event_when_status_is_unchanged` — No event fired if status hasn't changed
- Plus 2 from `ExampleTest`

### Manual WebSocket Testing Checklist

| # | Test | How to Verify |
|---|---|---|
| 1 | Laravel starts | `http://cozy-cafe.test` returns 200 |
| 2 | Database connection works | Orders show on dashboard |
| 3 | Migrations ran successfully | `php artisan migrate:status` shows DONE |
| 4 | Sample orders are created | 5 rows visible on dashboard |
| 5 | Dashboard shows DB records | Customer names match seed data |
| 6 | New orders can be created | Modal → Create → row appears |
| 7 | Status can be changed | Dropdown → select status → badge updates |
| 8 | Invalid statuses rejected | API returns 422 for unknown statuses |
| 9 | Updated status persists | Check database after change |
| 10 | Event dispatched on update | Check `php artisan test` passes |
| 11 | Reverb starts successfully | `php artisan reverb:start` shows "Starting server on 0.0.0.0:8080" |
| 12 | Browser connects to WebSocket | Header shows 🟢 Live |
| 13 | Echo subscribes to channel | Browser DevTools → Network → WS → subscribe `orders` |
| 14 | Listener receives event | Browser DevTools → WS messages show payload |
| 15 | Order row updates without refresh | Two-browser test: change in A, see in B |
| 16 | Summary cards update | Pending/Preparing/Ready counts change |
| 17 | Multiple browsers synchronized | Both A and B always show same data |
| 18 | Disconnection handled | Stop Reverb → indicator turns red → restart → turns green |
| 19 | Mobile screen usable | Open on phone/small viewport → card layout shows |

---

## Troubleshooting

### No real-time update in Browser B

1. Is Reverb running? → `php artisan reverb:start`
2. Is the connection indicator **🟢 Live**? If not, check Reverb is on port 8080
3. Open Browser DevTools (F12) → Network → WS tab → check WebSocket connection exists
4. Check `.env`: `BROADCAST_CONNECTION=reverb`, `QUEUE_CONNECTION=sync`
5. Ensure `VITE_REVERB_*` variables match `REVERB_*` and assets are rebuilt (`npm run build`)

### WebSocket connection fails (indicator stays amber/red)

1. Verify Reverb is running: `php artisan reverb:start --debug`
2. Check `REVERB_HOST` = `127.0.0.1` and `REVERB_PORT` = `8080`
3. Check `VITE_REVERB_HOST`, `VITE_REVERB_PORT` in `.env` match
4. Rebuild assets: `npm run build`
5. Check browser console for WebSocket errors
6. Ensure no firewall blocking port 8080

### Database updates, but Browser B does not update

1. Confirm `BROADCAST_CONNECTION=reverb` in `.env`
2. Confirm `QUEUE_CONNECTION=sync` (not `database` — that requires a queue worker)
3. Run `php artisan config:clear` after changing `.env`
4. Check the `OrderStatusUpdated` event implements `ShouldBroadcastNow`

### Dashboard only updates after refresh

This means Echo is not receiving events. Check:
- WebSocket connection is `Live` (not `Connecting` or `Disconnected`)
- The channel name in `app.js` matches: `echo.channel('orders')`
- The event name listener matches: `.order.status.updated` (dot-prefixed for custom broadcast names)

---

## Known Limitations

1. **No authentication** — The `orders` channel is public. A real production system should use private channels with auth middleware.
2. **No queue worker needed** — `ShouldBroadcastNow` broadcasts synchronously. In production with high traffic, switch to `ShouldBroadcast` with `QUEUE_CONNECTION=redis` and run `php artisan queue:work`.
3. **Reverb must be started manually** — Run `php artisan reverb:start` in a separate terminal before demonstrating.
4. **Single server** — Reverb's scaling (Redis) is not configured here. Sufficient for classroom demo.
5. **No HTTPS/WSS** — Development uses HTTP/WS. Production should use HTTPS with WSS.

---

## Security Considerations

> **For production deployment only (not required for this classroom activity):**

- Use **private or presence channels** (`PrivateChannel`) with authenticated users
- Define channel authorization in `routes/channels.php`
- Use **HTTPS** and **WSS** for encrypted WebSocket connections
- Store Reverb credentials in environment variables only — never in source code
- Implement rate limiting (`REVERB_APP_RATE_LIMITING_ENABLED=true`)
- Validate and sanitize all user input (already done in this app)

---

## Project Structure

```
cozy-cafe/
├── app/
│   ├── Events/
│   │   ├── OrderStatusUpdated.php  ← Core real-time event
│   │   └── OrderCreated.php        ← New order event
│   ├── Http/Controllers/
│   │   └── OrderController.php     ← Dashboard, create, update
│   └── Models/
│       └── Order.php               ← Eloquent model + status constants
├── config/
│   ├── broadcasting.php            ← Reverb driver configuration
│   └── reverb.php                  ← Reverb server settings
├── database/
│   ├── migrations/
│   │   └── ..._create_orders_table.php
│   └── seeders/
│       └── OrderSeeder.php         ← 5 sample orders
├── resources/
│   ├── css/app.css                 ← Cozy Café theme + animations
│   ├── js/app.js                   ← Laravel Echo + real-time handlers
│   └── views/orders/
│       └── index.blade.php         ← Full dashboard Blade template
├── routes/
│   ├── web.php                     ← HTTP routes
│   └── channels.php                ← WebSocket channel definitions
├── tests/Feature/
│   └── OrderTest.php               ← 6 automated feature tests
├── .env                            ← Local environment (not in git)
├── .env.example                    ← Template for new installations
└── README.md                       ← This file
```
