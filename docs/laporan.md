# Laporan Pengembangan — Sistem Pemesanan Restoran

| | |
|---|---|
| Nama Peserta | [Nama Peserta] |
| No. Peserta | [No. Peserta] |
| Skema | Programmer (LSP) |
| Tanggal Uji | [Tanggal Uji] |

## 1. Pendahuluan dan Tujuan

Restoran perlu mencatat pelanggan, meja, reservasi, dan pesanan dengan rapi agar tidak ada meja yang dipesan dua kali pada jam yang tumpang tindih dan total pesanan selalu benar. Proyek ini membuat aplikasi web sederhana untuk kebutuhan tersebut sebagai bukti praktik demonstrasi Sertifikasi LSP Programmer.

Tujuannya:

1. Menyediakan aplikasi yang mudah dijalankan secara lokal (XAMPP/Laragon) untuk mengelola pelanggan, meja, menu, reservasi, dan pesanan dengan alur kerja yang masuk akal.
2. Menunjukkan penerapan pemrograman terstruktur, OOP, library pre-existing, akses basis data, debugging, dan pengujian unit.

## 2. Kebutuhan Aplikasi

**Kebutuhan fungsional**

| No | Kebutuhan |
|---|---|
| F1 | Dashboard menampilkan total pelanggan, meja, reservasi, pesanan; reservasi hari ini; pesanan diproses; dan pendapatan hari ini. |
| F2 | Kelola pelanggan (nama, telepon, email opsional): lihat, cari, tambah, ubah, hapus. |
| F3 | Kelola meja (nomor, kapasitas, status Aktif/Nonaktif): lihat, cari, tambah, ubah, hapus. |
| F4 | Kelola menu (nama, harga, tersedia/tidak): lihat, cari, tambah, ubah, hapus. |
| F5 | Reservasi: buat, lihat (filter Hari Ini/Akan Datang/Semua + status), ubah, dan ubah status sesuai alur Menunggu → Dikonfirmasi → Selesai, atau Dibatalkan. |
| F6 | Validasi reservasi: jam operasional 10:00–22:00 dengan durasi 2 jam, tidak boleh di waktu yang sudah lewat, jumlah tamu ≤ kapasitas meja, meja harus Aktif, dan tidak boleh tumpang tindih dengan reservasi aktif lain di meja yang sama. |
| F7 | Pesanan dengan satu atau lebih menu. Subtotal dan total dihitung otomatis. Status: Diproses → Selesai atau Dibatalkan (final). |
| F8 | Harga di detail pesanan mengikuti harga menu saat pesanan dibuat. Pesanan dari reservasi hanya untuk reservasi yang tamunya sudah datang, dan tanggalnya mengikuti tanggal reservasi. |
| F9 | Laporan reservasi dan pesanan per rentang tanggal, bisa dicetak lewat browser. |

**Kebutuhan non-fungsional**: antarmuka berbahasa Indonesia dan responsif (Bootstrap 5 + Bootstrap Icons), validasi di server (validasi di browser hanya membantu), prepared statement PDO, escaping output (XSS), pesan berhasil/gagal setelah setiap aksi, data berelasi tidak bisa dihapus, dan pesanan disimpan dalam transaksi.

## 3. Teknologi yang Digunakan

| Teknologi | Kegunaan |
|---|---|
| PHP Native 8.1+ (diuji dengan PHP 8.3.6 CLI) | Bahasa pemrograman sisi server |
| MySQL / MariaDB (diuji dengan MariaDB 10.11.14) | Basis data relasional |
| PDO (`pdo_mysql`) | Koneksi database, prepared statement, transaksi |
| Bootstrap 5.3.3 dan Bootstrap Icons 1.11.3 (CDN) | Komponen antarmuka: navbar, card, tabel, form, alert, badge, nav pills, ikon |
| HTML, CSS, JavaScript seperlunya | Struktur halaman, tema warna, gaya cetak (`@media print`), interaksi form |
| PHP CLI | Menjalankan unit test |

## 4. Perancangan Singkat dan Struktur Database

### Arsitektur

Aplikasi memakai pola sederhana dengan satu titik masuk:

