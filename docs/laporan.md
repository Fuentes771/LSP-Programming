# Laporan Pengembangan — Sistem Pemesanan Restoran

| | |
|---|---|
| Nama Peserta | [Nama Peserta] |
| No. Peserta | [No. Peserta] |
| Skema | Programmer (LSP) |
| Tanggal Uji | [Tanggal Uji] |

## 1. Pendahuluan dan Tujuan

Restoran perlu mencatat pelanggan, meja, reservasi, dan pesanan dengan rapi agar tidak ada meja yang dipesan dua kali dan total pesanan selalu benar. Proyek ini membuat aplikasi web sederhana untuk kebutuhan tersebut sebagai bukti praktik demonstrasi Sertifikasi LSP Programmer.

Tujuannya:

1. Menyediakan aplikasi yang mudah dijalankan secara lokal (XAMPP/Laragon) untuk mengelola pelanggan, meja, menu, reservasi, dan pesanan.
2. Menunjukkan penerapan pemrograman terstruktur, OOP, library pre-existing, akses basis data, debugging, dan pengujian unit.

## 2. Kebutuhan Aplikasi

**Kebutuhan fungsional**

| No | Kebutuhan |
|---|---|
| F1 | Dashboard menampilkan jumlah pelanggan, meja, reservasi, pesanan, dan reservasi terbaru. |
| F2 | Kelola pelanggan (nama, telepon, email opsional): lihat, tambah, ubah, hapus. |
| F3 | Kelola meja (nomor, kapasitas, status Aktif/Nonaktif): lihat, tambah, ubah, hapus. |
| F4 | Kelola menu (nama, harga, tersedia/tidak): lihat, tambah, ubah, hapus. |
| F5 | Reservasi: buat, lihat, ubah, batalkan. Status: Menunggu, Dikonfirmasi, Selesai, Dibatalkan. |
| F6 | Validasi reservasi: jumlah tamu ≤ kapasitas meja, tidak boleh ada reservasi aktif yang bentrok (meja, tanggal, dan jam sama). |
| F7 | Pesanan dengan satu atau lebih menu. Subtotal dan total dihitung otomatis. Status: Diproses, Selesai, Dibatalkan. |
| F8 | Harga di detail pesanan mengikuti harga menu saat pesanan dibuat. |
| F9 | Laporan reservasi dan pesanan per rentang tanggal, bisa dicetak lewat browser. |

**Kebutuhan non-fungsional**: antarmuka berbahasa Indonesia dan responsif (Bootstrap 5), validasi di server, prepared statement PDO, escaping output (XSS), pesan berhasil/gagal setelah setiap aksi, data berelasi tidak bisa dihapus, dan pesanan disimpan dalam transaksi.

## 3. Teknologi yang Digunakan

| Teknologi | Kegunaan |
|---|---|
| PHP Native 8.1+ (diuji dengan PHP 8.3.6 CLI) | Bahasa pemrograman sisi server |
| MySQL / MariaDB (diuji dengan MariaDB 10.11.14) | Basis data relasional |
| PDO (`pdo_mysql`) | Koneksi database, prepared statement, transaksi |
| Bootstrap 5.3.3 (CDN) | Komponen antarmuka: navbar, card, tabel, form, alert, badge |
| HTML & CSS | Struktur halaman dan gaya cetak (`@media print`) |
| PHP CLI | Menjalankan unit test |

## 4. Perancangan Singkat dan Struktur Database

### Arsitektur

Aplikasi memakai pola sederhana dengan satu titik masuk:

```text
Browser ──> index.php?page=xxx ──> pages/xxx.php ──> classes/*.php ──> PDO ──> MySQL
                │                       │                  │
                │                       └─ includes/header, navbar, footer (tampilan)
                └─ whitelist halaman     └─ includes/functions.php (validasi & perhitungan)
```

