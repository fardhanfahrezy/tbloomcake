# PRD - Website TBloom Cake

## 1. Product Overview

- **Nama produk:** Website TBloom Cake
- **Jenis website:** E-commerce sederhana untuk penjualan cake (Simple Cake) + form request custom order (Custom Cake) + company profile & testimoni
- **Tujuan:** Memberikan channel pemesanan online yang jelas untuk Simple Cake, dan mengurangi miss komunikasi pada proses request Custom Cake dengan form terstruktur yang datanya langsung diteruskan ke admin
- **Problem:**
  - Sering terjadi miss komunikasi saat customer request custom cake (detail desain, ukuran, tulisan, dsb tidak tercatat rapi via chat WhatsApp biasa)
  - Revisi custom cake yang datang mendadak dari customer, menyulitkan proses produksi
  - Belum ada sistem availability/kuota harian yang transparan ke customer untuk Simple Cake

## 2. Target Users

- **Customer:** Pembeli individu yang ingin memesan Simple Cake langsung (checkout online) atau mengajukan request Custom Cake (form → diteruskan ke WhatsApp admin)
- **Admin:** Staf yang mengelola pesanan harian, konfirmasi pembayaran, update status pesanan, balas request custom cake via WhatsApp
- **Owner:** Pemilik usaha (mitra TBloom) — mengelola produk, harga, kuota, testimoni, dan memantau laporan pesanan secara keseluruhan

## 3. User Roles & Permissions

### Guest

- Melihat katalog Simple Cake beserta harga & availability
- Melihat halaman Custom Cake (informasi + form request)
- Melihat testimoni

### Customer

- Checkout Simple Cake (pilih ukuran, rasa, filling, warna, tulisan, jumlah, tanggal & jam pengambilan/pengiriman, metode pengambilan)
- Melakukan pembayaran online (pelunasan penuh untuk pemesanan via website)
- Melihat status pesanan sendiri
- Mengajukan pembatalan/perubahan pesanan (maks. H-2)
- Mengajukan request Custom Cake via form

### Admin

- Kelola produk (tambah/edit/hapus, harga, ukuran, rasa, filling, foto, status aktif/nonaktif)
- Kelola stok/availability harian (kuota per produk per tanggal)
- Kelola pesanan (lihat detail, ubah status pesanan, ubah status pembayaran)
- Kelola testimoni (tambah/edit/hapus/publikasikan)
- Menerima notifikasi request Custom Cake baru
- Approve/reject request pembatalan atau perubahan pesanan

### Owner

- Semua akses Admin
- Akses laporan pesanan & ringkasan penjualan
- Mengelola akun Admin lain

## 4. Goals

- Goal 1: Customer dapat memesan Simple Cake secara mandiri end-to-end (pilih produk → checkout → bayar) tanpa perlu chat admin
- Goal 2: Mengurangi miss komunikasi custom order dengan form terstruktur (semua detail tercatat & tersimpan sebagai data, bukan sekadar chat)
- Goal 3: Availability/kuota harian tampil real-time sehingga customer tidak salah pesan pada tanggal yang sudah penuh
- Goal 4: Admin memiliki satu panel terpusat untuk memantau seluruh pesanan, status pembayaran, dan permintaan custom

## 5. Scope

### In Scope

- Katalog & checkout online untuk Simple Cake (termasuk Cup Cake & Bento Cake sebagai varian produk sederhana)
- Sistem availability/kuota harian per produk
- Form request Custom Cake (data terstruktur, dikirim/diteruskan ke WhatsApp admin)
- Sistem pembayaran online (payment gateway) untuk pelunasan Simple Cake
- Estimasi biaya pengiriman berbasis area/jarak
- Manajemen pembatalan & perubahan pesanan (aturan H-2, refund 80%)
- Halaman testimoni (dikelola admin)
- Admin panel: produk, stok/availability, pesanan, testimoni
- Identitas visual sesuai brand guideline TBloom (warna, logo)