```text
Browser ──> index.php?page=xxx ──> pages/xxx.php ──> classes/*.php ──> PDO ──> MySQL
                │                       │                  │
                │                       ├─ includes/header, navbar, footer (tampilan)
                │                       └─ assets/css/style.css, assets/js/app.js
                └─ whitelist halaman     └─ includes/functions.php (validasi & perhitungan)
```

- `index.php` memuat konfigurasi, fungsi, dan class, mengatur zona waktu Asia/Jakarta, lalu memanggil halaman dari daftar yang diizinkan (*whitelist*).
- Setiap halaman memproses POST lebih dulu, memanggil method class, menyimpan pesan flash, lalu mengalihkan halaman (pola Post/Redirect/Get) agar form tidak terkirim ulang saat halaman di-refresh.
- Class model berisi query database dan aturan yang membutuhkan data. Fungsi di `includes/functions.php` bersifat murni (tanpa database) sehingga bisa diuji lewat CLI.

### Alur Status

```text
Reservasi:  Menunggu ──► Dikonfirmasi ──► Selesai
                │              │
                └──────────────┴──► Dibatalkan   (Selesai & Dibatalkan = final)

Pesanan:    Diproses ──► Selesai
                └──────► Dibatalkan             (Selesai & Dibatalkan = final)
```

Alur ini disimpan sebagai konstanta `RESERVATION_TRANSITIONS` dan `ORDER_TRANSITIONS`, lalu diperiksa dengan fungsi `canChangeStatus()`.

### Struktur Tabel

| Tabel | Kolom | Relasi |
|---|---|---|
| `customers` | id (PK), name, phone, email (NULL), created_at | — |
| `restaurant_tables` | id (PK), table_number (UNIQUE), capacity, status ENUM('Aktif','Nonaktif') | — |
| `menu_items` | id (PK), name, price DECIMAL(12,2), is_available TINYINT(1) | — |
| `reservations` | id (PK), customer_id, table_id, reservation_date DATE, reservation_time TIME, guest_count, status ENUM, notes (NULL), created_at | FK → customers, restaurant_tables (RESTRICT) |
| `restaurant_orders` | id (PK), customer_id, reservation_id (NULL), order_date DATE, status ENUM, total_amount DECIMAL(12,2), created_at | FK → customers, reservations (RESTRICT) |
| `order_items` | id (PK), order_id, menu_item_id, menu_name, price DECIMAL(12,2), quantity, subtotal DECIMAL(12,2) | FK → restaurant_orders (CASCADE), menu_items (RESTRICT) |

```text
customers 1──* reservations *──1 restaurant_tables
    │               │
    1               0..1
    *               *
restaurant_orders 1──* order_items *──1 menu_items
```

Catatan desain:

- Harga dan total disimpan sebagai `DECIMAL(12,2)`, bukan teks atau float, agar perhitungan uang tepat.
- `order_items` menyimpan salinan `menu_name` dan `price`. Jika harga menu berubah, pesanan lama tetap memakai harga saat pesanan dibuat.
- Foreign key `ON DELETE RESTRICT` mencegah data induk yang masih dipakai ikut terhapus. Index `(table_id, reservation_date, reservation_time)` mempercepat pengecekan jadwal bentrok.
- Data contoh di `database.sql` memakai tanggal relatif (`CURDATE()`), sehingga dashboard langsung berisi reservasi dan pesanan hari ini saat demo.

## 5. Implementasi Fitur