- `index.php` memuat konfigurasi, fungsi, dan class, lalu memanggil halaman dari daftar yang diizinkan (*whitelist*).
- Setiap halaman memproses POST lebih dulu, memanggil method class, menyimpan pesan flash, lalu mengalihkan halaman (pola Post/Redirect/Get) agar form tidak terkirim ulang saat halaman di-refresh.
- Class model berisi query database. Fungsi di `includes/functions.php` bersifat murni (tanpa database) sehingga bisa diuji lewat CLI.

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

## 5. Implementasi Fitur

| Fitur | File | Keterangan |
|---|---|---|
| Dashboard | `pages/dashboard.php` | 4 kartu jumlah data (`BaseModel::count()`) dan 5 reservasi terbaru (`Reservation::latest()`). |
| Pelanggan | `pages/customers.php`, `classes/Customer.php` | Form tambah/ubah dan tabel. Hapus ditolak jika pelanggan punya reservasi atau pesanan. |
| Meja | `pages/tables.php`, `classes/RestaurantTable.php` | Nomor meja unik (pelanggaran UNIQUE ditangkap dan diubah menjadi pesan ramah). Hapus ditolak jika meja punya reservasi. |
| Menu | `pages/menu.php`, `classes/MenuItem.php` | Harga harus > 0. Hapus ditolak jika menu sudah pernah dipesan; pengguna diarahkan untuk mengubah status menjadi Tidak Tersedia. |
| Reservasi | `pages/reservations.php`, `classes/Reservation.php` | `Reservation::save()` memeriksa format input, keberadaan pelanggan, meja Aktif, kapasitas, dan jadwal bentrok. Membatalkan reservasi mengubah status menjadi Dibatalkan, bukan menghapus data. |
| Pesanan | `pages/orders.php`, `classes/Order.php` | Form menampilkan semua menu tersedia dengan input jumlah. `Order::create()` mengambil harga dari database (bukan dari form), menghitung subtotal dan total, lalu menyimpan pesanan dan item dalam satu transaksi. Halaman detail menampilkan item dan form ubah status. |
| Laporan | `pages/reports.php` | Filter rentang tanggal (default: awal bulan sampai hari ini). Total nilai pesanan tidak menghitung pesanan Dibatalkan. Tombol Cetak memanggil `window.print()`; navbar, form, dan tombol disembunyikan saat dicetak. |

## 6. Penerapan Pemrograman Terstruktur, OOP, Library, dan Database

### Pemrograman Terstruktur (J.620100.017.02)

Logika yang dipakai berulang dipisah menjadi fungsi kecil di `includes/functions.php`:

- Perhitungan: `calculateSubtotal()`, `calculateOrderTotal()`, `buildOrderItems()`.
- Validasi dasar: `validatePrice()`, `validateQuantity()`, `validateGuestCount()`, `isValidDate()`, `isValidTime()`, `isValidPhone()`, `isValidEmail()`.
- Aturan bisnis: `hasReservationConflict()`, yang menerima daftar reservasi sebagai parameter sehingga bisa diuji tanpa database.
- Validasi per entitas: `validateCustomerData()`, `validateTableData()`, `validateMenuData()`, `validateReservationData()`, `validateDateRange()`. Semuanya mengembalikan daftar pesan error.
- Helper tampilan: `e()` (escape HTML), `formatRupiah()`, `setFlash()`/`getFlash()`, `showErrors()`, `actionButton()`.

```php
function calculateOrderTotal(array $items): float
{
    $total = 0.0;
    foreach ($items as $item) {
        $total += calculateSubtotal($item['price'], $item['quantity']);
    }
    return round($total, 2);
}
```

### OOP (J.620100.018.02)

