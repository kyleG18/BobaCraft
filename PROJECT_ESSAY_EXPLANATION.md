# Lab Activity 7: Building a Real-Time Milk Tea Barista Dashboard (BobaCraft)
**Course:** IPT 102 – Integrative Programming and Technologies 2  
**Module:** Module 7 – Real-Time Event Driven WebSockets  
**Submitted by:** Kyle Gacula & Partner  

---

### Project Overview

For this final laboratory activity, our group built **BobaCraft**, a real-time Milk Tea Barista Display System (KDS) and order tracking dashboard designed for modern bubble tea shops.

In a busy milk tea shop, customers rarely order standard off-the-shelf items. Almost every single cup involves customized choices—tea bases, sweetness percentages (0%, 25%, 50%, 70%, 100%), ice levels, and various toppings like brown sugar pearls, egg pudding, grass jelly, or cream cheese foam. Because of this complexity, baristas working at the shaker table, the tea brewing counter, and the cup sealing station need to be completely in sync with the front cashier and the customers waiting in the lobby.

The main problem with traditional web applications in this kind of shop is communication latency. If the system relies on manual page refreshing, cashiers and baristas constantly have to call out drink numbers across a loud blender and shaker counter to check if a drink is ready. If someone forgets to reload the page, the lobby display shows stale information, causing ice to melt and drinks to sit on the counter longer than they should.

We developed **BobaCraft** to eliminate this delay entirely. Whenever a barista scoops the pearls and begins shaking a drink or seals the cup, they click a single dropdown on their station tablet. Instantly, without anyone touching a refresh button, every connected terminal in the store—including the customer pickup monitor—updates in real time.

---

### Why We Chose WebSockets Over Traditional Polling

When building web applications, the standard approach is the traditional HTTP request-response cycle: the browser asks for a webpage, the server sends it back, and the connection closes immediately. If you want the page to stay up to date, developers usually resort to polling—setting up an interval script in JavaScript that sends a request to the server every few seconds asking if anything changed.

While polling is easy to implement, it has serious drawbacks in an active shop environment:
- **Wasted Server Resources:** If you set a 3-second timer, the browser sends dozens of requests every minute even when no new drinks have been ordered. Most responses come back completely empty, wasting server CPU and database connections.
- **Artificial Lag:** If you increase the interval to 10 or 15 seconds to save server resources, updates appear delayed, defeating the purpose of an instant kitchen board.

To solve this, we used **WebSockets** powered by Laravel Reverb. WebSockets allow the browser and the server to maintain a single, persistent two-way connection. When the page loads, the socket handshake occurs once and stays connected in the background. The server only pushes data when an actual event happens, such as when a barista bumps a drink status. The update arrives on other screens within 30 to 50 milliseconds without reloading or screen flickering.

---

### Technologies We Used and How They Fit Together

We used a modern full-stack Laravel toolchain to keep the architecture clean and reliable:

1. **Laravel 11 & PHP 8.3:** Laravel serves as our backend engine. It manages REST routes, validates order inputs, updates database records using Eloquent models, and handles event broadcasting.
2. **MySQL 8.4 (via Laragon):** We created a dedicated database named `bobacraft_db` running on port 3306. Storing order records in MySQL ensures that all drink tickets, customer details, timestamps, and stages are safely persisted and survive server restarts.
3. **Laravel Reverb:** Instead of relying on expensive third-party cloud services like Pusher or setting up a separate Node.js server, we utilized Laravel Reverb. It is Laravel’s official, high-throughput WebSocket server running locally on port 8080.
4. **Laravel Echo & Pusher Protocol:** On the client side, we used Laravel Echo to connect to port 8080 and subscribe to the `orders` broadcast channel. Echo listens for `.order.status.updated` and `.order.created` events and automatically executes our JavaScript UI updates.
5. **Tailwind CSS v4 (Dark Boba Aesthetic):** We designed a custom theme inspired by modern bubble tea bars—deep roasted boba brown backgrounds (`#130F0C`), warm golden honey accents (`#F59E0B`), taro purple highlights (`#C084FC`), and fresh matcha green badges (`#10B981`) for easy readability under bright cafe lighting.
6. **Vite:** Vite compiles and bundles our JavaScript and CSS into optimized production files, ensuring fast load times and clean delivery.

---

### How the Live Event Pipeline Works

Here is the exact journey of an update when a barista changes an order status:

1. **User Action:** The barista at the shaker counter selects Cup #201 (*Brown Sugar Boba Fresh Milk*) and updates the dropdown from **Order Queued / Brewing** to **Shaking & Pearls**.
2. **Asynchronous Request:** JavaScript fires an AJAX `PATCH` request in the background to `/orders/{id}` with the new status and a CSRF token. The page does not refresh.
3. **Database Mutation:** Laravel's `OrderController` receives the request, validates that the submitted stage is legitimate, and updates the row in the `bobacraft_db` MySQL database.
4. **Event Dispatch:** Right after the database confirms the save, Laravel triggers the `OrderStatusUpdated` event. Because this event implements `ShouldBroadcastNow`, it skips any queue delay and immediately pushes the payload to the Reverb WebSocket daemon on port 8080.
5. **Reverb Broadcast:** Reverb streams the JSON packet (including cup ID, drink description, updated badge color, bump timestamp, and recalculated summary metrics) to all active browsers subscribed to the `orders` channel.
6. **DOM Update Across the Shop:** On the customer pickup display and the cashier screen, Laravel Echo catches the broadcast. A JavaScript function updates the badge to taro purple, plays a slide-in toast notification, updates the "Last Bar Scan" time, and recalculates the counter cards at the top of the dashboard.

The entire process takes less than a tenth of a second, providing a completely responsive experience.

---

### Testing and Demonstration

We tested the application locally by running:
1. `php artisan serve` for the web server on `http://127.0.0.1:8000`.
2. `php artisan reverb:start` for the WebSocket daemon on port 8080.
3. MySQL running via Laragon on port 3306.

We verified real-time synchronization by opening two separate Google Chrome windows side by side to `http://127.0.0.1:8000`. In the left window (Barista Station), changing a drink status or submitting a new drink ticket instantly updated the right window (Customer Pickup Screen) with zero lag. We also ran our automated test suite (`php artisan test`), and all 8 feature tests passed successfully with 25 assertions.

---

### Key Takeaways

Building BobaCraft demonstrated the practical value of event-driven architecture in integrative programming. Traditional web development often treats databases as static storage that users only view on command. However, in real-world commercial operations like beverage bars, food dispatch, and logistics, software needs to reflect the physical state of the workflow as it happens.

Learning how to pair Laravel with Reverb and Echo gave us hands-on experience in building reactive, multi-client web applications without relying on third-party paid services. It reinforced the importance of choosing the right protocol—in this case, WebSockets—to build systems that are fast, efficient, and user-friendly.
