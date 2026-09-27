# Database Schema - Website TBloom Cake

## 1. Database Overview

- **Nama project:** Website TBloom Cake
- **Nama database:** `tbloomcake` (production), `database.sqlite` (development/testing)
- **Versi schema:** 1.0.0
- **Tanggal:** 28 September 2026
- **Author:** `[Nama kamu]`
- **Status:** Draft
- **Related PRD:** PRD - Website TBloom Cake (v1.0)
- **Related Architecture Document:** Website Architecture Document - TBloom Cake (v1.0)

### 1.1 Database Purpose

- **Tujuan database:** Menyimpan data produk, availability harian, pesanan Simple Cake, request Custom Cake, testimoni, dan akun Admin/Owner
- **Jenis data yang disimpan:**
  - **Data utama (master):** `products`, `product_variants`, `users` (admin/owner)
  - **Data transaksi:** `orders`, `order_items`, `custom_cake_requests`, `payment_transactions`
  - **Data konfigurasi:** `product_availabilities` (kuota harian per varian produk)
  - **Data audit/log:** `order_status_histories`, `failed_jobs` (bawaan Laravel queue)

### 1.2 Database Technology

- **Database:** MySQL 8 (production), SQLite (development/testing — sesuai default project saat ini)
- **Version:** MySQL 8.x
- **ORM:** Eloquent (Laravel 13)
- **Migration Tool:** Laravel Migration
- **Hosting:** Laravel Cloud (managed database) `[ASUMSI, sesuai boost.json]`
- **Backup Strategy:** Backup harian otomatis oleh provider hosting `[ASUMSI, perlu konfirmasi]`

## 2. Database Architecture

### 2.1 Database Architecture Pattern

- [x] Relational
- [ ] NoSQL
- [ ] Hybrid

### 2.2 Database Diagram

```
┌──────────────┐
│    users     │  (admin/owner login)
└──────────────┘

┌──────────────┐        ┌───────────────────┐        ┌──────────────────────────┐
│   products   │───1:N─▶│ product_variants  │───1:N─▶│ product_availabilities   │
└──────────────┘        └─────────┬─────────┘        └──────────────────────────┘
                                   │ 1:N
                                   ▼
                          ┌────────────────┐
                          │  order_items   │
                          └───────┬────────┘
                                  │ N:1
                                  ▼
                          ┌──────────────┐        ┌──────────────────────────┐
                          │    orders    │───1:N─▶│ order_status_histories   │
                          └──────┬───────┘        └──────────────────────────┘
                                 │ 1:N
                                 ▼
                          ┌──────────────────────┐
                          │ payment_transactions │
                          └──────────────────────┘

┌──────────────────────┐        ┌──────────────┐
│ custom_cake_requests  │        │ testimonials │
└──────────────────────┘        └──────────────┘
```

## 3. Entity Overview

| Entity | Description | Type | Related Feature |
| --- | --- | --- | --- |
| users | Akun Admin & Owner (bukan customer — customer checkout sebagai guest) | Master | Authentication, F-009, F-010 |
| products | Data produk induk (Simple Cake, Cup Cake, Bento Cake) | Master | F-001, F-009 |
| product_variants | Varian ukuran & harga per produk | Master | F-001, F-002, F-009 |
| product_availabilities | Kuota harian per varian produk | Master/Config | F-004 |
| orders | Data pesanan Simple Cake | Transaction | F-002, F-006, F-007, F-010 |
| order_items | Detail item dalam satu order | Transaction | F-002, F-010 |
| order_status_histories | Riwayat perubahan status order (audit) | Log | F-007, F-010 |
| payment_transactions | Log transaksi/callback dari payment gateway | Transaction | F-006 |
| custom_cake_requests | Data request Custom Cake dari customer | Transaction | F-003 |
| testimonials | Testimoni customer | Master | F-008 |

## 4. Naming Convention

### 4.1 Table Naming

- **Format:** snake_case, plural
- **Case:** lowercase
- **Singular / plural:** Plural (mengikuti konvensi default Laravel/Eloquent)
- **Example:** `users`, `products`, `product_variants`, `orders`, `order_items`, `custom_cake_requests`, `testimonials`

### 4.2 Column Naming

- **Format:** snake_case
- **Primary Key:** `id` (BIGINT UNSIGNED, auto-increment — sesuai default Laravel, bukan UUID)
- **Foreign Key:** `[singular_table]_id` (mis. `product_id`, `order_id`)
- **Timestamp:** `created_at`, `updated_at`, `deleted_at` (soft delete hanya pada tabel tertentu — lihat bagian 12)

### 4.3 Other Conventions

- **Boolean:** `is_active`, `is_published`
- **Status:** `status`, `payment_status`
- **Amount:** `price`, `total_amount`, `shipping_cost`, `refund_amount`

## 5. Data Type Standards

| Purpose | Data Type | Example |
| --- | --- | --- |
| ID | BIGINT UNSIGNED (auto-increment) | 1, 2, 3 |
| Kode Order (public-facing) | VARCHAR(30) | TBC-20260930-0001 |
| Short Text | VARCHAR(255) | "Chocolate Cake" |
| Long Text | TEXT | Deskripsi produk |
| Integer | INTEGER | Jumlah (quantity) |
| Decimal (uang) | DECIMAL(12,2) | 155000.00 |
| Boolean | BOOLEAN (TINYINT(1)) | true/false |
| Date | DATE | 2026-09-30 |
| Date Time | TIMESTAMP | 2026-09-30 14:00:00 |
| JSON | JSON | `{"filling":"cokelat","fondant":true}` |

## 6. Table Schema

### TBL-001 users

**Description** Akun login untuk Admin dan Owner (customer TIDAK memiliki akun — checkout sebagai guest sesuai keputusan PRD/ADR-002).

**Table Definition**