- **Abstraksi dan pewarisan**: `abstract class BaseModel` menyimpan koneksi PDO (`protected PDO $db`) dan method umum `count()`, `find()`, `countRelated()`, `isConstraintError()`. Class `Customer`, `RestaurantTable`, `MenuItem`, `Reservation`, dan `Order` mewarisinya (`extends BaseModel`).
- **Enkapsulasi**: property `$db` dan `$table` bersifat `protected`. Query internal seperti `Reservation::onTableAndDate()` bersifat `private`.
- **Tanggung jawab jelas**: `Customer` mengelola pelanggan, `Reservation` menegakkan aturan kapasitas dan jadwal bentrok, dan `Order` menyimpan pesanan beserta item dalam transaksi.
- **Dependency injection**: koneksi PDO diberikan lewat constructor (`new Customer($db)`), dan `Order::create()` menerima objek `MenuItem`.

### Library / Komponen Pre-existing (J.620100.019.02)

Bootstrap 5.3.3 dimuat dari CDN. Komponen yang dipakai: navbar responsif (collapse), grid, card, table, form, alert (dismissible), badge status, dan button. PDO adalah komponen bawaan PHP untuk akses database.

### Akses Basis Data (J.620100.021.02)

- Koneksi PDO dibuat sekali (`getConnection()`) dengan `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES = false`, dan charset `utf8mb4`.
- Semua query yang menerima input memakai prepared statement (`prepare()` + `execute([...])`). Tidak ada input pengguna yang digabung langsung ke SQL.
- Transaksi di `Order::create()`:

```php
$this->db->beginTransaction();
// INSERT restaurant_orders ... lalu INSERT setiap order_items
$this->db->commit();
// jika PDOException: $this->db->rollBack(); pesan umum ke pengguna, detail ke error_log()
```

- Kesalahan database ditangkap di `index.php`. Pengguna hanya melihat pesan umum, sedangkan detail dan password tidak pernah ditampilkan.

### Penulisan Kode dengan Guidelines (J.620100.016.01)

Penamaan konsisten (tabel/kolom `snake_case`, fungsi/method `camelCase`, class `PascalCase`), validasi di server, `htmlspecialchars` untuk setiap output, pola Post/Redirect/Get, whitelist halaman, aksi hapus/batal lewat POST, dan pemblokiran akses langsung ke file halaman (`pages/`) serta template tampilan (`includes/header.php`, `navbar.php`, `footer.php`).

## 7. Debugging yang Dilakukan (J.620100.025.02)

Berikut masalah yang benar-benar ditemui selama pengembangan dan pengujian.

### Debugging 1 — Uji end-to-end gagal pada detail pesanan

- **Gejala**: skrip uji HTTP (curl) melaporkan 46 OK dan 2 FAIL. Pemeriksaan "Order total 85.000" dan "Item subtotal 50.000" gagal, padahal pesan "Pesanan berhasil disimpan" muncul.
- **Analisis**: halaman detail pesanan #1 dibuka langsung dan data di tabel `restaurant_orders`/`order_items` diperiksa. Total Rp 85.000 dan subtotal Rp 50.000 tersimpan dan tampil dengan benar, jadi aplikasi tidak salah. Masalahnya ada pada skrip uji: perintah `curl -X POST -L` tetap memakai metode **POST** saat mengikuti redirect 302. Akibatnya halaman tujuan menerima POST kosong dan menampilkan form "Buat Pesanan" (beserta pesan flash), bukan halaman detail. Browser sungguhan memakai GET setelah redirect 302.
- **Perbaikan**: opsi `-X POST` dihapus (curl otomatis memakai POST karena ada `-d` dan beralih ke GET saat redirect), lalu database di-reset dengan `database.sql`.
- **Verifikasi**: skrip uji dijalankan ulang dengan hasil **48 OK, 0 FAIL**.

### Debugging 2 — README.md tersimpan dalam encoding UTF-16

- **Gejala**: setelah README ditulis ulang, perintah `grep` tidak menemukan teks yang jelas ada di file, dan perintah `file README.md` mengenali file sebagai data biner.
- **Penyebab**: README bawaan repositori berencoding UTF-16 LE dengan akhir baris CRLF, dan editor mempertahankan encoding lama saat file ditimpa. Pemeriksaan dengan `od -c` menunjukkan byte `\0` di antara setiap karakter.
- **Perbaikan**: file dikonversi dengan `iconv -f UTF-16LE -t UTF-8` dan akhir baris diubah ke LF.
- **Verifikasi**: `file README.md` menghasilkan `Unicode text, UTF-8 text`, dan `grep` kembali menemukan teks.

