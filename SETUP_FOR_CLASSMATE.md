# ☕ COZY CAFÉ — GABAY PARA SA KAKLASE
### (Para sa IPT 102 Lab Activity 7 ni Kyle Gacula)

---

> 💬 **Huy!** Huwag mag-alala kahit hindi ka techie — sundin mo lang ito isa-isa at magiging ayos lahat. Kailangan mo lang ng **Laragon** at **Antigravity** sa laptop mo.

---

## 🟢 UNANG HAKBANG — I-download ang Project

1. Buksan ang **Chrome** o **Edge**
2. Pumunta sa link na ito:
   > **https://github.com/kyleG18/cozy-cafe**
3. I-click ang berdeng button na **"Code"**
4. I-click ang **"Download ZIP"**
5. Kapag na-download na, i-**extract**:
   - Right-click ang ZIP file → **Extract All** → OK
6. Buksan ang **File Explorer** → i-type sa address bar:
   ```
   C:\laragon\www
   ```
7. I-**paste** o i-**move** ang na-extract na folder doon
8. I-**rename** ang folder:
   - Kung ang pangalan ay `cozy-cafe-main` → palitan ng `cozy-cafe`
   - Right-click → Rename → burahin ang `-main` sa dulo

---

## 🟡 IKALAWANG HAKBANG — Buksan ang Laragon

1. Hanapin ang **Laragon** sa desktop o taskbar
2. I-double click para buksan
3. I-click ang **"Start All"** na button
4. Hintayin hanggang maging **berde** ang MySQL

   > ✅ Hindi na kailangan ng berdeng Apache — gagamitin natin ang ibang paraan para sa website!

---

## 🔵 IKATLONG HAKBANG — Buksan ang Antigravity at I-setup

1. Buksan ang **Antigravity** app
2. I-click ang **chat box**
3. **I-copy ang buong prompt sa ibaba** (Ctrl+A para piliin lahat, Ctrl+C para kopyahin):

---

### 📋 KOPYAHIN ANG PROMPT NA ITO AT I-PASTE SA ANTIGRAVITY:

```
I have downloaded the Cozy Café Laravel project and placed it in C:\laragon\www\cozy-cafe.

Laragon is open and MySQL is running (green). Apache does NOT need to be running.

Please do the following automatically, step by step:

1. Set PHP in the PATH using: C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64 and C:\laragon\bin\composer and C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin
2. Run: composer install
3. If .env does not exist, copy .env.example to .env and run: php artisan key:generate
4. Create a MySQL database called cozy_cafe using root user with no password, if it doesn't already exist
5. Make sure the .env file has: DB_CONNECTION=mysql, DB_HOST=127.0.0.1, DB_PORT=3306, DB_DATABASE=cozy_cafe, DB_USERNAME=root, DB_PASSWORD= (empty), BROADCAST_CONNECTION=reverb, QUEUE_CONNECTION=sync
6. Run: php artisan migrate
7. Run: php artisan db:seed
8. Run: npm install
9. Run: npm run build
10. Start the Laravel web server in the background: php artisan serve --host=0.0.0.0 --port=8000
11. Start the Reverb WebSocket server in the background: php artisan reverb:start
12. Wait a few seconds, then verify the site loads at http://localhost:8000 and shows the Cozy Café dashboard with order data
13. Run: php artisan test and show me the results
14. Give me a complete summary of what was completed and what URLs to open

This is for IPT 102 Lab Activity 7 - Real-Time Order Status Dashboard (Laravel + Reverb + Echo).
```

---

4. I-**paste** sa Antigravity (Ctrl+V) → pindutin ang **Enter**
5. **Hintayin** si Antigravity — mag-a-automatic siya ng lahat!
   > ⏳ Aabutin ng mga **5–10 minuto**. Huwag sarahin ang window.
6. Kapag tapos na, dapat sabihin ni Antigravity na **"Site is live at http://localhost:8000"**

---

## 🟠 IKAAPAT NA HAKBANG — Buksan ang Website

1. Buksan ang **Google Chrome**
2. I-type sa address bar:
   ```
   http://localhost:8000
   ```