| Fitur | File | Keterangan |
|---|---|---|
| Dashboard | `pages/dashboard.php` | Kartu total data, reservasi hari ini, pesanan diproses, pendapatan hari ini, dan tombol cepat. |
| Pelanggan | `pages/customers.php`, `classes/Customer.php` | Form tambah/ubah, pencarian nama/telepon, jumlah reservasi & pesanan per pelanggan. Hapus ditolak jika pelanggan punya reservasi atau pesanan. |
| Meja | `pages/tables.php`, `classes/RestaurantTable.php` | Nomor meja unik. Menampilkan jumlah reservasi aktif mendatang. Hapus ditolak jika meja punya reservasi; nonaktifkan ditolak jika masih ada reservasi aktif mendatang. |
| Menu | `pages/menu.php`, `classes/MenuItem.php` | Harga harus > 0. Hapus ditolak jika menu sudah pernah dipesan; pengguna diarahkan mengubah status menjadi Tidak Tersedia. |
| Reservasi | `pages/reservations.php`, `classes/Reservation.php` | Halaman daftar (filter + tombol aksi sesuai status: Konfirmasi, Selesai, Pesanan, Ubah, Batalkan) dan halaman form terpisah. `Reservation::create()`/`update()` memeriksa format, jam operasional, waktu lewat, pelanggan, meja Aktif, kapasitas, dan tumpang tindih. `changeStatus()` menegakkan alur status, menolak "Selesai" sebelum tanggalnya, dan menolak pembatalan jika sudah ada pesanan. Di form, JavaScript menonaktifkan meja yang kapasitasnya kurang. |
| Pesanan | `pages/orders.php`, `classes/Order.php` | Form dengan tombol −/+ dan pratinjau subtotal/total. Pilihan reservasi difilter sesuai pelanggan; memilih reservasi mengunci tanggal. Bisa dibuka langsung dari tombol "Pesanan" di daftar reservasi. `Order::create()` mengambil harga dari database, memvalidasi reservasi, lalu menyimpan pesanan dan item dalam satu transaksi. Detail menampilkan meja & jam reservasi dan hanya tombol status yang sah. |
| Laporan | `pages/reports.php` | Filter rentang tanggal dan pintasan periode. Total nilai pesanan tidak menghitung pesanan Dibatalkan. Tombol Cetak memanggil `window.print()`; navbar, form, dan tombol disembunyikan saat dicetak. |

## 6. Penerapan Pemrograman Terstruktur, OOP, Library, dan Database

### Pemrograman Terstruktur (J.620100.017.02)

Logika yang dipakai berulang dipisah menjadi fungsi kecil di `includes/functions.php`:

- Perhitungan: `calculateSubtotal()`, `calculateOrderTotal()`, `buildOrderItems()`.
- Validasi dasar: `validatePrice()`, `validateQuantity()` (1–100), `validateGuestCount()`, `isValidDate()`, `isValidTime()`, `isValidPhone()`, `isValidEmail()`, `validateOrderDate()`.
- Aturan waktu reservasi: `timeToMinutes()`, `isWithinOperatingHours()`, `latestReservationStart()`, `isPastDateTime()` (waktu "sekarang" dikirim sebagai parameter agar bisa diuji).
- Aturan bisnis: `hasReservationConflict()` (tumpang tindih slot 2 jam) dan `canChangeStatus()` (alur status).
- Validasi per entitas: `validateCustomerData()`, `validateTableData()`, `validateMenuData()`, `validateReservationData()`, `validateDateRange()`. Semuanya mengembalikan daftar pesan error.
- Helper tampilan: `e()` (escape HTML), `formatRupiah()`, `statusBadge()`, `pageHeader()`, `emptyState()`, `flashResult()`, `showErrors()`, `actionButton()`.

```php
function hasReservationConflict(array $existing, int $tableId, string $date, string $time, ?int $excludeId = null): bool
{
    $start = timeToMinutes(normalizeTime($time));
    foreach ($existing as $reservation) {
        if ($excludeId !== null && (int) $reservation['id'] === $excludeId) {
            continue;
        }
        if ((int) $reservation['table_id'] === $tableId
            && $reservation['reservation_date'] === $date
            && in_array($reservation['status'], ACTIVE_RESERVATION_STATUSES, true)
            && abs(timeToMinutes(normalizeTime($reservation['reservation_time'])) - $start) < RESERVATION_DURATION_MINUTES) {
            return true;
        }
    }
    return false;
}
```

### OOP (J.620100.018.02)

