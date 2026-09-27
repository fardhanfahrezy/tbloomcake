# Website Architecture Document - TBloom Cake

## 1. Architecture Overview

- **Nama produk:** Website TBloom Cake
- **Versi dokumen:** 1.0
- **Tanggal:** 28 September 2026
- **Status:** Draft
- **Author:** `[Nama kamu]`
- **Related PRD:** PRD - Website TBloom Cake (v1.0)
- **Architecture Style:** Monolithic (Laravel MVC) — bukan split Frontend/Backend seperti Next.js + Node.js terpisah, karena repo yang sudah berjalan adalah Laravel 13 dengan Blade sebagai view layer
- **Architecture Decision Summary:** Dipilih **Laravel Monolith** (bukan microservices/headless) karena skala usaha masih kecil–menengah (single tenant, 1 toko), kebutuhan fitur relatif standar (katalog, checkout, admin panel, form request), dan tim development kemungkinan kecil. Monolith Laravel memberikan time-to-market lebih cepat, biaya hosting lebih murah, dan cukup untuk beban trafik yang diproyeksikan.

### 1.1 Purpose

- **Tujuan dokumen:** Menjadi acuan teknis bagi tim development dalam membangun, mengembangkan, dan memelihara website TBloom Cake secara konsisten
- **Tujuan architecture:** Menyediakan struktur sistem yang sederhana, mudah dirawat oleh tim kecil, namun cukup solid untuk menangani transaksi (order, pembayaran) dan data availability harian tanpa race condition
- **Problem yang ingin diselesaikan oleh architecture:** Mencegah double-booking kuota harian, memastikan data request Custom Cake tersimpan terstruktur (mengurangi miss komunikasi), dan integrasi payment gateway yang aman

### 1.2 Architecture Principles

- **Principle 1 — Simplicity first:** Gunakan pola Laravel standar (MVC + Eloquent) sebelum menambah kompleksitas (queue, cache, microservice) kecuali benar-benar dibutuhkan
- **Principle 2 — Single source of truth untuk availability:** Perhitungan kuota harian selalu dihitung dari tabel `orders` secara real-time/locked, bukan dari counter terpisah yang bisa desync
- **Principle 3 — Fail-safe payment:** Status pembayaran hanya berubah melalui callback/webhook resmi dari payment gateway yang terverifikasi, tidak pernah diubah manual oleh input customer
- **Principle 4 — Separation of concern by domain:** Modul dipisah per domain bisnis (Produk, Order Simple Cake, Request Custom Cake, Pembayaran, Testimoni) menggunakan Service Layer, agar tetap mudah dipecah jadi microservice di masa depan bila diperlukan

## 2. System Context

### 2.1 System Context Diagram

```
                    ┌─────────────────┐
                    │     Guest       │
                    └────────┬────────┘
                             │
                             ▼
┌──────────────┐      ┌───────────────┐      ┌──────────────┐
│   Customer   │─────▶│    Website    │◀─────│    Admin/    │
│  (Browser)   │      │  (Laravel App)│      │    Owner     │
└──────────────┘      └───────┬───────┘      └──────────────┘
                              │
              ┌───────────────┼───────────────┐
              ▼               ▼               ▼
      ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
      │   Database   │ │   Payment    │ │  WhatsApp    │
      │  (MySQL)     │ │   Gateway    │ │  Notifikasi  │
      └──────────────┘ └──────────────┘ └──────────────┘
```

### 2.2 External Systems

| System | Purpose | Integration | Protocol |
| --- | --- | --- | --- |
| Payment Gateway `[ASUMSI: Midtrans/Xendit]` | Pembayaran online (transfer bank, QRIS, ShopeePay, BJB) | REST API + Webhook callback | HTTPS |
| WhatsApp API `[ASUMSI: Fonnte/Wablas, atau notifikasi manual di dashboard bila tidak ada budget integrasi]` | Notifikasi request Custom Cake baru ke admin | REST API | HTTPS |
| Cloud Storage (opsional) `[ASUMSI: Laravel Cloud storage / S3-compatible]` | Simpan foto produk & upload referensi desain customer | SDK/API | HTTPS |
| Peta/Estimasi Jarak `[ASUMSI: Google Maps Distance Matrix API, bila estimasi ongkir dihitung otomatis]` | Hitung estimasi jarak untuk ongkos kirim | REST API | HTTPS |

### 2.3 System Boundary

**In Scope**

- Website publik (katalog, checkout Simple Cake, form Custom Cake, testimoni)
- Admin panel (produk, stok, pesanan, testimoni)
- Integrasi payment gateway
- Estimasi ongkos kirim berbasis area/jarak

**Out of Scope**

- Sistem POS untuk transaksi offline di toko
- Sistem keuangan/akuntansi penuh (dessert, minuman, pastry tetap dikelola manual di luar sistem ini)
- Aplikasi mobile native

## 3. Architecture Style

### 3.1 Architecture Pattern

- [x] Modular Monolith
- [ ] Microservices
- [ ] Serverless
- [ ] Event-Driven
- [ ] Hybrid

*Catatan: "Modular Monolith" dipilih (bukan Monolithic murni) — kode dipisah per domain module (Product, Order, CustomRequest, Payment, Testimonial) di dalam satu codebase Laravel, agar tetap terstruktur tanpa overhead microservices.*

### 3.2 Architecture Diagram

```
┌─────────────────────────────────────────────┐
│                  Client                      │
│         Browser (Desktop / Mobile)           │
└──────────────────────┬───────────────────────┘
                        │ HTTPS
                        ▼
┌─────────────────────────────────────────────┐
│           Laravel Application Layer          │
│                                               │
│  routes/web.php ─▶ Controller ─▶ Service     │
│      ▲                              │        │
│      │                              ▼        │
│  Blade Views  ◀───────────────  Eloquent     │
│  (resources/views + Tailwind)      Model     │
└──────────────┬──────────────┬────────────────┘
               │              │
               ▼              ▼
       ┌──────────────┐ ┌──────────────┐
       │   Database    │ │  External API │
       │  (MySQL)      │ │ (Payment, WA) │
       └──────────────┘ └──────────────┘
```

## 4. System Components

### 4.1 Component Overview