| Column | Data Type | Nullable | Default | Key | Unique | Description |
| --- | --- | --- | --- | --- | --- | --- |
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Yes | User ID |
| name | VARCHAR(255) | No | - | - | No | Nama admin/owner |
| email | VARCHAR(255) | No | - | - | Yes | Email login |
| email_verified_at | TIMESTAMP | Yes | NULL | - | No | Waktu verifikasi email |
| password | VARCHAR(255) | No | - | - | No | Password (hashed, bcrypt) |
| role | VARCHAR(20) | No | 'admin' | - | No | Nilai: `admin`, `owner` |
| remember_token | VARCHAR(100) | Yes | NULL | - | No | Token "remember me" |
| created_at | TIMESTAMP | No | NOW() | - | No | Tanggal dibuat |
| updated_at | TIMESTAMP | No | NOW() | - | No | Tanggal diubah |

**Primary Key:** `users.id` **Foreign Keys:** None **Indexes:**

- `UNIQUE INDEX users_email_unique ON users(email)`
- `INDEX users_role_idx ON users(role)`

**Constraints**

- Email harus unique dan format valid
- Password disimpan sebagai hash (bcrypt), tidak pernah plaintext
- Role harus salah satu dari: `admin`, `owner`
- Tidak ada soft delete pada tabel ini — akun admin dinonaktifkan via kolom terpisah bila diperlukan `[ASUMSI: tambah is_active bila dibutuhkan fitur nonaktifkan admin]`

---

### TBL-002 products

**Description** Data produk induk: Simple Cake, Cup Cake, Bento Cake. Harga & ukuran spesifik ada di `product_variants`.

| Column | Data Type | Nullable | Default | Key | Unique | Description |
| --- | --- | --- | --- | --- | --- | --- |
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Yes | Product ID |
| category | VARCHAR(30) | No | - | - | No | `simple_cake`, `cup_cake`, `bento_cake` |
| name | VARCHAR(255) | No | - | - | No | Nama produk |
| slug | VARCHAR(255) | No | - | - | Yes | URL slug |
| description | TEXT | Yes | NULL | - | No | Deskripsi produk |
| photo_path | VARCHAR(255) | Yes | NULL | - | No | Path foto utama |
| is_active | BOOLEAN | No | true | - | No | Status tampil di katalog |
| created_at | TIMESTAMP | No | NOW() | - | No | Tanggal dibuat |
| updated_at | TIMESTAMP | No | NOW() | - | No | Tanggal diubah |
| deleted_at | TIMESTAMP | Yes | NULL | - | No | Soft delete |

**Primary Key:** `products.id` **Indexes:**

- `UNIQUE INDEX products_slug_unique ON products(slug)`
- `INDEX products_category_idx ON products(category)`
- `INDEX products_is_active_idx ON products(is_active)`

**Constraints**

- `slug` harus unique
- `category` harus salah satu dari nilai enum yang valid (lihat bagian 9)
- Produk yang di-soft-delete tidak boleh tampil di katalog maupun dipilih saat checkout

---

### TBL-003 product_variants

**Description** Varian ukuran & harga dari sebuah produk (mis. Simple Cake 10cm = Rp65.000, Cup Cake isi 6, dst).

| Column | Data Type | Nullable | Default | Key | Unique | Description |
| --- | --- | --- | --- | --- | --- | --- |
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Yes | Variant ID |
| product_id | BIGINT UNSIGNED | No | - | FK | No | Relasi ke `products.id` |
| size_label | VARCHAR(50) | No | - | - | No | Label ukuran (mis. "10 cm", "Isi 6") |
| price | DECIMAL(12,2) | No | 0 | - | No | Harga varian |
| max_daily_quota | INTEGER | No | 5 | - | No | Batas order per hari (default 5) |
| is_active | BOOLEAN | No | true | - | No | Status varian aktif |
| created_at | TIMESTAMP | No | NOW() | - | No | Tanggal dibuat |
| updated_at | TIMESTAMP | No | NOW() | - | No | Tanggal diubah |

**Primary Key:** `product_variants.id` **Foreign Key:** `product_variants.product_id → products.id` **Indexes:**

- `INDEX product_variants_product_id_idx ON product_variants(product_id)`
- `UNIQUE INDEX product_variants_product_size_unique ON product_variants(product_id, size_label)`

**Constraints**

- `price >= 0`
- `max_daily_quota > 0`
- Kombinasi `product_id` + `size_label` harus unique (tidak boleh ada duplikat ukuran pada produk yang sama)

---

### TBL-004 product_availabilities

**Description** Kuota harian per varian produk. Baris hanya dibuat saat pertama kali ada order masuk untuk tanggal tsb (lazy creation) untuk menghindari tabel membengkak dengan tanggal kosong.

| Column | Data Type | Nullable | Default | Key | Unique | Description |
| --- | --- | --- | --- | --- | --- | --- |
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Yes | Availability ID |
| product_variant_id | BIGINT UNSIGNED | No | - | FK | No | Relasi ke `product_variants.id` |
| date | DATE | No | - | - | No | Tanggal availability |
| used_quota | INTEGER | No | 0 | - | No | Jumlah order valid pada tanggal ini |
| created_at | TIMESTAMP | No | NOW() | - | No | Tanggal dibuat |
| updated_at | TIMESTAMP | No | NOW() | - | No | Tanggal diubah |

**Primary Key:** `product_availabilities.id` **Foreign Key:** `product_availabilities.product_variant_id → product_variants.id` **Indexes:**

- `UNIQUE INDEX availabilities_variant_date_unique ON product_availabilities(product_variant_id, date)`
- `INDEX availabilities_date_idx ON product_availabilities(date)`

**Constraints**

- `used_quota >= 0` dan `used_quota <= product_variants.max_daily_quota` (divalidasi di application layer, bukan di level DB)
- Kombinasi `product_variant_id` + `date` harus unique — inilah baris yang dikunci (`lockForUpdate`) saat proses checkout untuk mencegah race condition

---

### TBL-005 orders

**Description** Data pesanan Simple Cake dari customer (guest checkout — tanpa `user_id` karena customer tidak login).