- **Abstraksi dan pewarisan**: `abstract class BaseModel` menyimpan koneksi PDO (`protected PDO $db`) dan method umum `count()`, `find()`, `countRelated()`, `isConstraintError()`. Class `Customer`, `RestaurantTable`, `MenuItem`, `Reservation`, dan `Order` mewarisinya (`extends BaseModel`).
- **Enkapsulasi**: property `$db`, `$table` bersifat `protected`; property `$now` dan method internal seperti `Reservation::persist()`, `onTableAndDate()`, `countActiveOrders()` bersifat `private`.
- **Tanggung jawab jelas**: `Reservation` menegakkan aturan jadwal dan alur status; `Order` menyimpan pesanan beserta item dalam transaksi.
- **Dependency injection**: koneksi PDO diberikan lewat constructor (`new Customer($db)`); `Reservation` dan `Order` juga dapat menerima waktu "sekarang" (`new Reservation($db, $now)`); `Order::create()` menerima objek `MenuItem`.

### Library / Komponen Pre-existing (J.620100.019.02)

Bootstrap 5.3.3 dan Bootstrap Icons 1.11.3 dimuat dari CDN. Komponen yang dipakai: navbar responsif (collapse), grid, card, table, form, input group, nav pills, alert (dismissible, otomatis hilang untuk pesan sukses), badge status, button, dan ikon. PDO adalah komponen bawaan PHP untuk akses database.

### Akses Basis Data (J.620100.021.02)

- Koneksi PDO dibuat sekali (`getConnection()`) dengan `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES = false`, dan charset `utf8mb4`.
- Semua query yang menerima input memakai prepared statement (`prepare()` + `execute([...])`), termasuk pencarian `LIKE`. Tidak ada input pengguna yang digabung langsung ke SQL.
- Transaksi di `Order::create()`:

```php
$this->db->beginTransaction();
// INSERT restaurant_orders ... lalu INSERT setiap order_items
$this->db->commit();
// jika PDOException: $this->db->rollBack(); pesan umum ke pengguna, detail ke error_log()
```

- Kesalahan database ditangkap di `index.php`. Pengguna hanya melihat pesan umum, sedangkan detail dan password tidak pernah ditampilkan.

### Penulisan Kode dengan Guidelines (J.620100.016.01)

Penamaan konsisten (tabel/kolom `snake_case`, fungsi/method `camelCase`, class `PascalCase`, konstanta `UPPER_CASE`), aturan operasional dalam konstanta yang mudah diubah, validasi di server, `htmlspecialchars` untuk setiap output, pola Post/Redirect/Get, whitelist halaman, aksi hapus/ubah status lewat POST, CSS dan JavaScript dipisah ke folder `assets/`, serta pemblokiran akses langsung ke file halaman (`pages/`) dan template tampilan (`includes/header.php`, `navbar.php`, `footer.php`).

## 7. Debugging yang Dilakukan (J.620100.025.02)

Berikut masalah yang benar-benar ditemui selama pengembangan dan pengujian.

### Debugging 1 — Uji end-to-end gagal pada detail pesanan

- **Gejala**: skrip uji HTTP (curl) melaporkan 46 OK dan 2 FAIL. Pemeriksaan "Order total 85.000" dan "Item subtotal 50.000" gagal, padahal pesan "Pesanan berhasil disimpan" muncul.
- **Analisis**: halaman detail pesanan dibuka langsung dan data di tabel `restaurant_orders`/`order_items` diperiksa. Total Rp 85.000 dan subtotal Rp 50.000 tersimpan dan tampil dengan benar, jadi aplikasi tidak salah. Masalahnya ada pada skrip uji: perintah `curl -X POST -L` tetap memakai metode **POST** saat mengikuti redirect 302. Akibatnya halaman tujuan menerima POST kosong dan menampilkan form, bukan halaman detail. Browser sungguhan memakai GET setelah redirect 302.
- **Perbaikan**: opsi `-X POST` dihapus (curl otomatis memakai POST karena ada `-d` dan beralih ke GET saat redirect), lalu database di-reset dengan `database.sql`.
- **Verifikasi**: skrip uji dijalankan ulang dengan hasil **48 OK, 0 FAIL** (versi awal aplikasi).

### Debugging 2 — README.md tersimpan dalam encoding UTF-16