| Component | Responsibility | Technology |
| --- | --- | --- |
| Web Layer (Blade + Tailwind) | Render UI katalog, checkout, form custom, admin panel | Laravel Blade, Tailwind CSS 4, Vite |
| Application Layer | Routing, request handling, validasi | Laravel 13 (PHP 8.3/8.4) |
| Domain Services | Business logic (availability check, order flow, refund calc) | PHP Service Classes |
| Database | Penyimpanan data persisten | MySQL/MariaDB (production), SQLite (dev/testing) |
| Queue/Jobs | Kirim notifikasi WhatsApp/email secara async | Laravel Queue (database driver) |
| File Storage | Foto produk, upload referensi desain customer | Laravel Filesystem (`storage/app/public`) → disk `local`/`s3` |
| Authentication | Login Admin/Owner (session-based) | Laravel built-in Auth |
| Cache | Cache data availability/katalog | Laravel Cache (database/file driver, upgrade ke Redis bila traffic naik) |

### 4.2 Component Diagram

```
                    ┌───────────────┐
                    │  Blade Views  │
                    └───────┬───────┘
                            │
                            ▼
                    ┌───────────────┐
                    │  Controllers  │
                    └───────┬───────┘
                            │
                            ▼
                    ┌───────────────┐
                    │   Services    │
                    └───────┬───────┘
                            │
              ┌─────────────┼─────────────┐
              ▼             ▼             ▼
        ┌──────────┐  ┌──────────┐  ┌──────────┐
        │ Database │  │  Queue   │  │ Storage  │
        └──────────┘  └──────────┘  └──────────┘
```

## 5. Frontend Architecture

*(Frontend di sini adalah Blade view layer yang di-render server-side oleh Laravel, bukan SPA terpisah)*

### 5.1 Frontend Technology

- **Framework:** Laravel Blade (server-side rendering)
- **Language:** PHP (Blade templating) + JavaScript vanilla/Alpine.js untuk interaktivitas ringan `[ASUMSI: Alpine.js untuk multi-step form Custom Cake & date picker availability]`
- **Styling:** Tailwind CSS 4
- **UI Library:** Custom components (tidak menggunakan library besar seperti shadcn agar tetap ringan)
- **State Management:** Tidak diperlukan state management kompleks (server-rendered); state lokal cukup dengan Alpine.js
- **Form Management:** Laravel Form Request + Blade `@error`/old input helper
- **Validation:** Server-side via Laravel Form Request; validasi ringan di client (HTML5 + Alpine) untuk UX
- **HTTP Client:** Fetch API native (untuk cek availability async tanpa reload halaman)
- **Testing:** Pest (Feature test untuk flow halaman)

### 5.2 Frontend Structure

```
resources/
├── views/
│   ├── layouts/
│   │   └── app.blade.php
│   ├── components/
│   │   ├── button.blade.php
│   │   ├── product-card.blade.php
│   │   └── availability-badge.blade.php
│   ├── catalog/
│   │   └── index.blade.php
│   ├── checkout/
│   │   ├── show.blade.php
│   │   └── confirmation.blade.php
│   ├── custom-cake/
│   │   └── form.blade.php
│   ├── testimonials/
│   │   └── index.blade.php
│   └── admin/
│       ├── dashboard.blade.php
│       ├── products/
│       ├── orders/
│       └── testimonials/
├── css/
│   └── app.css
└── js/
    └── app.js
```

### 5.3 Component Architecture

**Shared Components**

- Button, Input, Modal, Table, Pagination, Alert/Badge (availability status)

**Feature Components**

- Product Catalog Card (menampilkan harga & availability)
- Multi-step Custom Cake Form
- Order Summary / Checkout Card
- Testimonial Card

**Page Components**

- Home / Landing
- Katalog Simple Cake
- Detail Produk & Checkout
- Form Custom Cake
- Testimoni
- Admin: Dashboard, Produk, Pesanan, Testimoni, Login

## 6. Backend Architecture

### 6.1 Backend Technology

- **Runtime:** PHP 8.3/8.4
- **Framework:** Laravel 13
- **Language:** PHP
- **API Style:** Mayoritas server-rendered (Blade); beberapa endpoint JSON internal untuk AJAX (cek availability, estimasi ongkir) mengikuti gaya REST ringan
- **Validation:** Laravel Form Request classes
- **ORM:** Eloquent
- **Authentication:** Laravel session-based auth (guard `web`) untuk Admin/Owner; Customer checkout sebagai guest (tanpa akun) `[ASUMSI sesuai PRD, perlu konfirmasi]`
- **Authorization:** Laravel Policy/Gate berbasis kolom `role` pada tabel `users` (Admin, Owner)

### 6.2 Backend Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── CatalogController.php
│   │   ├── CheckoutController.php
│   │   ├── CustomCakeRequestController.php
│   │   ├── PaymentWebhookController.php
│   │   ├── TestimonialController.php
│   │   └── Admin/
│   │       ├── ProductController.php
│   │       ├── OrderController.php
│   │       └── TestimonialController.php
│   ├── Requests/
│   │   ├── StoreOrderRequest.php
│   │   └── StoreCustomCakeRequest.php
│   └── Middleware/
│       └── EnsureIsAdmin.php
├── Models/
│   ├── User.php
│   ├── Product.php
│   ├── ProductAvailability.php
│   ├── Order.php
│   ├── OrderItem.php
│   ├── CustomCakeRequest.php
│   └── Testimonial.php
├── Services/
│   ├── AvailabilityService.php
│   ├── OrderService.php
│   ├── ShippingEstimateService.php
│   ├── PaymentService.php
│   └── WhatsAppNotificationService.php
├── Jobs/
│   └── SendCustomCakeNotification.php
├── Policies/
│   └── OrderPolicy.php
└── Providers/
    └── AppServiceProvider.php
```

### 6.3 Request Flow

```
Client (Browser)
      │
      ▼
routes/web.php
      │
      ▼
Middleware (auth admin, jika perlu)
      │
      ▼
Controller
      │
      ▼
Form Request (validasi)
      │
      ▼
Service (business logic: cek kuota, hitung ongkir, dsb)
      │
      ▼
Eloquent Model
      │
      ▼
Database
      │
      ▼