| Column | Data Type | Nullable | Default | Key | Unique | Description |
| --- | --- | --- | --- | --- | --- | --- |
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Yes | Order ID |
| order_code | VARCHAR(30) | No | - | - | Yes | Kode order publik (mis. TBC-20260930-0001) |
| customer_name | VARCHAR(255) | No | - | - | No | Nama customer |
| customer_whatsapp | VARCHAR(20) | No | - | - | No | Nomor WhatsApp customer |
| customer_email | VARCHAR(255) | Yes | NULL | - | No | Email (opsional, untuk kirim invoice) |
| status | VARCHAR(20) | No | 'pending' | - | No | Lihat ENUM-002 |
| payment_status | VARCHAR(20) | No | 'unpaid' | - | No | Lihat ENUM-003 |
| pickup_date | DATE | No | - | - | No | Tanggal pengambilan/pengiriman |
| pickup_time | TIME | No | - | - | No | Jam pengambilan (maks. 21:00) |
| delivery_type | VARCHAR(10) | No | 'pickup' | - | No | `pickup` atau `delivery` |
| delivery_address | TEXT | Yes | NULL | - | No | Alamat (wajib jika `delivery`) |
| delivery_recipient_phone | VARCHAR(20) | Yes | NULL | - | No | Nomor penerima (jika beda dari pemesan) |
| shipping_cost | DECIMAL(12,2) | Yes | 0 | - | No | Estimasi/biaya ongkir final |
| subtotal_amount | DECIMAL(12,2) | No | 0 | - | No | Subtotal item |
| total_amount | DECIMAL(12,2) | No | 0 | - | No | Total akhir (subtotal + ongkir) |
| refund_amount | DECIMAL(12,2) | Yes | NULL | - | No | Nominal refund jika dibatalkan (80% dari total) |
| cancellation_reason | TEXT | Yes | NULL | - | No | Alasan pembatalan/perubahan |
| created_at | TIMESTAMP | No | NOW() | - | No | Tanggal order dibuat |
| updated_at | TIMESTAMP | No | NOW() | - | No | Tanggal diubah |

**Primary Key:** `orders.id` **Foreign Keys:** None (guest checkout — tidak terhubung ke `users`) **Indexes:**

- `UNIQUE INDEX orders_order_code_unique ON orders(order_code)`
- `INDEX orders_status_idx ON orders(status)`
- `INDEX orders_pickup_date_idx ON orders(pickup_date)`
- `INDEX orders_customer_whatsapp_idx ON orders(customer_whatsapp)` — untuk customer melacak order tanpa login

**Constraints**

- `total_amount >= 0`, `subtotal_amount >= 0`, `shipping_cost >= 0`
- `delivery_address` wajib diisi (NOT NULL secara aplikatif) jika `delivery_type = 'delivery'`
- `order_code` harus unique, di-generate otomatis saat order dibuat

---

### TBL-006 order_items

**Description** Detail item dalam satu order (bisa lebih dari satu varian dalam satu order, meski umumnya 1 order = 1 item).

| Column | Data Type | Nullable | Default | Key | Unique | Description |
| --- | --- | --- | --- | --- | --- | --- |
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Yes | Order Item ID |
| order_id | BIGINT UNSIGNED | No | - | FK | No | Relasi ke `orders.id` |
| product_variant_id | BIGINT UNSIGNED | No | - | FK | No | Relasi ke `product_variants.id` |
| quantity | INTEGER | No | 1 | - | No | Jumlah pesanan |
| unit_price | DECIMAL(12,2) | No | 0 | - | No | Harga per unit saat order dibuat (snapshot) |
| subtotal | DECIMAL(12,2) | No | 0 | - | No | `unit_price * quantity` |
| flavor | VARCHAR(100) | Yes | NULL | - | No | Rasa cake |
| filling | VARCHAR(100) | Yes | NULL | - | No | Isi/filling cake |
| color | VARCHAR(100) | Yes | NULL | - | No | Warna cake/cover |
| cake_text | VARCHAR(150) | Yes | NULL | - | No | Tulisan pada cake (maks. 10 kata, divalidasi di app) |
| created_at | TIMESTAMP | No | NOW() | - | No | Tanggal dibuat |

**Primary Key:** `order_items.id` **Foreign Keys:**

- `order_items.order_id → orders.id` (ON DELETE CASCADE)
- `order_items.product_variant_id → product_variants.id` (ON DELETE RESTRICT)

**Indexes:**

- `INDEX order_items_order_id_idx ON order_items(order_id)`

**Constraints**

- `quantity > 0`
- `unit_price >= 0`
- `subtotal = unit_price * quantity` (divalidasi di application layer saat insert)
- Harga (`unit_price`) disimpan sebagai snapshot agar perubahan harga produk di kemudian hari tidak mengubah riwayat order lama

---

### TBL-007 order_status_histories

**Description** Audit trail perubahan status order (untuk transparansi & investigasi jika ada dispute dengan customer).

| Column | Data Type | Nullable | Default | Key | Unique | Description |
| --- | --- | --- | --- | --- | --- | --- |
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Yes | History ID |
| order_id | BIGINT UNSIGNED | No | - | FK | No | Relasi ke `orders.id` |
| changed_by_user_id | BIGINT UNSIGNED | Yes | NULL | FK | No | Admin/Owner yang mengubah (NULL jika sistem otomatis, mis. webhook) |
| from_status | VARCHAR(20) | Yes | NULL | - | No | Status sebelumnya |
| to_status | VARCHAR(20) | No | - | - | No | Status baru |
| note | TEXT | Yes | NULL | - | No | Catatan tambahan |
| created_at | TIMESTAMP | No | NOW() | - | No | Waktu perubahan |

**Primary Key:** `order_status_histories.id` **Foreign Keys:**

- `order_status_histories.order_id → orders.id` (ON DELETE CASCADE)
- `order_status_histories.changed_by_user_id → users.id` (ON DELETE SET NULL)

**Indexes:**

- `INDEX order_status_histories_order_id_idx ON order_status_histories(order_id)`

---

### TBL-008 payment_transactions

**Description** Log setiap request/callback dari payment gateway, terpisah dari `orders` agar riwayat transaksi tidak hilang meski status order berubah lagi.

| Column | Data Type | Nullable | Default | Key | Unique | Description |
| --- | --- | --- | --- | --- | --- | --- |
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Yes | Transaction ID |
| order_id | BIGINT UNSIGNED | No | - | FK | No | Relasi ke `orders.id` |
| gateway | VARCHAR(30) | No | - | - | No | Nama provider (mis. `midtrans`, `xendit`) `[ASUMSI]` |
| gateway_reference | VARCHAR(100) | Yes | NULL | - | No | ID transaksi dari gateway |
| amount | DECIMAL(12,2) | No | 0 | - | No | Nominal transaksi |
| status | VARCHAR(20) | No | 'pending' | - | No | Status dari gateway (mis. `settlement`, `expire`, `failed`) |
| raw_payload | JSON | Yes | NULL | - | No | Payload lengkap dari webhook (untuk debugging) |
| created_at | TIMESTAMP | No | NOW() | - | No | Waktu diterima |