### Out of Scope

- Penjualan Dessert (sistem keuangan terpisah dari cake)
- Penjualan Minuman dan Pastry
- Kalkulasi otomatis harga Custom Cake (harga final tetap ditentukan admin manual via WhatsApp)
- COD (Cash on Delivery)
- Fitur loyalty program / membership (tidak ada membership)

## 6. Features

### F-001 Katalog Simple Cake

- **Deskripsi:** Menampilkan daftar Simple Cake beserta harga per ukuran dan status availability per tanggal
- **Actor:** Guest, Customer
- **Input:** Filter tanggal, kategori produk
- **Process:** Sistem menampilkan produk beserta sisa kuota (mis. "3/5 tersedia") berdasarkan tanggal yang dipilih
- **Output:** Daftar produk dengan status availability
- **Business Rules:** Maksimal 5 pesanan per produk per hari; produk yang penuh ditandai tidak bisa dipesan untuk tanggal tsb
- **Acceptance Criteria:** Customer tidak bisa checkout produk yang kuotanya sudah 5/5 pada tanggal terpilih
- **Priority:** Must Have

### F-002 Checkout Simple Cake

- **Deskripsi:** Proses pemesanan Simple Cake oleh customer
- **Actor:** Customer
- **Input:** Ukuran, rasa, filling, warna cover, tulisan (maks. 10 kata), jumlah, tanggal & jam pengambilan/pengiriman, metode (pickup/delivery), alamat & nomor penerima (jika delivery)
- **Process:** Validasi kuota tersedia → validasi minimal H-1 → hitung total harga (+ ongkir jika delivery) → lanjut ke pembayaran
- **Output:** Order tersimpan dengan status "Menunggu Pembayaran"
- **Business Rules:** Minimal order H-1; tulisan maks. 10 kata; waktu pengambilan tersedia sampai 21.00 WIB
- **Acceptance Criteria:** Order tidak bisa dibuat untuk tanggal yang kurang dari H-1 atau kuota penuh
- **Priority:** Must Have

### F-003 Form Request Custom Cake

- **Deskripsi:** Form pengajuan custom cake yang datanya diteruskan ke admin (WhatsApp/panel admin), bukan checkout langsung
- **Actor:** Guest, Customer
- **Input:** Nama, no. WhatsApp, tanggal pengambilan, ukuran, rasa, filling, warna, tulisan, upload gambar referensi desain, permintaan fondant, catatan tambahan
- **Process:** Validasi minimal H-3 (H-7 jika ada permintaan print edible) → simpan data request → kirim notifikasi ke admin (WhatsApp/dashboard) → tampilkan pesan konfirmasi ke customer
- **Output:** Request tersimpan dengan status "Menunggu Konfirmasi Admin"
- **Business Rules:** Minimal H-3; H-7 untuk print; perubahan desain maksimal H-2; harga final ditentukan admin, bukan otomatis
- **Acceptance Criteria:** Semua field wajib terisi sebelum submit; data tersimpan lengkap dan dapat dilihat admin di panel
- **Priority:** Must Have

### F-004 Sistem Availability/Kuota Harian

- **Deskripsi:** Menampilkan dan mengontrol kuota pesanan per produk per hari
- **Actor:** Admin (kelola), Customer (lihat)
- **Input:** Tanggal, produk
- **Process:** Sistem menghitung jumlah order yang sudah masuk untuk tanggal & produk tsb, dibandingkan dengan batas maksimal (default 5)
- **Output:** Status availability (tersedia/penuh) per produk per tanggal
- **Business Rules:** Maksimal 5 pesanan per produk per hari (admin dapat mengubah batas ini)
- **Acceptance Criteria:** Kuota otomatis berkurang saat ada order baru yang statusnya valid (belum dibatalkan)
- **Priority:** Must Have

### F-005 Estimasi Biaya Pengiriman