Response (Blade view / JSON untuk AJAX)
```

## 7. API Architecture

*Digunakan untuk endpoint internal AJAX (bukan public API), dikonsumsi oleh JS di halaman checkout*

### 7.1 API Overview

- **API Style:** REST (internal, JSON)
- **Base URL:** `/api` (route group internal, bukan API publik bertoken)
- **Version:** v1 (disiapkan, walau saat ini belum butuh versioning eksternal)
- **Authentication:** Session cookie (customer) / Sanctum session guard (admin)
- **Response Format:** JSON
- **Error Format:** `{ "success": false, "message": "..." }`

### 7.2 API Endpoint

**API-001 Cek Availability Produk**

- **Method:** GET
- **Endpoint:** `/api/products/{product}/availability?date=YYYY-MM-DD`
- **Authentication:** Tidak wajib (public)
- **Authorization:** -
- **Description:** Mengembalikan sisa kuota produk pada tanggal tertentu

Request

```json
{ "date": "2026-09-30" }
```

Response

```json
{
  "success": true,
  "data": { "product_id": 1, "date": "2026-09-30", "max_quota": 5, "used": 3, "remaining": 2 }
}
```

Error Response

```json
{ "success": false, "message": "Produk tidak ditemukan" }
```

**Business Rules:** `remaining = max_quota - used`; produk dianggap penuh jika `remaining <= 0`

---

**API-002 Estimasi Ongkos Kirim**

- **Method:** POST
- **Endpoint:** `/api/shipping/estimate`
- **Authentication:** Tidak wajib
- **Description:** Menghitung estimasi ongkir berdasarkan area & ukuran cake

Request

```json
{ "address": "Jl. Contoh No. 1, Indramayu", "product_size": "18cm" }
```

Response

```json
{ "success": true, "data": { "estimated_cost": 150000, "vehicle_type": "car", "zone": "Timur - Cirebon" } }
```

**Business Rules:** Cake > ukuran lunch box wajib `vehicle_type: car`; estimasi bisa berubah, ditandai sebagai perkiraan

---

**API-003 Payment Webhook**

- **Method:** POST
- **Endpoint:** `/api/payment/webhook`
- **Authentication:** Signature verification dari payment gateway (bukan session)
- **Description:** Menerima notifikasi status pembayaran dari payment gateway

Request (contoh generik, format aktual mengikuti dokumentasi gateway terpilih)

```json
{ "order_id": "TBC-20260930-0001", "status": "settlement", "signature": "..." }
```

Response

```json
{ "success": true }
```

**Business Rules:** Signature wajib diverifikasi sebelum status order diubah; request tanpa signature valid ditolak dengan HTTP 403

## 8. Authentication & Authorization

### 8.1 Authentication Flow

```
User (Admin/Owner)
     │
     ▼
Login Form
     │
     ▼
Laravel Auth (session guard)
     │
     ├── Invalid → Error "Kredensial salah"
     │
     └── Valid
           │
           ▼
      Session Cookie
           │
           ▼
       Admin Panel
```

*Customer tidak melalui flow ini — checkout sebagai guest, identitas cukup dari nomor WhatsApp/email pada form order `[ASUMSI]`*

### 8.2 Authentication Method

- [x] Session
- [ ] JWT
- [ ] OAuth
- [ ] SSO

### 8.3 Authorization

| Role | Permission |
| --- | --- |
| Guest | Lihat katalog, testimoni, isi form custom cake |
| Customer | Checkout, lihat status order sendiri, ajukan pembatalan/perubahan |
| Admin | Kelola produk, stok, pesanan, testimoni |
| Owner | Semua akses Admin + kelola akun Admin + akses laporan |

### 8.4 Permission Matrix

| Resource | Guest | Customer | Admin | Owner |
| --- | --- | --- | --- | --- |
| View Product | ✓ | ✓ | ✓ | ✓ |
| Create Order | ✓ (guest checkout) | ✓ | ✓ | ✓ |
| Submit Custom Request | ✓ | ✓ | - | - |
| Manage Product | - | - | ✓ | ✓ |
| Manage Order Status | - | - | ✓ | ✓ |
| Manage Testimonial | - | - | ✓ | ✓ |
| Manage Admin Users | - | - | - | ✓ |

## 9. Database Architecture

### 9.1 Database Technology

- **Database:** MySQL 8 (production), SQLite (development/testing — sudah default di project)
- **Version:** MySQL 8.x
- **ORM:** Eloquent (Laravel 13)
- **Migration Tool:** Laravel Migrations (`php artisan make:migration`)
- **Backup Strategy:** Automated daily backup via provider hosting (mis. Laravel Cloud backup) `[ASUMSI, perlu konfirmasi]`

### 9.2 Entity Relationship Diagram

```
┌──────────────┐        ┌──────────────────┐
│    users     │        │     products      │
├──────────────┤        ├──────────────────┤
│ id           │        │ id               │
│ name         │        │ category         │
│ email        │        │ name             │
│ password     │        │ base_price       │
│ role         │        │ description      │
└──────────────┘        │ photo_path       │
                         │ is_active        │
                         └────────┬─────────┘
                                  │ 1:N
                                  ▼
                         ┌──────────────────┐
                         │ product_variants │  (ukuran & harga)
                         ├──────────────────┤
                         │ id               │
                         │ product_id       │
                         │ size             │
                         │ price            │
                         └────────┬─────────┘
                                  │ 1:N
                                  ▼
                    ┌──────────────────────────┐
                    │ product_availabilities    │
                    ├──────────────────────────┤
                    │ id                       │
                    │ product_variant_id       │
                    │ date                     │
                    │ max_quota                │
                    │ used_quota               │
                    └──────────────────────────┘

┌──────────────┐        ┌──────────────────┐
│    orders    │───1:N─▶│   order_items    │
├──────────────┤        ├──────────────────┤
│ id           │        │ id               │
│ customer_name│        │ order_id         │
│ customer_wa  │        │ product_variant_id│
│ status       │        │ quantity         │
│ payment_status│       │ notes (tulisan)  │
│ pickup_date  │        │ price            │
│ pickup_time  │        └──────────────────┘
│ delivery_type│
│ address      │
│ total_amount │
└──────────────┘

┌──────────────────────┐
│ custom_cake_requests  │
├──────────────────────┤
│ id                   │
│ customer_name        │
│ customer_wa          │
│ pickup_date          │
│ detail (JSON)        │
│ reference_image_path │
│ status               │
└──────────────────────┘

