# 🎬 BobaCraft — Screen Recording Demonstration Video Script

**Course:** IPT 102 – Integrative Programming and Technologies 2  
**Activity:** Lab Activity 7 – Building a Real-Time Event Dashboard Using WebSockets  
**Target Duration:** 2 to 3 minutes  
**Presenter:** Kyle Gacula & Partner  

---

## 📋 Pre-Recording Setup Checklist
1. Open **Laragon** and verify MySQL is running (port 3306).
2. Open **VS Code** with the project folder:  
   `C:\Users\Kyle Gacula\OneDrive\Desktop\FinalsIPT`
3. Have your terminal services ready:
   - **Terminal 1:** `php artisan serve` (Listening on `http://127.0.0.1:8000`)
   - **Terminal 2:** `php artisan reverb:start` (Listening on port `8080`)
4. Open **Google Chrome** with two side-by-side windows:
   - **Left Window:** `http://127.0.0.1:8000` (Simulates Barista Station Tablet)
   - **Right Window:** `http://127.0.0.1:8000` (Simulates Customer Pickup Lounge TV Display)

---

## 🎙️ Step-by-Step Script & Narration

### Step 1: Introduction (0:00 – 0:30)
* **What to Show on Screen:**  
  Full desktop view showing the two side-by-side Chrome windows with the **BobaCraft Milk Tea Bar** dashboard loaded. Move your cursor to the green **"Tea Bar Online"** indicator in the header.
* **Narration:**  
  > *"Good day, instructor and classmates. Today, we are presenting our Lab Activity 7 project: **BobaCraft**, a real-time Milk Tea Barista Kitchen Display System and drink order tracker built with Laravel, MySQL, and Laravel Reverb WebSockets."*  
  > *"On the left screen, we have the Barista Shaker Station tablet. On the right screen, we have the Customer Pickup Display in the cafe lounge. Notice that both screens show a glowing green indicator confirming our WebSocket connection is live."*

---

### Step 2: Demonstrating Live Status Update (0:30 – 1:15)
* **What to Show on Screen:**  
  In the **Left Window (Barista Station)**, click the dropdown for **Cup #201 (Brown Sugar Boba Fresh Milk)** and change it from **"Order Queued / Brewing"** to **"Shaking & Pearls"**.  
  Immediately draw the viewer's attention to the **Right Window (Customer Pickup Display)**.
* **Narration:**  
  > *"Watch what happens when the barista starts preparing Cup #201. We change its status from 'Order Queued / Brewing' to 'Shaking & Pearls'. Instantly, on the right customer display, the badge turns taro purple, the timestamp updates, a notification toast pops up in the corner, and our summary counter increases—all without anyone pressing refresh!"*

---

### Step 3: Taking a New Milk Tea Order Live (1:15 – 1:55)
* **What to Show on Screen:**  
  In the **Left Window**, click the golden **"+ Take Boba Order"** button.  
  Type a drink order, e.g.: `Cup #206 — Tiger Sugar Pearl Fresh Milk (Large, 50% Sugar, Less Ice)`.  
  Click **"Brew & Queue Drink"**.  
  Notice the new drink appear instantaneously at the top of the queue on both windows.
* **Narration:**  
  > *"Now, let's take a brand-new customer order from the cashier counter. We click 'Take Boba Order', enter 'Tiger Sugar Pearl Fresh Milk with 50% sugar and less ice', and submit. The moment we click submit, the drink appears at the top of both screens with an initial queued status, and our total cup counter updates automatically."*

---

### Step 4: Moving a Cup to Pickup Counter (1:55 – 2:30)
* **What to Show on Screen:**  
  Change Cup #206 to **"Sealed & At Bar"** (fresh green badge).  
  Change an earlier cup to **"Claimed / Enjoy!"** (blue badge).
* **Narration:**  
  > *"Once the drink is mixed and sealed, the barista updates the cup to 'Sealed & At Bar'. The customer sitting in the lounge immediately sees their cup turn green on the TV monitor, letting them know their drink is ready at the counter."*

---

### Step 5: Technical Summary & Conclusion (2:30 – 3:00)
* **What to Show on Screen:**  
  Switch briefly to your VS Code window showing the running `php artisan reverb:start` terminal, or point to the technical documentation.
* **Narration:**  
  > *"Behind the scenes, every update sends a background AJAX request to Laravel, commits the record into our dedicated `bobacraft_db` MySQL database, and broadcasts an event via Laravel Reverb on port 8080. Laravel Echo in the browser catches the payload and updates the user interface in under 50 milliseconds."*  
  > *"This completely eliminates manual page refreshes and heavy HTTP polling. Thank you for watching our demonstration!"*