- **Gejala**: setelah README ditulis ulang, perintah `grep` tidak menemukan teks yang jelas ada di file, dan perintah `file README.md` mengenali file sebagai data biner.
- **Penyebab**: README bawaan repositori berencoding UTF-16 LE dengan akhir baris CRLF, dan editor mempertahankan encoding lama saat file ditimpa. Pemeriksaan dengan `od -c` menunjukkan byte `\0` di antara setiap karakter.
- **Perbaikan**: file dikonversi dengan `iconv -f UTF-16LE -t UTF-8` dan akhir baris diubah ke LF.
- **Verifikasi**: `file README.md` menghasilkan `Unicode text, UTF-8 text`, dan `grep` kembali menemukan teks.

### Debugging 3 — Mengantisipasi perbedaan format waktu (diverifikasi dengan unit test)

- **Risiko**: input `<input type="time">` mengirim waktu dalam format `HH:MM` (misalnya `19:00`), sedangkan MySQL mengembalikan kolom `TIME` dalam format `HH:MM:SS` (`19:00:00`). Jika keduanya dibandingkan langsung, reservasi yang bentrok tidak akan terdeteksi.
- **Penanganan**: sejak perancangan dibuat fungsi `normalizeTime()`, yang dipakai di `hasReservationConflict()` dan saat menyimpan reservasi. Unit test "Meja, tanggal, dan waktu sama (format HH:MM dari form) ditolak" juga ditambahkan.
- **Verifikasi**: pada versi awal (41 pengujian), normalisasi waktu sengaja dihapus pada salinan sementara. Unit test langsung mendeteksinya: `[FAIL] Meja, tanggal, dan waktu sama (format HH:MM dari form) ditolak`, hasil `40 PASS, 1 FAIL`, exit code `1`. Pada kode asli semua pengujian PASS.

### Debugging 4 — Unit test lama gagal setelah aturan bentrok diubah menjadi slot 2 jam

- **Gejala**: setelah `hasReservationConflict()` diubah dari "jam persis sama" menjadi "tumpang tindih dalam 2 jam", unit test lama dijalankan dan menghasilkan `[FAIL] Waktu berbeda pada meja sama diterima` (`40 PASS, 1 FAIL`, exit code 1).
- **Penyebab**: test lama mengharapkan reservasi 20:00 diterima walaupun sudah ada reservasi 19:00 di meja yang sama. Dengan aturan baru, 20:00 masih berada di dalam slot 19:00–21:00, jadi penolakan justru perilaku yang benar. Yang perlu diperbarui adalah harapan test-nya, bukan kodenya.
- **Perbaikan**: test lama diganti dengan kasus batas yang lebih jelas: 19:00 vs 20:30 dan 17:30 vs 19:00 harus ditolak, sedangkan 19:00 vs 21:00 dan 17:00 vs 19:00 (tepat bersambung) harus diterima.
- **Verifikasi**: semua 68 pengujian PASS. Sebagai pembuktian, aturan bentrok sengaja dikembalikan ke "jam persis sama" pada salinan sementara: dua test tumpang tindih langsung gagal (`66 PASS, 2 FAIL`, exit code 1).

### Debugging 5 — Halaman melebar ke samping di layar ponsel

- **Gejala**: pada screenshot lebar 390 px, halaman Reservasi dan Pelanggan bisa digeser ke samping (lebar dokumen 597 px dan 469 px), padahal tabel sudah dibungkus `.table-responsive`.
- **Analisis**: dengan skrip Playwright diukur lebar setiap elemen. Kontainer `.table-responsive` sudah benar (lebar 366 px, isi bisa di-scroll). Penyebabnya adalah teks tersembunyi `.visually-hidden` pada tombol ikon di kolom Aksi. Elemen itu berposisi `absolute`, dan karena `.table-responsive` tidak berposisi `relative`, elemen tersebut "lolos" dari area scroll tabel dan ikut melebarkan halaman.
- **Perbaikan**: menambahkan `.table-responsive { position: relative; }` di `assets/css/style.css`.
- **Verifikasi**: pengukuran ulang di 9 halaman menunjukkan lebar dokumen tepat 390 px; tabel yang lebar tetap bisa di-scroll di dalam kartunya.

## 8. Hasil Pengujian Unit (J.620100.033.02)