┌──────────────┐
│ testimonials │
├──────────────┤
│ id           │
│ customer_name│
│ content      │
│ rating       │
│ is_published │
└──────────────┘
```

### 9.3 Database Tables

**users**

| Field | Type | Nullable | Key | Description |
| --- | --- | --- | --- | --- |
| id | BIGINT | No | PK | User ID |
| name | VARCHAR | No | - | Nama admin/owner |
| email | VARCHAR | No | UNIQUE | Email login |
| password | VARCHAR | No | - | Password (hashed) |
| role | ENUM('admin','owner') | No | - | Role akses |
| created_at | TIMESTAMP | No | - | Tanggal dibuat |

**products**

| Field | Type | Nullable | Key | Description |
| --- | --- | --- | --- | --- |
| id | BIGINT | No | PK | Product ID |
| category | ENUM('simple_cake','cup_cake','bento_cake') | No | - | Kategori produk |
| name | VARCHAR | No | - | Nama produk |
| description | TEXT | Yes | - | Deskripsi produk |
| photo_path | VARCHAR | Yes | - | Path foto |
| is_active | BOOLEAN | No | - | Status aktif/nonaktif |

**product_variants**

| Field | Type | Nullable | Key | Description |
| --- | --- | --- | --- | --- |
| id | BIGINT | No | PK | Variant ID |
| product_id | BIGINT | No | FK → products.id | Relasi produk |
| size | VARCHAR | No | - | Ukuran (10cm, 12cm, dst) |
| price | DECIMAL | No | - | Harga sesuai ukuran |
| max_daily_quota | INT | No | - | Default 5 |

**product_availabilities**

| Field | Type | Nullable | Key | Description |
| --- | --- | --- | --- | --- |
| id | BIGINT | No | PK | Availability ID |
| product_variant_id | BIGINT | No | FK | Relasi varian produk |
| date | DATE | No | - | Tanggal |
| used_quota | INT | No | - | Jumlah order masuk pada tanggal tsb |

**orders**

| Field | Type | Nullable | Key | Description |
| --- | --- | --- | --- | --- |
| id | BIGINT | No | PK | Order ID |
| order_code | VARCHAR | No | UNIQUE | Kode order (mis. TBC-20260930-0001) |
| customer_name | VARCHAR | No | - | Nama customer |
| customer_whatsapp | VARCHAR | No | - | Nomor WA customer |
| status | ENUM('pending','confirmed','in_progress','completed','cancelled') | No | - | Status pesanan |
| payment_status | ENUM('unpaid','paid','refunded') | No | - | Status pembayaran |
| pickup_date | DATE | No | - | Tanggal pengambilan |
| pickup_time | TIME | No | - | Jam pengambilan |
| delivery_type | ENUM('pickup','delivery') | No | - | Metode pengambilan |
| delivery_address | TEXT | Yes | - | Alamat (jika delivery) |
| shipping_cost | DECIMAL | Yes | - | Estimasi/biaya ongkir |
| total_amount | DECIMAL | No | - | Total pembayaran |
| created_at | TIMESTAMP | No | - | Tanggal order dibuat |

**order_items**

| Field | Type | Nullable | Key | Description |
| --- | --- | --- | --- | --- |
| id | BIGINT | No | PK | Order item ID |
| order_id | BIGINT | No | FK → orders.id | Relasi order |
| product_variant_id | BIGINT | No | FK | Relasi varian produk |
| quantity | INT | No | - | Jumlah pesanan |
| cake_text | VARCHAR(100) | Yes | - | Tulisan pada cake (maks 10 kata) |
| color | VARCHAR | Yes | - | Warna cover |
| price | DECIMAL | No | - | Harga saat order |

**custom_cake_requests**

| Field | Type | Nullable | Key | Description |
| --- | --- | --- | --- | --- |
| id | BIGINT | No | PK | Request ID |
| customer_name | VARCHAR | No | - | Nama customer |
| customer_whatsapp | VARCHAR | No | - | Nomor WA |
| pickup_date | DATE | No | - | Tanggal pengambilan diinginkan |
| detail | JSON | Yes | - | Detail ukuran/rasa/filling/warna/tulisan/fondant/catatan |
| reference_image_path | VARCHAR | Yes | - | Path upload gambar referensi |
| status | ENUM('new','contacted','confirmed','rejected') | No | - | Status follow-up admin |
| created_at | TIMESTAMP | No | - | Tanggal request dibuat |

**testimonials**

| Field | Type | Nullable | Key | Description |
| --- | --- | --- | --- | --- |
| id | BIGINT | No | PK | Testimonial ID |
| customer_name | VARCHAR | No | - | Nama customer |
| content | TEXT | No | - | Isi testimoni |
| rating | TINYINT | Yes | - | Rating 1–5 |
| is_published | BOOLEAN | No | - | Status publikasi |

## 10. Data Flow Architecture

### 10.1 Main Data Flow

```
User
 │
 ▼
Blade View (Browser)
 │
 ▼
Controller
 │
 ▼
Service (Business Logic)
 │
 ├───────────────┐
 ▼               ▼
Database       External Service (Payment/WA)
 │
 └───────┬───────┘
         ▼
      Response
         │
         ▼
    Blade View / JSON
         │
         ▼
        User
```

### 10.2 Feature Data Flow

**Checkout Simple Cake**

```
Input (pilih produk, tanggal, detail)
  │
  ▼
Validasi (Form Request: kuota tersedia? min. H-1?)
  │
  ▼
Business Logic (AvailabilityService::reserve, OrderService::create)
  │
  ▼
Database (insert orders + order_items, increment used_quota)
  │
  ▼
Redirect ke Payment Gateway
  │
  ▼
Response (halaman konfirmasi order)
```

**Request Custom Cake**

```
Input (form + upload gambar)
  │
  ▼
Validasi (min. H-3 / H-7 untuk print)
  │
  ▼
Business Logic (simpan request, dispatch Job notifikasi)
  │
  ▼
Database (insert custom_cake_requests)
  │
  ▼
Job Queue → WhatsAppNotificationService → kirim ke admin
  │
  ▼
Response (halaman terima kasih ke customer)
```

## 11. Integration Architecture

### 11.1 External Integrations

| Service | Purpose | Integration Method | Authentication |
| --- | --- | --- | --- |
| Payment Gateway | Pembayaran | REST API + Webhook | API Key + Signature |
| WhatsApp API | Notifikasi request custom cake | REST API | API Key/Token |
| Google Maps (opsional) | Estimasi jarak untuk ongkir | REST API | API Key |

### 11.2 Integration Flow

```
Website
   │
   ▼