### Debugging 3 — Mengantisipasi perbedaan format waktu (diverifikasi dengan unit test)

- **Risiko**: input `<input type="time">` mengirim waktu dalam format `HH:MM` (misalnya `19:00`), sedangkan MySQL mengembalikan kolom `TIME` dalam format `HH:MM:SS` (`19:00:00`). Jika keduanya dibandingkan langsung, reservasi yang bentrok tidak akan terdeteksi.
- **Penanganan**: sejak perancangan dibuat fungsi `normalizeTime()`, yang dipakai di `hasReservationConflict()` dan saat menyimpan reservasi. Unit test "Meja, tanggal, dan waktu sama (format HH:MM dari form) ditolak" juga ditambahkan.
- **Verifikasi**: pada salinan sementara, normalisasi waktu sengaja dihapus. Unit test langsung mendeteksinya: `[FAIL] Meja, tanggal, dan waktu sama (format HH:MM dari form) ditolak`, hasil `40 PASS, 1 FAIL`, exit code `1`. Pada kode asli semua pengujian PASS.

## 8. Hasil Pengujian Unit (J.620100.033.02)

### Unit test (`php tests/test_functions.php`)

Dijalankan pada PHP 8.3.6 CLI. Exit code: **0**.

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

== Penolakan reservasi bentrok ==
[PASS] Meja, tanggal, dan waktu sama (format HH:MM dari form) ditolak
[PASS] Bentrok dengan reservasi Dikonfirmasi ditolak
[PASS] Reservasi lama berstatus Dibatalkan tidak dianggap bentrok
[PASS] Meja berbeda pada waktu sama diterima
[PASS] Waktu berbeda pada meja sama diterima
[PASS] Tanggal berbeda pada meja sama diterima
[PASS] Mengubah reservasi itu sendiri tidak dianggap bentrok

== Validasi data lain ==
[PASS] Data pelanggan valid tanpa email diterima
[PASS] Email pelanggan tidak valid ditolak
[PASS] Nama pelanggan kosong ditolak
[PASS] Tanggal 2026-02-30 ditolak
[PASS] Waktu 25:00 ditolak
[PASS] Rentang tanggal terbalik ditolak
[PASS] Escape HTML mencegah tag script