### Unit test (`php tests/test_functions.php`)

Dijalankan pada PHP 8.3.6 CLI. Exit code: **0**.

| Kelompok | Jumlah |
|---|---|
| Perhitungan subtotal dan total pesanan | 11 |
| Validasi jumlah tamu terhadap kapasitas meja | 5 |
| Validasi harga dan kuantitas | 13 |
| Penolakan reservasi bentrok (slot 2 jam) | 10 |
| Jam operasional dan waktu reservasi | 11 |
| Alur status reservasi dan pesanan | 11 |
| Validasi data lain | 7 |
| **Total** | **68 PASS, 0 FAIL** |

```text
== Perhitungan subtotal dan total pesanan ==
[PASS] Subtotal 25.000 x 2 = 50.000
[PASS] Subtotal menerima input string ("15000", "3") = 45.000
[PASS] Subtotal harga desimal 12.500,50 x 3 = 37.501,50
[PASS] Total 3 item (50.000 + 20.000 + 15.000) = 85.000
[PASS] Total pesanan tanpa item = 0
[PASS] buildOrderItems mengabaikan kuantitas 0
[PASS] buildOrderItems memakai harga dari data menu
[PASS] buildOrderItems menghitung subtotal item
[PASS] buildOrderItems menolak jika tidak ada menu dipilih
[PASS] buildOrderItems menolak kuantitas negatif
[PASS] buildOrderItems menolak menu yang tidak tersedia

== Validasi jumlah tamu terhadap kapasitas meja ==
[PASS] 4 tamu pada meja kapasitas 4 diterima
[PASS] 2 tamu pada meja kapasitas 6 diterima
[PASS] 5 tamu pada meja kapasitas 4 ditolak
[PASS] 0 tamu ditolak
[PASS] Jumlah tamu negatif ditolak

== Validasi harga dan kuantitas ==
[PASS] Harga 15000 valid
[PASS] Harga desimal 12500.50 valid
[PASS] Harga 0 ditolak
[PASS] Harga negatif ditolak
[PASS] Harga bukan angka ditolak
[PASS] Harga kosong ditolak
[PASS] Kuantitas 3 valid
[PASS] Kuantitas 0 ditolak
[PASS] Kuantitas negatif ditolak
[PASS] Kuantitas desimal 1.5 ditolak
[PASS] Kuantitas bukan angka ditolak
[PASS] Kuantitas 100 (batas maksimal) valid
[PASS] Kuantitas 101 (melebihi batas) ditolak

== Penolakan reservasi bentrok (slot 2 jam) ==
[PASS] Meja, tanggal, dan waktu sama (format HH:MM dari form) ditolak
[PASS] Bentrok dengan reservasi Dikonfirmasi ditolak
[PASS] Reservasi lama berstatus Dibatalkan tidak dianggap bentrok
[PASS] Meja berbeda pada waktu sama diterima
[PASS] Jam tumpang tindih (19:00 vs 20:30) pada meja sama ditolak
[PASS] Jam tumpang tindih sebelum reservasi lain (17:30 vs 19:00) ditolak
[PASS] Tepat setelah slot berakhir (19:00 vs 21:00) diterima
[PASS] Tepat sebelum slot dimulai (17:00 vs 19:00) diterima
[PASS] Tanggal berbeda pada meja sama diterima
[PASS] Mengubah reservasi itu sendiri tidak dianggap bentrok

== Jam operasional dan waktu reservasi ==
[PASS] Jam mulai terakhir adalah 20:00 (tutup 22:00 - durasi 2 jam)
[PASS] Reservasi 10:00 (jam buka) diterima
[PASS] Reservasi 20:00 (selesai tepat 22:00) diterima
[PASS] Reservasi 09:30 (sebelum buka) ditolak
[PASS] Reservasi 20:30 (melewati jam tutup) ditolak
[PASS] Jam yang sudah lewat hari ini (12:00) terdeteksi
[PASS] Jam yang belum lewat hari ini (19:00) tidak dianggap lewat
[PASS] Tanggal kemarin terdeteksi sudah lewat
[PASS] Data reservasi lengkap dan valid diterima
[PASS] Reservasi di waktu yang sudah lewat ditolak
[PASS] Reservasi di luar jam operasional (23:00) ditolak

== Alur status reservasi dan pesanan ==
[PASS] Reservasi Menunggu -> Dikonfirmasi diizinkan
[PASS] Reservasi Dikonfirmasi -> Selesai diizinkan
[PASS] Reservasi Menunggu -> Selesai (melompati konfirmasi) ditolak
[PASS] Reservasi Dibatalkan -> Dikonfirmasi ditolak
[PASS] Reservasi Selesai -> Dibatalkan ditolak
[PASS] Pesanan Diproses -> Selesai diizinkan
[PASS] Pesanan Diproses -> Dibatalkan diizinkan
[PASS] Pesanan Selesai -> Diproses ditolak
[PASS] Pesanan Dibatalkan -> Selesai ditolak
[PASS] Tanggal pesanan hari ini diterima
[PASS] Tanggal pesanan besok ditolak

== Validasi data lain ==
[PASS] Data pelanggan valid tanpa email diterima
[PASS] Email pelanggan tidak valid ditolak
[PASS] Nama pelanggan kosong ditolak
[PASS] Tanggal 2026-02-30 ditolak
[PASS] Waktu 25:00 ditolak
[PASS] Rentang tanggal terbalik ditolak
[PASS] Escape HTML mencegah tag script

Hasil: 68 PASS, 0 FAIL dari 68 pengujian.
```