OrderService / CustomCakeRequestService
   │
   ▼
Integration Service (PaymentService / WhatsAppNotificationService)
   │
   ▼
External API
   │
   ▼
Response / Webhook Callback
   │
   ▼
Update status di Database
```

### 11.3 Failure Handling

- **Timeout:** Set timeout 10–15 detik untuk semua HTTP client ke API eksternal
- **Retry:** Gunakan Laravel Queue retry (default 3x dengan backoff) untuk job notifikasi WhatsApp yang gagal
- **Rate Limit:** Terapkan Laravel `throttle` middleware pada endpoint publik (checkout, custom request) untuk mencegah abuse
- **Fallback:** Jika WhatsApp API gagal, request custom cake tetap tersimpan di database dan tampil di dashboard admin sebagai "belum terkirim notifikasi"
- **Logging:** Semua request/response ke API eksternal dicatat di channel log terpisah (`storage/logs/integrations.log`)
- **Alerting:** `[ASUMSI: notifikasi email ke developer/owner jika job gagal >3x, via Laravel failed_jobs table]`

## 12. File & Storage Architecture

### 12.1 Storage

- **Provider:** Laravel Filesystem — disk `public` (local) untuk awal, dapat upgrade ke S3-compatible bila kebutuhan storage besar
- **Bucket:** `[ASUMSI: tbloom-cake-assets, bila menggunakan S3]`
- **Folder Structure:**

```
storage/app/public/
├── products/
├── custom-requests/
└── testimonials/
```

- **Maximum File Size:** 5 MB per file (foto produk & upload referensi desain)
- **Allowed File Types:** jpg, jpeg, png, webp

### 12.2 Upload Flow

```
User (upload foto produk / referensi desain)
 │
 ▼
Frontend (input file + preview)
 │
 ▼
Upload Validation (tipe file, ukuran max)
 │
 ▼
Storage (disk public / S3)
 │
 ▼
File URL disimpan
 │
 ▼
Database (kolom photo_path / reference_image_path)
```

## 13. Caching Architecture

### 13.1 Cache Strategy

- **Cache Provider:** Database cache driver (default project), upgrade ke Redis bila traffic tinggi
- **TTL:** Bervariasi per jenis data (lihat tabel di bawah)
- **Cache Key Strategy:** `product-list`, `availability:{product_variant_id}:{date}`
- **Invalidation Strategy:** Cache di-clear otomatis saat ada perubahan data terkait (event-based invalidation)

### 13.2 Cached Data

| Data | Cache | TTL | Invalidation |
| --- | --- | --- | --- |
| Daftar Produk (katalog) | Yes | 5 menit | Saat admin update produk |
| Availability per tanggal | Yes (short TTL) | 30 detik `[perlu TTL pendek agar tidak double-booking]` | Saat ada order baru masuk untuk tanggal tsb |
| Testimoni terpublikasi | Yes | 10 menit | Saat admin publish/edit/hapus testimoni |
| Session Admin | Yes | 120 menit (sesuai `SESSION_LIFETIME`) | Saat logout |

## 14. Security Architecture

### 14.1 Security Requirements

- **HTTPS:** Wajib di semua environment production (force HTTPS via middleware/`APP_URL`)
- **Password Hashing:** Bcrypt (default Laravel, `Hash::make`)
- **Authentication:** Session-based (Laravel default)
- **Authorization:** Policy/Gate berbasis role
- **CSRF Protection:** Bawaan Laravel (`@csrf` di setiap form)
- **XSS Protection:** Blade otomatis escape output (`{{ }}`); hindari `{!! !!}` untuk input user
- **SQL Injection Protection:** Eloquent/Query Builder (parameter binding otomatis)
- **Rate Limiting:** Middleware `throttle` pada route checkout, custom request, dan webhook
- **CORS:** Dibatasi hanya untuk domain sendiri (tidak ada API publik eksternal)
- **Security Headers:** Tambahkan header `X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy` dasar

### 14.2 Sensitive Data

| Data | Storage | Encryption | Access |
| --- | --- | --- | --- |
| Password Admin | Database | Hash (bcrypt) | System |
| API Key Payment Gateway | `.env` / Secret Manager | Environment variable (tidak di-commit) | Backend only |
| Nomor WhatsApp Customer | Database | Plain (bukan data sangat sensitif, tapi akses dibatasi role) | Admin/Owner |
| Alamat Customer | Database | Plain | Admin/Owner |

### 14.3 Security Flow

```
Request
   │
   ▼
HTTPS
   │
   ▼
Rate Limiting (throttle middleware)
   │
   ▼
Authentication (jika akses admin)
   │
   ▼
Authorization (Policy/Gate)
   │
   ▼
Validation (Form Request)
   │
   ▼