Hasil: 41 PASS, 0 FAIL dari 41 pengujian.
```

Pemeriksaan bahwa runner benar-benar mendeteksi kegagalan: pada salinan sementara, `calculateSubtotal()` sengaja dirusak (perkalian diganti penjumlahan). Hasilnya `36 PASS, 5 FAIL dari 41 pengujian` dengan exit code **1**.

### Pengujian end-to-end (tambahan)

Dilakukan di lingkungan pengembangan dengan MariaDB 10.11.14 dan server bawaan PHP (`php -S`). Database diimpor dari `database.sql`, lalu 48 skenario HTTP diuji dengan curl. Hasil: **48 OK, 0 FAIL**, tanpa warning/notice PHP di log server. Skenario yang diuji antara lain:

- Ketujuh halaman merespons HTTP 200.
- Tambah/ubah/hapus pelanggan, meja, dan menu. Validasi menolak nama kosong, email salah, kapasitas 0, harga negatif, dan nomor meja duplikat. Nama berisi `<b>` ditampilkan ter-escape.
- Reservasi: bentrok ditolak, tamu melebihi kapasitas ditolak, meja Nonaktif ditolak, mengubah reservasi sendiri tidak dianggap bentrok, pembatalan berhasil, dan slot reservasi yang sudah dibatalkan dapat dipakai lagi.
- Pesanan dengan 3 item: total Rp 85.000 dan subtotal benar. Ditolak jika: reservasi milik pelanggan lain, menu tidak tersedia, tanpa item, atau kuantitas negatif. Status pesanan bisa diubah.
- Setelah harga menu diubah dari Rp 25.000 ke Rp 27.000, detail pesanan lama tetap menampilkan Rp 25.000.
- Hapus pelanggan, meja, atau menu yang masih berelasi ditolak dengan pesan yang jelas.
- Laporan periode Oktober 2026 menampilkan total Rp 85.000 (pesanan Dibatalkan Rp 30.000 tidak dihitung). Rentang tanggal terbalik ditolak, dan periode kosong menampilkan pesan.
- Parameter `?page=` yang tidak dikenal kembali ke dashboard, dan akses langsung ke `pages/*.php` ditolak.

**Uji rollback transaksi**: trigger sementara pada `order_items` dipasang untuk memaksa INSERT gagal, lalu `Order::create()` dipanggil. Hasil: pengguna menerima pesan "Pesanan gagal disimpan. Silakan coba lagi.", dan jumlah baris `restaurant_orders` tetap 2 sebelum dan sesudah (**ROLLBACK OK**). Trigger dihapus setelah pengujian.

**Pemeriksaan tampilan**: halaman dashboard, reservasi, buat pesanan, laporan (termasuk mode cetak), dan pelanggan pada lebar layar ponsel (390 px) diambil screenshot-nya dengan Chromium headless. Tata letak Bootstrap tampil rapi dan responsif, dan elemen navigasi/form tersembunyi pada mode cetak.

> Pengujian di XAMPP/Laragon pada Windows belum dilakukan di lingkungan pengembangan ini. Langkah verifikasi manualnya ada di README (bagian Instalasi).

## 9. Kesimpulan

Aplikasi Sistem Pemesanan Restoran sudah memenuhi kebutuhan pada PRD: data pelanggan, meja, dan menu dapat dikelola; reservasi tervalidasi terhadap kapasitas dan jadwal bentrok; pesanan multi-item tersimpan dalam transaksi dengan total yang benar dan harga yang terkunci saat pesanan dibuat; dan laporan dapat difilter per tanggal serta dicetak.

Proyek ini menunjukkan penerapan pemrograman terstruktur (fungsi validasi dan perhitungan), OOP (`BaseModel` dan class turunannya), library Bootstrap 5, akses database dengan PDO (prepared statement, relasi, dan transaksi), debugging yang terdokumentasi, serta unit test yang dapat dijalankan ulang dengan hasil 41 PASS dan 0 FAIL.

Pengembangan lanjutan yang mungkin dilakukan jika aplikasi dipakai di luar demonstrasi: login dan hak akses, proteksi CSRF, serta pencarian dan paginasi data.

### Pemetaan Unit Kompetensi

| Kode | Bukti dalam proyek |
|---|---|
| J.620100.016.01 | Penamaan konsisten, validasi server, penanganan error (`index.php`, class), prepared statement, dan `e()` untuk escaping output. |
| J.620100.017.02 | `includes/functions.php`: fungsi validasi, perhitungan, dan pengecekan bentrok. |
| J.620100.018.02 | `classes/`: `BaseModel` (abstract) beserta `Customer`, `RestaurantTable`, `MenuItem`, `Reservation`, `Order`. |
| J.620100.019.02 | Bootstrap 5.3.3 (CDN) di `includes/header.php` dan seluruh halaman. |
| J.620100.021.02 | `database.sql` (PK, FK, DECIMAL), `config/database.php` (PDO), transaksi di `Order::create()`. |
| J.620100.023.02 | `README.md`, komentar seperlunya, `docs/laporan.md`, `docs/presentasi.md`. |
| J.620100.025.02 | Bagian 7 laporan ini (3 contoh debugging). |
| J.620100.033.02 | `tests/test_functions.php` (41 pengujian, PASS/FAIL, exit code). |