Pemeriksaan bahwa runner benar-benar mendeteksi kegagalan: pada salinan sementara, `calculateSubtotal()` sengaja dirusak (perkalian diganti penjumlahan). Hasilnya `63 PASS, 5 FAIL dari 68 pengujian` dengan exit code **1**.

### Pengujian end-to-end (tambahan)

Dilakukan di lingkungan pengembangan dengan MariaDB 10.11.14 dan server bawaan PHP (`php -S`). Database diimpor dari `database.sql`, lalu skenario HTTP diuji dengan curl. Versi awal aplikasi lulus 48/48 skenario. Setelah perbaikan alur reservasi dan pesanan, skrip diperbarui menjadi **61 skenario** dengan hasil **61 OK, 0 FAIL**, tanpa warning/notice PHP di log server. Skenario yang diuji antara lain:

- Sepuluh halaman/tampilan (termasuk form reservasi dan form pesanan) merespons HTTP 200. Dashboard menampilkan pendapatan hari ini Rp 120.000 dari data contoh.
- Reservasi: berhasil dibuat dengan status Menunggu; tumpang tindih 2 jam (19:00 vs 18:00) ditolak; slot yang bersambung (17:00, berakhir 19:00) diterima; tanggal yang sudah lewat, jam 09:00, dan jam 21:00 ditolak; tamu melebihi kapasitas dan meja Nonaktif ditolak; form tetap terisi setelah gagal; mengubah reservasi sendiri tidak dianggap bentrok; form tidak lagi memiliki pilihan status.
- Alur status reservasi: Konfirmasi berhasil; lompat dari Menunggu ke Selesai ditolak; Selesai sebelum tanggal reservasi ditolak; membatalkan reservasi yang sudah punya pesanan ditolak; mengubah reservasi Selesai ditolak; mengganti pelanggan pada reservasi berpesanan ditolak; membatalkan dua kali ditolak; filter Hari Ini dan filter status bekerja.
- Pesanan: form bisa diisi otomatis dari reservasi; tanggal pesanan dipaksa sama dengan tanggal reservasi walaupun form mengirim tanggal lain; detail menampilkan meja reservasi; total Rp 65.000 benar. Ditolak bila: reservasi masih besok, reservasi berstatus Menunggu, reservasi milik pelanggan lain, tanggal pesanan di masa depan, jumlah 101, menu tidak tersedia, atau tanpa item.
- Alur status pesanan: Diproses → Selesai berhasil dan tombol ubah status hilang; Selesai → Diproses ditolak; pesanan dapat dibatalkan, setelah itu reservasinya boleh dibatalkan; filter status pesanan bekerja.
- Data master dan laporan: menonaktifkan meja yang punya reservasi mendatang ditolak; pencarian pelanggan bekerja; hapus pelanggan berelasi ditolak; nama berisi `<b>` tampil ter-escape; laporan hari ini menampilkan total Rp 120.000 (pesanan Dibatalkan tidak dihitung); rentang tanggal terbalik ditolak.