**Primary Key:** `payment_transactions.id` **Foreign Key:** `payment_transactions.order_id → orders.id` (ON DELETE CASCADE) **Indexes:**

- `INDEX payment_transactions_order_id_idx ON payment_transactions(order_id)`
- `UNIQUE INDEX payment_transactions_gateway_ref_unique ON payment_transactions(gateway, gateway_reference)` — mencegah pemrosesan webhook duplikat (idempotency)

---

### TBL-009 custom_cake_requests

**Description** Data pengajuan Custom Cake dari customer, diteruskan ke admin via WhatsApp/dashboard.

| Column | Data Type | Nullable | Default | Key | Unique | Description |
| --- | --- | --- | --- | --- | --- | --- |
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Yes | Request ID |
| customer_name | VARCHAR(255) | No | - | - | No | Nama customer |
| customer_whatsapp | VARCHAR(20) | No | - | - | No | Nomor WhatsApp customer |
| pickup_date | DATE | No | - | - | No | Tanggal pengambilan diinginkan |
| size_label | VARCHAR(50) | Yes | NULL | - | No | Ukuran diinginkan |
| flavor | VARCHAR(100) | Yes | NULL | - | No | Rasa |
| filling | VARCHAR(100) | Yes | NULL | - | No | Filling |
| color | VARCHAR(100) | Yes | NULL | - | No | Warna cover |
| cake_text | TEXT | Yes | NULL | - | No | Tulisan pada cake |
| wants_fondant | BOOLEAN | No | false | - | No | Permintaan fondant |
| fondant_detail | TEXT | Yes | NULL | - | No | Detail karakter/desain fondant |
| wants_print | BOOLEAN | No | false | - | No | Permintaan print edible (min. H-7) |
| additional_notes | TEXT | Yes | NULL | - | No | Catatan tambahan customer |
| reference_image_path | VARCHAR(255) | Yes | NULL | - | No | Path upload gambar referensi |
| status | VARCHAR(20) | No | 'new' | - | No | Lihat ENUM-004 |
| notified_at | TIMESTAMP | Yes | NULL | - | No | Waktu notifikasi WhatsApp terkirim ke admin |
| created_at | TIMESTAMP | No | NOW() | - | No | Tanggal request dibuat |
| updated_at | TIMESTAMP | No | NOW() | - | No | Tanggal diubah |

**Primary Key:** `custom_cake_requests.id` **Foreign Keys:** None **Indexes:**

- `INDEX custom_cake_requests_status_idx ON custom_cake_requests(status)`
- `INDEX custom_cake_requests_pickup_date_idx ON custom_cake_requests(pickup_date)`

**Constraints**

- `pickup_date` minimal H-3 dari tanggal submit (H-7 jika `wants_print = true`) — divalidasi di application layer
- `notified_at = NULL` berarti notifikasi WhatsApp gagal terkirim → tetap tampil di dashboard admin sebagai perlu ditindaklanjuti manual

---

### TBL-010 testimonials

**Description** Testimoni customer yang dikurasi dan dipublikasikan oleh admin.

| Column | Data Type | Nullable | Default | Key | Unique | Description |
| --- | --- | --- | --- | --- | --- | --- |
| id | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Yes | Testimonial ID |
| customer_name | VARCHAR(255) | No | - | - | No | Nama customer |
| content | TEXT | No | - | - | No | Isi testimoni |
| rating | TINYINT UNSIGNED | Yes | NULL | - | No | Rating 1–5 |
| photo_path | VARCHAR(255) | Yes | NULL | - | No | Foto pendukung (opsional) |
| is_published | BOOLEAN | No | false | - | No | Status tampil publik |
| created_by_user_id | BIGINT UNSIGNED | No | - | FK | No | Admin/Owner yang menginput |
| created_at | TIMESTAMP | No | NOW() | - | No | Tanggal dibuat |
| updated_at | TIMESTAMP | No | NOW() | - | No | Tanggal diubah |

**Primary Key:** `testimonials.id` **Foreign Key:** `testimonials.created_by_user_id → users.id` (ON DELETE RESTRICT) **Indexes:**

- `INDEX testimonials_is_published_idx ON testimonials(is_published)`

**Constraints**

- `rating` antara 1–5 jika diisi
- Hanya baris dengan `is_published = true` yang ditampilkan di halaman publik

## 7. Table Relationship

**REL-001**

- **Relationship:** `products` 1 ─── N `product_variants`
- **Description:** Satu produk memiliki banyak varian ukuran/harga
- **Foreign Key:** `product_variants.product_id → products.id`
- **Delete Behavior:** ON DELETE RESTRICT (produk tidak boleh dihapus jika masih punya varian aktif; nonaktifkan via `is_active`/soft delete)
- **Update Behavior:** ON UPDATE CASCADE

**REL-002**

- **Relationship:** `product_variants` 1 ─── N `product_availabilities`
- **Description:** Satu varian memiliki data kuota untuk banyak tanggal
- **Foreign Key:** `product_availabilities.product_variant_id → product_variants.id`
- **Delete Behavior:** ON DELETE CASCADE
- **Update Behavior:** ON UPDATE CASCADE

**REL-003**

- **Relationship:** `orders` 1 ─── N `order_items`
- **Description:** Satu order dapat memiliki banyak item
- **Foreign Key:** `order_items.order_id → orders.id`
- **Delete Behavior:** ON DELETE CASCADE
- **Update Behavior:** ON UPDATE CASCADE

**REL-004**

- **Relationship:** `product_variants` 1 ─── N `order_items`
- **Description:** Satu varian dapat muncul di banyak order item
- **Foreign Key:** `order_items.product_variant_id → product_variants.id`
- **Delete Behavior:** ON DELETE RESTRICT (menjaga histori order tetap valid)
- **Update Behavior:** ON UPDATE CASCADE