3. Pindutin ang **Enter**
4. Dapat lumabas ang magandang **Cozy Café dashboard** na may mga order ☕

   > ✅ Hanapin sa taas ng page ang **"🟢 Live"** na indicator — kapag berde na ito, connected na ang real-time!

   > ⚠️ Kung naka-**orange/amber** pa rin ("Connecting...") pagkatapos ng 30 segundo, sabihin kay Antigravity:
   > *"Please start the Reverb WebSocket server: php artisan reverb:start in C:\laragon\www\cozy-cafe"*

---

## 🎯 IKALIMANG HAKBANG — I-demo sa Teacher

Ito ang pinakamahalaga — ipapakita mo ang **real-time** na feature!

### Paano mag-demo:

**1.** Buksan ang **Chrome** → pumunta sa `http://localhost:8000`

**2.** Buksan ang **Firefox** (o isa pang Chrome window: pindutin Ctrl+N) → pumunta rin sa `http://localhost:8000`

**3.** Ilagay ang dalawa **side by side** sa screen (kalahati ng screen bawat isa)
   > Sa Windows: i-drag ang Chrome window sa kaliwa → i-drag ang Firefox sa kanan

**4.** Tingnan na **pareho silang may "🟢 Live"** sa header

**5.** Sa **Chrome (kaliwa):**
   - Hanapin ang order ni **"Juan Dela Cruz"**
   - I-click ang dropdown na nagsasabing **"Pending"**
   - Piliin ang **"Preparing"**

**6.** Tignan ang **Firefox (kanan)** nang HINDI ni-refresh:
   - 🔥 Magbabago ang status ng order ng **AUTOMATIC!**
   - Lalabas ang notification: *"Order #1 (Juan Dela Cruz) is now Preparing."*
   - Magbabago rin ang numero sa **Summary Cards** sa taas

> 👆 **Iyon na iyon!** Real-time WebSocket demo gamit ang Laravel Reverb at Laravel Echo. Swerte! ☕

---

## 🆘 Kung May Problema

| Problema | Gagawin |
|---|---|
| **Hindi bumukas ang `localhost:8000`** | Sabihin kay Antigravity: *"Please start the Laravel server: php artisan serve --host=0.0.0.0 --port=8000 in C:\laragon\www\cozy-cafe"* |
| **Naka-orange "Connecting..." at hindi nagiging green** | Sabihin kay Antigravity: *"Please start the Reverb WebSocket server: php artisan reverb:start in C:\laragon\www\cozy-cafe"* |
| **Walang orders na nakikita / blangko** | Sabihin kay Antigravity: *"Please run php artisan db:seed in C:\laragon\www\cozy-cafe to load sample orders"* |
| **May error na pulang screen** | Sabihin kay Antigravity: *"The site at localhost:8000 shows a 500 error. Please run php artisan config:clear and php artisan cache:clear in C:\laragon\www\cozy-cafe"* |
| **Nag-install si Antigravity pero nag-error sa composer** | Sabihin kay Antigravity: *"composer install failed. Please set the PHP path to C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64 and try again"* |

---

## 📌 Mabilis na Reference

| | |
|---|---|
| 🌐 **Website URL** | http://localhost:8000 |
| ⚡ **Reverb Port** | 8080 (WebSocket server) |
| 🗄️ **Database** | cozy_cafe (MySQL, root, no password) |
| 📁 **Project Folder** | C:\laragon\www\cozy-cafe |
| 🔗 **GitHub** | https://github.com/kyleG18/cozy-cafe |
| 👤 **Student** | Kyle Gacula |
| 📚 **Subject** | IPT 102 – Lab Activity 7 |

---

> 🙏 **Salamat sa tulong!** Tatlong command lang ang kailangan patakbuhin ni Antigravity:
> 1. `composer install` + `npm install` + `npm run build` (install)
> 2. `php artisan serve` (website)
> 3. `php artisan reverb:start` (real-time)
>
> Kapag tatlong ito ay tumatakbo, maganda na ang demo! Good luck bukas! ☕✨