- **Deskripsi:** Menghitung/menampilkan estimasi ongkos kirim berdasarkan area/jarak dan ukuran cake
- **Actor:** Customer
- **Input:** Alamat/lokasi penerima, ukuran cake
- **Process:** Sistem menghitung estimasi berdasarkan zona area (mis. Area Barat, Area Timur/Cirebon) dan ukuran (kendaraan roda dua vs mobil/GoCar)
- **Output:** Estimasi biaya ongkir ditampilkan sebelum pembayaran
- **Business Rules:** Cake besar (lebih besar dari lunch box) wajib menggunakan mobil; biaya final dapat disesuaikan admin
- **Acceptance Criteria:** Customer melihat estimasi ongkir sebelum konfirmasi pembayaran
- **Priority:** Should Have

### F-006 Pembayaran Online (Payment Gateway)

- **Deskripsi:** Pelunasan pembayaran Simple Cake melalui payment gateway
- **Actor:** Customer
- **Input:** Metode pembayaran (transfer bank, QRIS, BJB, ShopeePay, dll)
- **Process:** Redirect/integrasi ke payment gateway → sistem menerima callback status pembayaran → update status order
- **Output:** Order berstatus "Dibayar" atau "Menunggu Pembayaran"
- **Business Rules:** Tidak ada COD; pembayaran via website harus lunas (tidak ada skema DP untuk order via website)
- **Acceptance Criteria:** Status order otomatis berubah setelah pembayaran terkonfirmasi dari gateway
- **Priority:** Must Have

### F-007 Pembatalan & Perubahan Pesanan

- **Deskripsi:** Customer dapat mengajukan pembatalan/perubahan pesanan
- **Actor:** Customer, Admin
- **Input:** Order ID, jenis permintaan (batal/ubah)
- **Process:** Sistem cek apakah masih dalam batas H-2 → jika ya, permintaan diteruskan ke admin untuk diproses → jika dibatalkan, hitung refund 80%
- **Output:** Status order berubah menjadi "Dibatalkan" atau "Diubah"; refund diproses manual/otomatis oleh admin
- **Business Rules:** Maksimal H-2 sebelum tanggal pengambilan; refund 80% dari total pembayaran
- **Acceptance Criteria:** Permintaan pembatalan/perubahan setelah H-2 otomatis ditolak sistem dengan pesan jelas
- **Priority:** Must Have

### F-008 Testimoni Customer

- **Deskripsi:** Menampilkan testimoni yang dikurasi admin
- **Actor:** Admin (kelola), Guest/Customer (lihat)
- **Input:** Nama, foto (opsional), isi testimoni, rating (opsional)
- **Process:** Admin menambah/edit/hapus/publikasikan testimoni
- **Output:** Testimoni tampil di halaman utama/khusus
- **Business Rules:** Hanya testimoni berstatus "dipublikasikan" yang tampil ke publik
- **Acceptance Criteria:** Testimoni yang belum dipublikasikan tidak muncul di halaman publik
- **Priority:** Should Have

### F-009 Admin Panel — Manajemen Produk & Stok

- **Deskripsi:** Panel untuk mengelola produk dan availability
- **Actor:** Admin, Owner
- **Input:** Data produk (nama, harga, ukuran, rasa, filling, foto, status), kuota harian
- **Process:** CRUD produk; atur kuota harian per produk
- **Output:** Data produk & stok ter-update di katalog
- **Business Rules:** Produk nonaktif tidak tampil di katalog customer
- **Acceptance Criteria:** Perubahan harga/stok langsung tercermin di halaman customer
- **Priority:** Must Have

### F-010 Admin Panel — Manajemen Pesanan