Business Logic
```

## 15. Error Handling & Logging

### 15.1 Error Strategy

- **Validation Error:** Redirect back dengan pesan error per field (Blade `@error`)
- **Authentication Error:** Redirect ke halaman login dengan pesan
- **Authorization Error:** HTTP 403 dengan halaman error kustom
- **Not Found:** HTTP 404 dengan halaman error kustom
- **Business Error** (mis. kuota penuh, order lewat H-2): Pesan jelas ke user, tidak menampilkan stack trace
- **Internal Server Error:** HTTP 500 generic page (detail hanya di log, tidak ditampilkan ke user saat production/`APP_DEBUG=false`)

### 15.2 Error Format (untuk endpoint JSON)

```json
{
  "success": false,
  "error": {
    "code": "QUOTA_FULL",
    "message": "Kuota untuk produk dan tanggal ini sudah penuh"
  }
}
```

### 15.3 Logging

**Log Levels:** DEBUG, INFO, WARN, ERROR, FATAL (sesuai standar Monolog di `config/logging.php`)

**Logged Events**

- Login/logout admin
- Order dibuat, dibatalkan, diubah
- Payment webhook diterima
- Kegagalan integrasi eksternal (payment/WA)
- Error 500 / exception tak tertangani

## 16. Performance Architecture

### 16.1 Performance Target

| Metric | Target |
| --- | --- |
| Page Load | \< 3 detik (4G) |
| API Response (internal AJAX) | \< 500 ms |
| Database Query | \< 100 ms per query |
| Concurrent Users | \~50–100 (skala UMKM, dapat direvisi sesuai proyeksi trafik) |
| Requests/sec | \~10–20 req/s |

### 16.2 Optimization Strategy

- [x] Caching (katalog & availability)
- [x] Lazy Loading (gambar produk)
- [x] Image Optimization (kompres foto produk sebelum upload)
- [x] Database Indexing (index pada `orders.pickup_date`, `product_availabilities.date`)
- [x] Query Optimization (eager loading relasi untuk hindari N+1)
- [x] Pagination (daftar pesanan di admin panel)
- [x] Background Jobs (notifikasi WhatsApp async)
- [ ] CDN (opsional, untuk asset statis bila traffic besar)
- [ ] Code Splitting (tidak relevan untuk Blade server-rendered)

## 17. Scalability Architecture

### 17.1 Scaling Strategy

- [x] Vertical Scaling (upgrade resource server — cukup untuk skala awal)
- [ ] Horizontal Scaling (dipertimbangkan jika traffic tumbuh signifikan)
- [ ] Load Balancer
- [ ] Auto Scaling
- [ ] Database Replication
- [x] Queue (untuk job async, sudah dari awal)

*Catatan: Untuk skala UMKM single-tenant, vertical scaling + queue sudah cukup di fase awal. Horizontal scaling/load balancer masuk roadmap Phase 2/3 (lihat bagian 33).*

### 17.2 Scaling Diagram (Future — bila traffic tumbuh)

```
                  ┌──────────────┐
                  │    Client    │
                  └──────┬───────┘
                         ▼
                  ┌──────────────┐
                  │ Load Balancer│
                  └──────┬───────┘
                         │
             ┌───────────┼───────────┐
             ▼           ▼           ▼
         ┌───────┐   ┌───────┐   ┌───────┐
         │ App 1 │   │ App 2 │   │ App 3 │
         └───┬───┘   └───┬───┘   └───┬───┘
             │           │           │
             └───────────┼───────────┘
                         ▼
                  ┌──────────────┐
                  │   Database   │
                  └──────────────┘
```

## 18. Background Jobs & Queue

### 18.1 Queue Technology

- **Queue:** Laravel Queue — driver `database` (default project, sesuai `QUEUE_CONNECTION=database`)
- **Worker:** `php artisan queue:work` (dijalankan via supervisor/systemd di production)
- **Retry Strategy:** 3x retry dengan backoff, sesuai default Laravel job
- **Dead Letter Queue:** Tabel `failed_jobs` (sudah ada di migration bawaan project)

### 18.2 Background Jobs

| Job | Trigger | Frequency | Retry |
| --- | --- | --- | --- |
| SendCustomCakeNotification | Event: request custom cake baru | On demand | Yes (3x) |
| SendOrderConfirmation | Event: order berhasil dibayar | On demand | Yes |
| CleanupExpiredUnpaidOrders | Schedule | Harian | Yes |
| SyncPaymentStatus (fallback polling) `[ASUMSI, opsional bila webhook tidak reliable]` | Schedule | Setiap 15 menit | Yes |

## 19. Deployment Architecture

### 19.1 Environment

| Environment | Purpose | URL | Database |
| --- | --- | --- | --- |
| Development | Local development | `http://localhost:8000` | SQLite |
| Staging | Testing sebelum rilis | `[ASUMSI: staging.tbloomcake.com]` | MySQL (staging) |
| Production | Live system | `[ASUMSI: tbloomcake.com]` | MySQL (production) |

### 19.2 Deployment Diagram

```
Developer
    │
    ▼
Git Repository (GitHub)
    │
    ▼
CI/CD Pipeline
    │
    ├──────────────┐
    ▼              ▼
  Staging      Production
    │              │
    ▼              ▼
Laravel Cloud   Laravel Cloud
    │              │
    ▼              ▼
 Database       Database
```

## 20. CI/CD Architecture

### 20.1 Pipeline

```
Push Code
   │
   ▼
Lint (Pint --dirty)
   │
   ▼
Unit + Feature Test (Pest)
   │
   ▼
Build (npm run build)
   │
   ▼
Deploy Staging
   │
   ▼
Approval Manual
   │
   ▼
Deploy Production
```

### 20.2 CI/CD Requirements

- **Repository:** GitHub `[ASUMSI]`
- **CI/CD Platform:** GitHub Actions `[ASUMSI]`, deploy ke Laravel Cloud
- **Branch Strategy:** `main` (production), `develop` (staging), feature branch per fitur
- **Deployment Strategy:** Deploy otomatis ke staging saat merge ke `develop`; deploy ke production manual/approval saat merge ke `main`
- **Rollback Strategy:** Rollback ke deployment sebelumnya via Laravel Cloud release history

## 21. Monitoring & Observability

### 21.1 Monitoring

- **Server Monitoring:** Bawaan platform hosting (Laravel Cloud metrics) `[ASUMSI]`
- **Database Monitoring:** Query log lambat via Laravel `DB::listen` / Laravel Pulse (opsional)
- **API Monitoring:** Log request/response ke integrasi eksternal
- **Uptime Monitoring:** `[ASUMSI: UptimeRobot atau sejenis, gratis untuk skala kecil]`
- **Performance Monitoring:** Laravel Pulse (opsional, sudah tersedia sebagai first-party tool)

### 21.2 Metrics

| Metric | Description | Alert Threshold |
| --- | --- | --- |
| CPU | Server CPU usage | > 80% |
| Memory | Memory usage | > 80% |
| Error Rate | HTTP 500 errors | > 1% request |
| Response Time | Rata-rata response time | > 1000 ms |
| Uptime | Ketersediaan layanan | \< 99% |

### 21.3 Observability

```
Application
    │
    ├── Logs (storage/logs)
    ├── Metrics (Laravel Pulse, opsional)
    └── Traces (opsional, belum prioritas untuk skala ini)
          │
          ▼
   Dashboard Hosting Provider
          │
          ▼
        Alert (email/WA ke developer)
```

## 22. Testing Architecture

### 22.1 Testing Strategy

```
                 Testing
                    │
       ┌────────────┼────────────┐
       ▼            ▼            ▼
    Unit Test   Feature Test   E2E Test
   (Services)   (Http/Pest)   (opsional)
```

### 22.2 Test Types