**REL-005**

- **Relationship:** `orders` 1 ─── N `payment_transactions`
- **Description:** Satu order dapat memiliki banyak log transaksi (retry, callback ganda, dll)
- **Foreign Key:** `payment_transactions.order_id → orders.id`
- **Delete Behavior:** ON DELETE CASCADE
- **Update Behavior:** ON UPDATE CASCADE

**REL-006**

- **Relationship:** `orders` 1 ─── N `order_status_histories`
- **Description:** Satu order memiliki banyak riwayat perubahan status
- **Foreign Key:** `order_status_histories.order_id → orders.id`
- **Delete Behavior:** ON DELETE CASCADE
- **Update Behavior:** ON UPDATE CASCADE

**REL-007**

- **Relationship:** `users` 1 ─── N `testimonials`
- **Description:** Satu admin/owner dapat menginput banyak testimoni
- **Foreign Key:** `testimonials.created_by_user_id → users.id`
- **Delete Behavior:** ON DELETE RESTRICT
- **Update Behavior:** ON UPDATE CASCADE

*Catatan: `custom_cake_requests` tidak memiliki foreign key ke `orders` karena Custom Cake tidak diproses melalui sistem order website — kesepakatan harga & produksi terjadi manual via WhatsApp (sesuai PRD F-003).*

## 8. Table Schema - Related Tables

*(Sudah dijabarkan lengkap pada bagian 6 di atas: TBL-002 s.d. TBL-010)*

## 9. Enum / Status Definition

**ENUM-001 product category**

- `simple_cake`
- `cup_cake`
- `bento_cake`

**ENUM-002 order status**

- `pending` — order dibuat, menunggu pembayaran
- `confirmed` — pembayaran diterima, dikonfirmasi admin
- `in_progress` — sedang diproduksi
- `completed` — selesai diambil/dikirim
- `cancelled` — dibatalkan

**Status Transition**

```
pending
   │
   ▼
confirmed
   │
   ▼
in_progress
   │
   ▼
completed

Alternative:
pending ───────▶ cancelled
confirmed ─────▶ cancelled   (maks. H-2, refund 80%)
```

**ENUM-003 payment_status**

- `unpaid`
- `paid`
- `refunded`

**ENUM-004 custom_cake_requests status**

- `new` — baru masuk, belum ditindaklanjuti
- `contacted` — admin sudah menghubungi customer via WhatsApp
- `confirmed` — kesepakatan tercapai, masuk produksi (di luar sistem)
- `rejected` — request ditolak/dibatalkan

## 10. Index Strategy

**IDX-001**

- **Table:** `users`
- **Column:** `email`
- **Type:** UNIQUE INDEX
- **Reason:** Login admin/owner berdasarkan email, mencegah duplikasi akun

**IDX-002**

- **Table:** `orders`
- **Column:** `status`, `pickup_date`
- **Type:** INDEX (composite disarankan: `orders(status, pickup_date)`)
- **Reason:** Query dashboard admin untuk pesanan aktif per tanggal

**IDX-003**

- **Table:** `product_availabilities`
- **Column:** `product_variant_id`, `date`
- **Type:** UNIQUE INDEX
- **Reason:** Memastikan hanya ada satu baris kuota per varian per tanggal, sekaligus mempercepat lookup availability saat checkout

**IDX-004**

- **Table:** `orders`
- **Column:** `customer_whatsapp`
- **Type:** INDEX
- **Reason:** Customer melacak status order tanpa login, cukup dengan nomor WA + kode order

**IDX-005**

- **Table:** `payment_transactions`
- **Column:** `gateway`, `gateway_reference`
- **Type:** UNIQUE INDEX
- **Reason:** Idempotency — mencegah webhook yang sama diproses dua kali

## 11. Constraint Strategy

**Primary Key**

- Semua tabel wajib memiliki primary key `id` (BIGINT UNSIGNED, auto-increment) — mengikuti konvensi default Laravel yang sudah dipakai di project (bukan UUID, untuk kesederhanaan & performa index)

**Foreign Key**

- Semua relationship antar entity menggunakan foreign key dengan `ON DELETE` behavior eksplisit (lihat bagian 7)

**Unique**

- `users.email`
- `products.slug`
- `orders.order_code`
- `product_variants(product_id, size_label)` — composite unique
- `product_availabilities(product_variant_id, date)` — composite unique
- `payment_transactions(gateway, gateway_reference)` — composite unique

**Check Constraint**

- `CHECK (product_variants.price >= 0)`
- `CHECK (product_variants.max_daily_quota > 0)`
- `CHECK (order_items.quantity > 0)`
- `CHECK (order_items.unit_price >= 0)`
- `CHECK (orders.total_amount >= 0)`
- `CHECK (product_availabilities.used_quota >= 0)`
- `CHECK (testimonials.rating BETWEEN 1 AND 5)`

*Catatan: MySQL 8 mendukung CHECK constraint, tapi sebagian tim memilih validasi ini cukup di level aplikasi (Form Request/Model) mengikuti gaya Laravel — keputusan akhir menyesuaikan preferensi tim.*

## 12. Soft Delete Strategy

**Tables Using Soft Delete**

- `products` (agar riwayat order lama tetap valid meski produk dihapus dari katalog)

**Tables NOT Using Soft Delete**

- `orders`, `order_items`, `custom_cake_requests`, `testimonials`, `users`, `product_variants`, `product_availabilities` — dihapus permanen jika memang perlu, atau cukup dinonaktifkan via kolom `is_active`/`status` `[ASUMSI: dapat direvisi bila tim ingin soft delete lebih luas, mis. untuk product_variants]`

**Column:** `deleted_at TIMESTAMP NULL`

**Rules**

- `deleted_at = NULL` → aktif
- `deleted_at != NULL` → dihapus (soft)
- Data yang di-soft-delete tidak ditampilkan pada query default (`SoftDeletes` trait Eloquent)
- Data tidak benar-benar dihapus kecuali melalui proses `forceDelete()` khusus (dibatasi ke Owner)

## 13. Audit Trail

**TBL-007 order_status_histories** *(sudah dijabarkan detail di bagian 6)* menjadi audit trail utama untuk perubahan status order.

**Contoh**