- **Deskripsi:** Panel untuk melihat & mengelola seluruh pesanan (Simple Cake & request Custom Cake)
- **Actor:** Admin, Owner
- **Input:** Filter status, tanggal, jenis pesanan
- **Process:** Admin melihat detail pesanan, update status pesanan & pembayaran
- **Output:** Data pesanan ter-update, riwayat status tercatat
- **Business Rules:** Setiap perubahan status tercatat dengan timestamp untuk audit trail
- **Acceptance Criteria:** Admin dapat menemukan pesanan tertentu dan mengubah statusnya dalam \<3 klik
- **Priority:** Must Have

## 7. User Flow

**Flow Simple Cake:** Landing Page → Pilih Kategori (Simple Cake) → Pilih Produk & Tanggal → Cek Availability → Isi Detail Pesanan (rasa, warna, tulisan, dll) → Pilih Pickup/Delivery → (jika delivery: isi alamat → lihat estimasi ongkir) → Ringkasan Order → Pembayaran (Payment Gateway) → Konfirmasi Order → (opsional) Ajukan Pembatalan/Perubahan sebelum H-2

**Flow Custom Cake:** Landing Page → Halaman Custom Cake → Isi Form Request (data + upload referensi) → Submit → Notifikasi ke Admin (WhatsApp/Dashboard) → Admin Follow-up via WhatsApp (nego harga & desain) → Kesepakatan Harga & DP 50% → Produksi

**Flow Admin:** Login Admin → Dashboard (ringkasan pesanan hari ini + request custom baru) → Kelola Pesanan/Produk/Testimoni sesuai kebutuhan

## 8. Business Rules

- Simple Cake: minimal order H-1, maksimal 5 order per produk per hari, tulisan maks. 10 kata
- Custom Cake: minimal order H-3, print edible minimal H-7, perubahan desain maks. H-2
- Fondant: tidak menerima pesanan mendadak; harga di luar harga cake (estimasi Rp50.000–Rp70.000/karakter)
- Jam operasional toko: 09.00–17.00 WIB; pengambilan cake dapat dilakukan sampai 21.00 WIB
- Tidak ada COD; order via admin butuh DP minimal 50%; order via website harus lunas
- Pembatalan/perubahan maksimal H-2; refund 80% dari total pembayaran jika dibatalkan sesuai ketentuan
- Desain sederhana (masih kategori Simple Cake) tidak dikenakan biaya tambahan; desain rumit diarahkan ke Custom Cake/WhatsApp

## 9. Data Requirements

- **Produk:** nama, kategori (Simple/Custom/Cup Cake/Bento Cake), ukuran, harga, rasa, filling, warna cover, foto, status aktif
- **Availability:** produk, tanggal, kuota maksimal, jumlah terpakai
- **Order (Simple Cake):** customer info, detail produk terpilih, tulisan, jumlah, tanggal & jam ambil/kirim, metode pengambilan, alamat (jika delivery), status pembayaran, status pesanan, riwayat perubahan status
- **Request Custom Cake:** nama, no. WhatsApp, tanggal ambil, detail desain (ukuran, rasa, filling, warna, tulisan, fondant, catatan), file gambar referensi, status request
- **Testimoni:** nama, isi, foto (opsional), rating (opsional), status publikasi
- **User Admin/Owner:** nama, email, role, kredensial login

## 10. UI/UX Requirements