| Test | Scope | Tool | Target |
| --- | --- | --- | --- |
| Unit | Service class (AvailabilityService, ShippingEstimateService) | Pest | 70%+ coverage service kritikal |
| Feature | HTTP flow (checkout, custom request, admin CRUD) | Pest + Laravel HTTP test | Semua flow utama (F-001–F-010 di PRD) |
| E2E | User flow lengkap (browser) | `[ASUMSI: Laravel Dusk, opsional]` | Flow checkout & custom request |
| Load | Performance saat traffic tinggi | `[ASUMSI: k6, opsional]` | 50–100 concurrent users |
| Security | Kerentanan umum (XSS, CSRF, SQLi) | Manual review + `composer audit`/`npm audit` | Critical issues = 0 |

## 23. Infrastructure

### 23.1 Infrastructure Stack

- **Cloud Provider:** Laravel Cloud (sesuai `boost.json` — `cloud: true`)
- **Compute:** Managed PHP runtime Laravel Cloud
- **Database:** MySQL (managed, disediakan Laravel Cloud) `[ASUMSI]`
- **Storage:** Laravel Cloud storage / S3-compatible
- **CDN:** `[ASUMSI: bawaan Laravel Cloud, bila tersedia]`
- **DNS:** `[ASUMSI: dikelola melalui registrar domain mitra]`
- **Container:** Dikelola otomatis oleh Laravel Cloud (abstraksi platform)
- **Queue:** Database-driven queue worker
- **Cache:** Database/file cache (upgrade Redis jika perlu)
- **Monitoring:** Laravel Cloud dashboard + Laravel Pulse (opsional)

### 23.2 Infrastructure Diagram

```
                    Internet
                       │
                       ▼
                  Laravel Cloud
                  (App Runtime)
                       │
                       ▼
                  Cache / Queue
                       │
                       ▼
                    Database
                       │
                       ▼
                    Storage
```

## 24. Configuration & Environment Variables

### 24.1 Environment Variables

| Variable | Environment | Sensitive | Description |
| --- | --- | --- | --- |
| APP_KEY | All | Yes | Encryption key aplikasi |
| DB_CONNECTION / DB_DATABASE / DB_USERNAME / DB_PASSWORD | All | Yes | Koneksi database |
| PAYMENT_GATEWAY_KEY | Staging, Production | Yes | API key payment gateway |
| PAYMENT_GATEWAY_WEBHOOK_SECRET | Staging, Production | Yes | Verifikasi signature webhook |
| WHATSAPP_API_TOKEN | Staging, Production | Yes | Token API notifikasi WhatsApp |
| APP_URL | All | No | Base URL aplikasi |
| FILESYSTEM_DISK | All | No | Disk penyimpanan file aktif |

### 24.2 Secret Management

- **Secret Manager:** Environment variables via `.env` (tidak di-commit, sudah masuk `.gitignore`), dikelola juga lewat dashboard Laravel Cloud untuk production
- **Rotation Strategy:** Rotasi API key payment gateway & WhatsApp minimal setiap 6–12 bulan atau saat dicurigai bocor
- **Access Policy:** Hanya developer/owner yang memiliki akses ke `.env` production

## 25. Architecture Decision Records

**ADR-001 Pemilihan Laravel Monolith (bukan headless/microservices)**

- **Context:** Kebutuhan website untuk UMKM skala kecil–menengah dengan tim development terbatas
- **Problem:** Butuh arsitektur yang cepat dibangun, murah dihosting, dan mudah dirawat satu orang/tim kecil
- **Options:** (1) Laravel Monolith + Blade, (2) Laravel API + Next.js SPA, (3) Microservices
- **Decision:** Laravel Monolith + Blade
- **Reason:** Kompleksitas fitur belum membutuhkan pemisahan frontend/backend; monolith mempercepat development dan menurunkan biaya infrastruktur
- **Consequences:**
  - *Positive:* Development lebih cepat, satu codebase, biaya hosting rendah, cocok untuk tim kecil
  - *Negative:* Jika di masa depan butuh mobile app native, perlu tambahan API layer terpisah

**ADR-002 Guest Checkout untuk Customer**

- **Context:** Target customer TBloom adalah pembeli kasual yang tidak ingin ribet membuat akun
- **Problem:** Apakah customer wajib register untuk checkout?
- **Options:** (1) Wajib register, (2) Guest checkout dengan verifikasi nomor WA
- **Decision:** Guest checkout `[ASUMSI, perlu konfirmasi final]`
- **Reason:** Mengurangi friction saat checkout, meningkatkan konversi
- **Consequences:**
  - *Positive:* Proses checkout lebih cepat
  - *Negative:* Customer tidak punya riwayat order tersimpan di akun; pelacakan status order mengandalkan kode order/nomor WA

## 26. Feature-to-Architecture Mapping

| Feature (PRD) | Frontend | Backend | Database | External Service |
| --- | --- | --- | --- | --- |
| F-001 Katalog Simple Cake | `catalog/index.blade.php` | `CatalogController`, `AvailabilityService` | `products`, `product_variants`, `product_availabilities` | - |
| F-002 Checkout Simple Cake | `checkout/show.blade.php` | `CheckoutController`, `OrderService` | `orders`, `order_items` | Payment Gateway |
| F-003 Form Request Custom Cake | `custom-cake/form.blade.php` | `CustomCakeRequestController` | `custom_cake_requests` | WhatsApp API |
| F-004 Availability Harian | Badge di katalog | `AvailabilityService` | `product_availabilities` | - |
| F-005 Estimasi Ongkir | Checkout page | `ShippingEstimateService` | - | Google Maps API (opsional) |
| F-006 Pembayaran Online | Redirect ke gateway | `PaymentService`, `PaymentWebhookController` | `orders.payment_status` | Payment Gateway |
| F-007 Pembatalan/Perubahan | Halaman status order | `OrderService::cancel/update` | `orders` | - |
| F-008 Testimoni | `testimonials/index.blade.php` | `Admin\TestimonialController` | `testimonials` | - |
| F-009 Admin Panel - Produk & Stok | `admin/products/*` | `Admin\ProductController` | `products`, `product_variants`, `product_availabilities` | Storage |
| F-010 Admin Panel - Manajemen Pesanan | `admin/orders/*` | `Admin\OrderController` | `orders`, `order_items` | - |

## 27. Requirement-to-Architecture Matrix