**Uji rollback transaksi**: trigger sementara pada `order_items` dipasang untuk memaksa INSERT gagal, lalu `Order::create()` dipanggil. Hasil: pengguna menerima pesan "Pesanan gagal disimpan. Silakan coba lagi.", dan jumlah baris `restaurant_orders` tetap sama sebelum dan sesudah (**ROLLBACK OK**). Catatan: setelah aturan "tanggal pesanan tidak boleh melebihi hari ini" ditambahkan, skrip uji lama sempat ditolak oleh validasi sebelum transaksi dimulai (sehingga belum benar-benar menguji rollback). Skrip diperbaiki agar memakai tanggal hari ini, lalu dijalankan ulang dengan hasil di atas. Trigger dihapus setelah pengujian.

**Pemeriksaan tampilan**: halaman dashboard, daftar dan form reservasi, form pesanan, detail pesanan, pelanggan, laporan, serta tampilan ponsel (390 px) diambil screenshot-nya dengan Chromium headless. Interaksi JavaScript diperiksa: dengan 5 tamu, meja M01 dan M02 otomatis tidak dapat dipilih; memilih pelanggan hanya menampilkan reservasinya; memilih reservasi mengunci tanggal; pratinjau total menunjukkan Rp 75.000 untuk 2 × Rp 30.000 + 3 × Rp 5.000.

> Pengujian di XAMPP/Laragon pada Windows belum dilakukan di lingkungan pengembangan ini. Langkah verifikasi manualnya ada di README (bagian Instalasi).

## 9. Kesimpulan

Aplikasi Sistem Pemesanan Restoran sudah memenuhi kebutuhan pada PRD dan alur kerjanya sudah dirapikan: reservasi mengikuti jam operasional, slot 2 jam, dan alur status yang jelas; pesanan hanya bisa dikaitkan dengan reservasi yang tamunya sudah datang, tersimpan dalam transaksi dengan total yang benar, dan harga terkunci saat pesanan dibuat; laporan dapat difilter per tanggal serta dicetak.

Proyek ini menunjukkan penerapan pemrograman terstruktur (fungsi validasi, perhitungan, dan aturan waktu), OOP (`BaseModel` dan class turunannya), library Bootstrap 5, akses database dengan PDO (prepared statement, relasi, dan transaksi), debugging yang terdokumentasi, serta unit test yang dapat dijalankan ulang dengan hasil 68 PASS dan 0 FAIL.

Pengembangan lanjutan yang mungkin dilakukan jika aplikasi dipakai di luar demonstrasi: login dan hak akses, proteksi CSRF, edit item pesanan, serta paginasi data.

### Pemetaan Unit Kompetensi

| Kode | Bukti dalam proyek |
|---|---|
| J.620100.016.01 | Penamaan konsisten, konstanta aturan, validasi server, penanganan error (`index.php`, class), prepared statement, dan `e()` untuk escaping output. |
| J.620100.017.02 | `includes/functions.php`: fungsi validasi, perhitungan, aturan waktu, pengecekan bentrok, dan alur status. |
| J.620100.018.02 | `classes/`: `BaseModel` (abstract) beserta `Customer`, `RestaurantTable`, `MenuItem`, `Reservation`, `Order`. |
| J.620100.019.02 | Bootstrap 5.3.3 dan Bootstrap Icons (CDN) di seluruh halaman. |
| J.620100.021.02 | `database.sql` (PK, FK, DECIMAL), `config/database.php` (PDO), transaksi di `Order::create()`. |
| J.620100.023.02 | `README.md`, komentar seperlunya, `docs/laporan.md`, `docs/presentasi.md`, panduan DOCX/PPT. |
| J.620100.025.02 | Bagian 7 laporan ini (5 contoh debugging). |
| J.620100.033.02 | `tests/test_functions.php` (68 pengujian, PASS/FAIL, exit code). |