```
Order: TBC-20260930-0001
changed_by_user_id: 2 (admin)
from_status: pending
to_status: confirmed
note: "Pembayaran dikonfirmasi manual setelah cek mutasi bank"
```

*Catatan: Audit trail generik seperti `audit_logs` (mencatat semua perubahan di semua tabel) tidak dibuat di fase awal untuk menjaga kesederhanaan — cukup `order_status_histories` untuk kebutuhan utama (transparansi status pesanan). Bisa ditambahkan di Phase 2 jika dibutuhkan audit lebih luas (mis. perubahan harga produk).*

## 14. Database Transaction Rules

**Transaction-001**

- **Use Case:** Create Order (Checkout Simple Cake)

```
BEGIN
 │
 ├── SELECT product_availabilities ... FOR UPDATE (lock baris kuota tanggal terkait)
 │
 ├── Validasi: used_quota < max_daily_quota?
 │      │
 │      └── Jika tidak → ROLLBACK, tampilkan "Kuota penuh"
 │
 ├── Create Order
 │
 ├── Create Order Items
 │
 ├── Increment used_quota pada product_availabilities
 │
 └── Create order_status_histories (pending)
       │
       ▼
     COMMIT
```

**Business Rules**

- Order tidak boleh dibuat jika kuota tidak mencukupi
- Row locking (`lockForUpdate`) pada `product_availabilities` wajib digunakan untuk mencegah race condition saat dua customer checkout bersamaan di detik yang sama
- Order dan order items harus berhasil dibuat secara atomic

**Transaction-002**

- **Use Case:** Payment Webhook Update

```
BEGIN
 │
 ├── Verifikasi signature webhook
 │
 ├── Cek idempotency: apakah gateway_reference sudah pernah diproses?
 │      │
 │      └── Jika ya → SKIP (return 200 tanpa proses ulang)
 │
 ├── Create payment_transactions (log)
 │
 ├── Update orders.payment_status
 │
 └── Create order_status_histories
       │
       ▼
     COMMIT
```

**Transaction-003**

- **Use Case:** Pembatalan Order

```
BEGIN
 │
 ├── Validasi: apakah masih dalam batas H-2?
 │      │
 │      └── Jika tidak → ROLLBACK, tolak permintaan
 │
 ├── Update orders.status = 'cancelled'
 │
 ├── Hitung refund_amount = total_amount * 0.8
 │
 ├── Decrement used_quota pada product_availabilities terkait (mengembalikan kuota)
 │
 └── Create order_status_histories
       │
       ▼
     COMMIT
```

## 15. Data Validation Rules

| Table | Field | Rule |
| --- | --- | --- |
| users | email | Required + format email valid |
| users | password | Minimum 8 karakter, di-hash sebelum simpan |
| product_variants | price | >= 0 |
| product_variants | max_daily_quota | > 0 |
| orders | total_amount | >= 0 |
| orders | pickup_date | Minimal H-1 dari tanggal order dibuat |
| orders | delivery_address | Wajib diisi jika `delivery_type = 'delivery'` |
| order_items | quantity | > 0 |
| order_items | cake_text | Maksimal 10 kata (divalidasi di application layer) |
| custom_cake_requests | pickup_date | Minimal H-3 (H-7 jika `wants_print = true`) |
| testimonials | rating | Antara 1–5 jika diisi |

## 16. Data Lifecycle

```
Created
   │
   ▼
Active
   │
   ├───────────────┐
   ▼               ▼
Updated          Deleted/Cancelled
   │               │
   ▼               ▼
Active          Archived
```

**Data Retention**

| Data | Retention | Action After Retention |
| --- | --- | --- |
| Orders | 5 tahun `[ASUMSI, untuk kebutuhan pembukuan/pajak]` | Archive (export ke storage dingin) |
| Custom Cake Requests | 2 tahun `[ASUMSI]` | Archive |
| Payment Transactions | 5 tahun `[ASUMSI, sesuai kebutuhan audit keuangan]` | Archive |
| Testimonials | Tidak ada batas (selama masih relevan) | - |
| failed_jobs (queue) | 30 hari | Delete |

## 17. Query Patterns

**Query-001**

- **Use Case:** Cek availability produk pada tanggal tertentu

```sql
SELECT pv.id, pv.size_label, pv.max_daily_quota,
       COALESCE(pa.used_quota, 0) AS used_quota
FROM product_variants pv
LEFT JOIN product_availabilities pa
  ON pa.product_variant_id = pv.id AND pa.date = ?
WHERE pv.product_id = ? AND pv.is_active = true;
```

**Required Index:** `product_availabilities(product_variant_id, date)`

**Query-002**

- **Use Case:** Daftar pesanan admin (filter status & tanggal)

```sql
SELECT *
FROM orders
WHERE status = ?
  AND pickup_date = ?
ORDER BY created_at DESC;
```

**Required Index:** `orders(status, pickup_date)`

**Query-003**

- **Use Case:** Customer melacak order via kode + nomor WA (tanpa login)

```sql
SELECT *
FROM orders
WHERE order_code = ? AND customer_whatsapp = ?;
```

**Required Index:** `orders(order_code)` (sudah unique), `orders(customer_whatsapp)`

**Query-004**

- **Use Case:** Testimoni yang dipublikasikan untuk homepage

```sql
SELECT *
FROM testimonials
WHERE is_published = true
ORDER BY created_at DESC
LIMIT 10;
```

**Required Index:** `testimonials(is_published)`

## 18. Pagination Strategy

**Default**

- `page = 1`
- `limit = 20`

**Maximum**

- `limit <= 100`

**Strategy**

- [x] Offset Pagination (bawaan Laravel `paginate()`, cukup untuk skala data UMKM ini)
- [ ] Cursor Pagination
- [ ] Keyset Pagination

**Example** `GET /admin/orders?page=1&limit=20&status=pending`

## 19. Concurrency & Locking

**Concurrency Problems**

- **Race condition:** Dua customer checkout produk & tanggal yang sama secara bersamaan, berpotensi keduanya lolos validasi kuota sebelum salah satu commit
- **Double submission:** Customer klik tombol "Bayar" dua kali (double order)
- **Duplicate transaction:** Webhook payment gateway dikirim lebih dari sekali untuk transaksi yang sama
- **Stock/kuota conflict:** Kuota harian `product_availabilities.used_quota` diperebutkan banyak request bersamaan