- Desain simple, elegan, mengikuti identitas visual TBloom (warna: Dark Brown #251306, Burgundy Brown #512323, Brown #442818, Taupe #726251, Beige #C3B59C, Soft Pink #F7E3DF)
- Logo TBloom (format PNG) digunakan sebagai identitas utama di header/footer
- Gambar produk beresolusi tinggi; elemen grafis dekoratif sebaiknya SVG bila memungkinkan
- Tampilan availability harus jelas dan mudah dipahami (contoh: badge "3/5 tersedia" / "Penuh")
- Form Custom Cake harus terasa sederhana meski banyak field — gunakan multi-step form untuk mengurangi kelelahan input
- Mobile-first, karena mayoritas customer kemungkinan mengakses via HP

## 11. Non-Functional Requirements

### Performance

- Halaman katalog & checkout harus load dalam \<3 detik pada koneksi 4G standar
- Cek availability harus real-time/near real-time (tidak boleh terjadi double-booking kuota)

### Security

- Validasi input di sisi server untuk semua form (checkout & custom request)
- Payment callback dari gateway harus diverifikasi signature/token untuk mencegah spoofing
- Admin panel dilindungi autentikasi & role-based access control

### Accessibility

- Kontras warna teks terhadap background brand (terutama Beige/Soft Pink) perlu dicek agar tetap readable
- Alt text pada gambar produk

### Responsive

- Wajib fully responsive: mobile, tablet, desktop

## 12. Technical Requirements

- **Framework:** Laravel 13 (PHP 8.3/8.4) — sudah menjadi basis proyek saat ini
- **Frontend:** Blade + Tailwind CSS 4 (Vite sebagai bundler) `[sesuai stack project berjalan]`
- **Database:** MySQL/MariaDB untuk production; SQLite dapat dipakai untuk development/testing
- **Authentication:** Laravel built-in auth (session-based) untuk Admin/Owner; guest checkout untuk Customer
- **Payment Gateway:** `[ASUMSI: Midtrans atau Xendit — perlu konfirmasi mitra]`, mendukung transfer bank, QRIS, ShopeePay, BJB
- **Notifikasi WhatsApp:** `[ASUMSI: integrasi API WhatsApp (mis. Fonnte/Wablas) untuk notifikasi request custom cake ke admin — jika tidak tersedia, notifikasi ditampilkan di dashboard admin saja]`
- **Deployment:** Laravel Cloud `[sesuai boost.json project — cloud: true]`

## 13. Acceptance Criteria

- Customer dapat menyelesaikan pemesanan Simple Cake dari pilih produk sampai pembayaran berhasil tanpa bantuan admin
- Sistem menolak pemesanan pada produk/tanggal yang kuotanya sudah penuh
- Form Custom Cake berhasil tersimpan lengkap dengan file referensi, dan admin menerima notifikasi
- Admin dapat mengubah status pesanan dan status pembayaran melalui panel
- Testimoni yang dipublikasikan tampil di halaman publik; yang tidak, tidak tampil
- Pembatalan setelah H-2 ditolak otomatis oleh sistem dengan pesan yang jelas ke customer

## 14. Requirement Matrix

| ID | Fitur | Priority | Role Terkait |
| --- | --- | --- | --- |
| F-001 | Katalog Simple Cake | Must Have | Guest, Customer |
| F-002 | Checkout Simple Cake | Must Have | Customer |
| F-003 | Form Request Custom Cake | Must Have | Guest, Customer, Admin |
| F-004 | Sistem Availability Harian | Must Have | Admin, Customer |
| F-005 | Estimasi Biaya Pengiriman | Should Have | Customer |
| F-006 | Pembayaran Online | Must Have | Customer |
| F-007 | Pembatalan & Perubahan Pesanan | Must Have | Customer, Admin |
| F-008 | Testimoni Customer | Should Have | Admin, Guest |
| F-009 | Admin Panel - Produk & Stok | Must Have | Admin, Owner |
| F-010 | Admin Panel - Manajemen Pesanan | Must Have | Admin, Owner |

## 15. Future Development

- Dashboard laporan penjualan & analitik untuk Owner
- Sistem loyalty/membership untuk pelanggan tetap
- Integrasi otomatis WhatsApp untuk update status pesanan ke customer
- Kalkulator estimasi harga fondant otomatis berdasarkan jumlah karakter
- Ekspansi ke penjualan Dessert, Minuman, dan Pastry secara online (bila diputuskan masuk scope)
- Sistem tracking pengiriman real-time (integrasi API GoCar/kurir)
- Multi-cabang/multi-lokasi bila usaha berkembang