| Requirement | Architecture Component | Implementation | Status |
| --- | --- | --- | --- |
| REQ-001 Authentication Admin | Session Auth | Laravel built-in Auth | Planned |
| REQ-002 Payment | PaymentService + Webhook | Integrasi Payment Gateway | Planned |
| REQ-003 Notifikasi Custom Request | WhatsAppNotificationService + Job Queue | Integrasi WhatsApp API | Planned |
| REQ-004 Availability Real-time | AvailabilityService + Cache short-TTL | Query + cache invalidation | Planned |
| REQ-005 Refund 80% | OrderService::cancel | Business logic kalkulasi refund | Planned |

## 28. Technical Constraints

- **Constraint 1:** Tim development kemungkinan kecil (1–2 orang) → arsitektur harus tetap sederhana dan mudah dirawat
- **Constraint 2:** Payment gateway final belum ditentukan mitra → interface `PaymentService` harus dibuat abstrak (interface) agar mudah ganti provider
- **Constraint 3:** Belum ada kepastian budget untuk WhatsApp API resmi (berbayar) → fallback ke notifikasi manual di dashboard admin harus tetap berfungsi

**Assumptions**

- Payment gateway & WhatsApp API akan dikonfirmasi mitra sebelum development backend integrasi dimulai
- Traffic awal masih dalam skala kecil (UMKM single-tenant)

**Dependencies**

- Ketersediaan API key dari payment gateway & WhatsApp provider terpilih
- Aset brand (logo, foto produk resolusi tinggi) dari mitra

## 29. Risks & Mitigation

| Risk | Impact | Probability | Mitigation |
| --- | --- | --- | --- |
| Double-booking kuota harian akibat race condition | High | Medium | Gunakan database transaction + row locking (`lockForUpdate`) saat insert order |
| Payment gateway webhook gagal diterima | High | Low–Medium | Fallback polling status pembayaran berkala + retry job |
| WhatsApp API down/limit tercapai | Medium | Medium | Fallback ke notifikasi dashboard admin |
| Kesalahan input data custom request (miss komunikasi tetap terjadi) | Medium | Low | Form terstruktur wajib + preview ringkasan sebelum submit |
| Database failure | High | Low | Backup harian otomatis |

## 30. Disaster Recovery & Backup

### 30.1 Backup

- **Database Backup:** Backup otomatis harian (managed oleh hosting/Laravel Cloud) `[ASUMSI]`
- **File Backup:** Backup storage (foto produk, upload referensi) mengikuti jadwal yang sama
- **Backup Frequency:** Harian
- **Backup Retention:** 30 hari `[ASUMSI]`

### 30.2 Recovery

- **RPO (Recovery Point Objective):** ≤ 24 jam
- **RTO (Recovery Time Objective):** ≤ 4 jam `[ASUMSI, sesuaikan dengan SLA hosting]`
- **Recovery Procedure:** Restore dari backup terakhir via dashboard hosting provider
- **Failover Strategy:** Tidak ada failover otomatis di fase awal (skala kecil); ditambahkan di roadmap bila diperlukan

## 31. Migration Strategy

### 31.1 Database Migration

- **Migration Tool:** Laravel Migration (`php artisan migrate`)
- **Migration Process:** Semua perubahan schema melalui file migration versioned, dijalankan otomatis saat deploy
- **Rollback Strategy:** `php artisan migrate:rollback` untuk migration terakhir bila terjadi masalah

### 31.2 Version Migration

```
Version 1 (MVP: Simple Cake + Custom Request Form)
   │
   ▼
Migration (tambah fitur laporan, dsb — lihat bagian 33)
   │
   ▼
Version 2
   │
   ▼
Validation (staging test)
   │
   ▼
Production
```

## 32. Architecture Checklist

**Architecture**

- [x] Architecture pattern defined (Modular Monolith Laravel)
- [x] System context defined
- [x] Component architecture defined
- [x] Data flow defined
- [x] External integrations defined (perlu konfirmasi provider final)

**Frontend**

- [x] Frontend structure defined (Blade + Tailwind)
- [x] Component strategy defined
- [ ] State management defined (belum relevan — server-rendered)
- [x] Routing defined (`routes/web.php`)

**Backend**

- [x] API architecture defined (internal AJAX endpoints)
- [x] Service structure defined
- [x] Authentication defined
- [x] Authorization defined
- [x] Error handling defined

**Database**

- [x] ERD defined
- [x] Tables defined
- [x] Relationships defined
- [ ] Index strategy defined (perlu detail migration lanjutan)
- [ ] Backup strategy defined (menunggu konfirmasi provider hosting)

**Security**

- [x] Authentication
- [x] Authorization
- [ ] Encryption (perlu detail lanjut untuk data tertentu)
- [x] Rate limiting
- [ ] Security headers (perlu implementasi eksplisit)
- [x] Secret management

**Infrastructure**

- [x] Development environment
- [ ] Staging environment (perlu setup)
- [ ] Production environment (perlu setup)
- [ ] CI/CD (perlu setup GitHub Actions)
- [ ] Monitoring (perlu setup)
- [x] Logging (bawaan Laravel)
- [ ] Backup (perlu konfirmasi provider)
- [ ] Disaster recovery (perlu detail lanjut)

## 33. Future Architecture

**Phase 1 (MVP — sesuai scope PRD saat ini)**

- Katalog & checkout Simple Cake
- Form request Custom Cake
- Admin panel dasar (produk, stok, pesanan, testimoni)
- Integrasi payment gateway

**Phase 2**

- Dashboard laporan penjualan untuk Owner
- Integrasi WhatsApp otomatis untuk update status pesanan ke customer
- Redis cache & queue driver untuk performa lebih baik
- Laravel Pulse untuk observability lebih detail

**Phase 3**

- Ekspansi ke penjualan Dessert/Minuman/Pastry (jika diputuskan masuk scope)
- Horizontal scaling (load balancer, multi-instance) jika traffic tumbuh signifikan
- Sistem tracking pengiriman real-time (integrasi API kurir/GoCar)

**Potential Improvements**

- API layer terpisah (Sanctum token) bila suatu saat butuh aplikasi mobile native
- Sistem loyalty/membership untuk pelanggan tetap

## 34. Architecture Changelog

| Version | Date | Author | Changes |
| --- | --- | --- | --- |
| 1.0 | 2026-09-28 | `[Nama kamu]` | Initial architecture berdasarkan PRD v1.0 dan stack Laravel 13 yang sudah berjalan |