**Strategy**

- [x] Pessimistic Locking — `lockForUpdate()` pada baris `product_availabilities` saat checkout
- [x] Database Transaction — semua proses create order dibungkus transaction
- [x] Unique Constraint — `payment_transactions(gateway, gateway_reference)` mencegah duplikasi
- [x] Idempotency Key — gunakan `gateway_reference` sebagai idempotency key untuk webhook
- [ ] Optimistic Locking (tidak diperlukan untuk skala data ini)

**Example**

```
Customer A ──┐
             ├──▶ product_availabilities (locked row, tanggal & varian sama)
Customer B ──┘
```

Dengan `lockForUpdate()`, transaksi Customer B akan menunggu transaksi Customer A selesai (commit/rollback) sebelum membaca `used_quota`, sehingga tidak ada dua order yang lolos validasi saat kuota tersisa 1.

## 20. Database Security

**Access Control**

| Role | Database Access |
| --- | --- |
| Application (Laravel) | Read + Write (sesuai kredensial `.env`) |
| Admin Tool (mis. phpMyAdmin/TablePlus) | Limited, hanya developer terpercaya |
| Developer | Development DB (SQLite lokal) — tidak ada akses langsung ke production DB |
| Production Admin | Restricted, hanya melalui akses hosting provider (Laravel Cloud) yang di-log |

**Security Requirements**

- [x] Database tidak exposed langsung ke public internet (hanya diakses dari aplikasi via internal network)
- [x] Credentials menggunakan environment variable (`.env`), tidak hard-coded
- [ ] Encryption at rest — `[ASUMSI: tergantung fitur hosting provider, perlu dikonfirmasi]`
- [x] Encryption in transit — koneksi DB via SSL/TLS bila didukung provider
- [x] Least privilege — user aplikasi hanya punya akses ke database yang relevan, bukan akses superuser
- [x] Database audit logging — via `order_status_histories` untuk data bisnis kritikal
- [ ] Regular credential rotation — `[ASUMSI: rotasi password DB minimal setiap 6–12 bulan]`

## 21. Backup & Recovery

**Backup Strategy**

- **Backup type:** \[x\] Full, \[ \] Incremental, \[ \] Point-in-Time Recovery `[ASUMSI: tergantung fitur hosting provider]`
- **Frequency:** Harian
- **Retention:** 30 hari `[ASUMSI]`
- **Storage:** Managed oleh hosting provider (Laravel Cloud) `[ASUMSI]`
- **Encryption:** Mengikuti standar enkripsi provider hosting `[ASUMSI]`

**Recovery**

- **RPO:** ≤ 24 jam
- **RTO:** ≤ 4 jam `[ASUMSI]`
- **Recovery procedure:** Restore dari backup terakhir melalui dashboard hosting provider
- **Disaster recovery environment:** Belum ada environment DR terpisah di fase awal (skala UMKM); dipertimbangkan di Phase 2/3 jika traffic dan risiko meningkat

## 22. Migration Strategy

**Migration Naming** (mengikuti konvensi timestamp Laravel, urutan logis di bawah ini)

```
xxxx_xx_xx_000001_create_users_table          (bawaan Laravel, tambah kolom role)
xxxx_xx_xx_000010_create_products_table
xxxx_xx_xx_000011_create_product_variants_table
xxxx_xx_xx_000012_create_product_availabilities_table
xxxx_xx_xx_000020_create_orders_table
xxxx_xx_xx_000021_create_order_items_table
xxxx_xx_xx_000022_create_order_status_histories_table
xxxx_xx_xx_000023_create_payment_transactions_table
xxxx_xx_xx_000030_create_custom_cake_requests_table
xxxx_xx_xx_000040_create_testimonials_table
```

**Migration Rules**

- Migration harus versioned mengikuti timestamp Laravel
- Migration harus dapat dijalankan berurutan (`php artisan migrate`)
- Migration yang sudah dijalankan di production tidak boleh diedit — perubahan schema baru dibuat melalui migration baru (`php artisan make:migration`)
- Setiap migration harus diuji di local/staging (SQLite/MySQL) sebelum dijalankan di production

## 23. Seed Data

**Development Seed**

- Users: `admin@tbloomcake.test` (role: admin), `owner@tbloomcake.test` (role: owner)
- Products & Variants: contoh Simple Cake 10/12/15/18 cm sesuai harga di PRD, Cup Cake (isi 1/2/4/6/9/12), Bento Cake 10/12/15/18 cm
- Testimonials: 3–5 contoh testimoni dummy untuk keperluan tampilan development

**Production Seed**

- **Initial admin:** 1 akun Owner awal (dibuat manual oleh developer saat setup, bukan via seeder publik demi keamanan)
- **Initial configuration:** Harga & varian produk sesuai daftar resmi dari mitra TBloom (lihat PRD bagian 2)
- **Initial master data:** Kategori produk (`simple_cake`, `cup_cake`, `bento_cake`)

## 24. Database Environment

| Environment | Database | Purpose |
| --- | --- | --- |
| Local | SQLite (`database/database.sqlite`) | Development harian |
| Staging | MySQL (staging instance) `[ASUMSI]` | QA / Testing sebelum rilis |
| Production | MySQL (production instance, Laravel Cloud) `[ASUMSI]` | Live system |

**Database Isolation**

```
Local/Development ──▶ SQLite lokal
Staging ────────────▶ MySQL Staging
Production ─────────▶ MySQL Production
```

Production database tidak boleh diakses langsung oleh proses development (tidak ada seeding/testing langsung ke production).

## 25. Schema Versioning

**Current Version:** v1.0.0

**Change Log**

| Version | Date | Change | Migration |
| --- | --- | --- | --- |
| 1.0.0 | 2026-09-28 | Initial schema: users (role), products, product_variants, product_availabilities, orders, order_items, order_status_histories, payment_transactions, custom_cake_requests, testimonials | 000001–000040 |

## 26. Database-to-Feature Mapping

| Feature (PRD) | Tables | Operations |
| --- | --- | --- |
| F-001 Katalog Simple Cake | products, product_variants, product_availabilities | Read |
| F-002 Checkout Simple Cake | orders, order_items, product_availabilities | Create, Update |
| F-003 Form Request Custom Cake | custom_cake_requests | Create |
| F-004 Availability Harian | product_availabilities | Read, Update |
| F-005 Estimasi Ongkir | orders (kolom shipping_cost) | Update |
| F-006 Pembayaran Online | orders, payment_transactions | Create, Read, Update |
| F-007 Pembatalan & Perubahan | orders, order_status_histories, product_availabilities | Update |
| F-008 Testimoni | testimonials | Create, Read, Update, Delete |
| F-009 Admin Panel - Produk & Stok | products, product_variants, product_availabilities | CRUD |
| F-010 Admin Panel - Manajemen Pesanan | orders, order_items, order_status_histories | Read, Update |

## 27. API-to-Database Mapping

| API | Method | Tables | Operation |
| --- | --- | --- | --- |
| `/api/products/{id}/availability` | GET | product_variants, product_availabilities | READ |
| `/checkout` | POST | orders, order_items, product_availabilities | CREATE, UPDATE |
| `/custom-cake` | POST | custom_cake_requests | CREATE |
| `/api/payment/webhook` | POST | orders, payment_transactions, order_status_histories | CREATE, UPDATE |
| `/orders/{code}/cancel` | POST | orders, order_status_histories, product_availabilities | UPDATE |
| `/admin/products` | GET/POST | products, product_variants | CRUD |
| `/admin/orders` | GET | orders, order_items | READ |
| `/admin/testimonials` | GET/POST | testimonials | CRUD |

## 28. Database Acceptance Criteria

- [x] Semua entity dari PRD sudah memiliki table yang diperlukan
- [x] Semua relationship sudah didefinisikan (bagian 7)
- [x] Semua primary key sudah didefinisikan
- [x] Semua foreign key sudah didefinisikan beserta delete behavior
- [x] Semua required field memiliki constraint
- [x] Semua field memiliki data type
- [x] Unique constraint sudah ditentukan
- [x] Index strategy sudah ditentukan
- [x] Transaction strategy sudah ditentukan (checkout, payment webhook, pembatalan)
- [x] Soft delete strategy sudah ditentukan (hanya `products`)
- [x] Audit strategy sudah ditentukan (`order_status_histories`)
- [ ] Backup strategy sudah ditentukan sepenuhnya (menunggu konfirmasi provider hosting final)
- [x] Migration strategy sudah ditentukan
- [ ] Security requirement sudah ditentukan sepenuhnya (encryption at rest masih `[ASUMSI]`)
- [x] Query utama sudah diidentifikasi
- [ ] Database schema sudah diuji dengan seed data (dilakukan saat implementasi)

## 29. Final ERD

**Logical ERD**

```
┌─────────────┐
│    users    │
└─────────────┘

┌─────────────┐       1:N       ┌───────────────────┐       1:N       ┌──────────────────────────┐
│  products   │────────────────▶│ product_variants   │────────────────▶│ product_availabilities    │
└─────────────┘                 └─────────┬──────────┘                 └──────────────────────────┘
                                           │ 1:N
                                           ▼
┌─────────────┐       1:N       ┌───────────────┐
│   orders    │────────────────▶│ order_items   │
└──────┬──────┘                 └───────────────┘
       │ 1:N                    ┌──────────────────────────┐
       ├───────────────────────▶│ order_status_histories    │
       │ 1:N                    └──────────────────────────┘
       └───────────────────────▶┌──────────────────────┐
                                 │ payment_transactions  │
                                 └──────────────────────┘

┌──────────────────────┐        ┌──────────────┐
│ custom_cake_requests  │        │ testimonials │◀── created_by_user_id ── users
└──────────────────────┘        └──────────────┘
```

**Physical ERD**

```
users
  ├── id PK
  ├── name
  ├── email UNIQUE
  ├── password
  ├── role
  └── ...

products
  ├── id PK
  ├── category
  ├── name
  ├── slug UNIQUE
  └── ...

product_variants
  ├── id PK
  ├── product_id FK ──────────▶ products.id
  ├── size_label
  ├── price
  └── ...

product_availabilities
  ├── id PK
  ├── product_variant_id FK ──▶ product_variants.id
  ├── date
  ├── used_quota
  └── UNIQUE(product_variant_id, date)

orders
  ├── id PK
  ├── order_code UNIQUE
  ├── status
  ├── payment_status
  ├── total_amount
  └── ...

order_items
  ├── id PK
  ├── order_id FK ────────────▶ orders.id
  ├── product_variant_id FK ──▶ product_variants.id
  └── ...

order_status_histories
  ├── id PK
  ├── order_id FK ────────────▶ orders.id
  ├── changed_by_user_id FK ──▶ users.id
  └── ...

payment_transactions
  ├── id PK
  ├── order_id FK ────────────▶ orders.id
  ├── gateway_reference
  └── UNIQUE(gateway, gateway_reference)

custom_cake_requests
  ├── id PK
  ├── customer_whatsapp
  ├── status
  └── ...

testimonials
  ├── id PK
  ├── created_by_user_id FK ──▶ users.id
  ├── is_published
  └── ...
```

## 30. Database Documentation Checklist

```
DATABASE SCHEMA
│
├── Overview                          Selesai
│   ├── Purpose
│   ├── Technology
│   └── Architecture
│
├── Entities                          Selesai
│   ├── Entity List
│   ├── Table Definition
│   └── Relationships
│
├── Data Rules                        Selesai
│   ├── Data Types
│   ├── Constraints
│   ├── Validation
│   └── Enum
│
├── Performance                       Selesai
│   ├── Index
│   ├── Query Pattern
│   ├── Pagination
│   └── Concurrency
│
├── Security                          Sebagian (encryption at rest & rotation masih asumsi)
│   ├── Access Control
│   ├── Encryption
│   └── Audit
│
├── Reliability                       Sebagian (backup/DR menunggu konfirmasi hosting)
│   ├── Transaction
│   ├── Backup
│   ├── Recovery
│   └── Migration
│
└── Documentation                     Selesai
    ├── ERD
    ├── Feature Mapping
    ├── API Mapping
    └── Changelog
```
