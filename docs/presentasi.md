# Outline Presentasi — Sistem Pemesanan Restoran

Outline 8 slide yang bisa dipindahkan ke PowerPoint. Setiap slide berisi poin utama dan catatan pembicara.

---

## Slide 1 — Judul dan Identitas Peserta

**Sistem Pemesanan Restoran**
Aplikasi Web PHP Native & MySQL

- Nama: [Nama Peserta]
- No. Peserta: [No. Peserta]
- Skema: Programmer (LSP)
- Tanggal: [Tanggal Uji]

> Catatan: perkenalkan diri dan judul proyek secara singkat.

---

## Slide 2 — Latar Belakang dan Tujuan

**Latar belakang**
- Pencatatan reservasi dan pesanan secara manual rawan salah: meja terpesan ganda, total pesanan salah hitung.
- Restoran butuh laporan reservasi dan pesanan per periode.

**Tujuan**
- Membuat aplikasi web sederhana untuk mencatat pelanggan, meja, menu, reservasi, dan pesanan.
- Menunjukkan kompetensi pemrograman sesuai unit kompetensi LSP Programmer.

---

## Slide 3 — Fitur Aplikasi

- **Dashboard**: ringkasan data, reservasi hari ini, pesanan diproses, pendapatan hari ini
- **Pelanggan, Meja, Menu**: tambah, ubah, hapus, cari (data berelasi dilindungi); pelanggan baru bisa langsung ditambahkan dari form reservasi/pesanan
- **Reservasi**: alur Menunggu → Dikonfirmasi → Selesai; jam buka 10:00–22:00, slot 2 jam, cek kapasitas & tumpang tindih
- **Pesanan**: dibuat dari reservasi yang tamunya sudah datang, pratinjau total, harga terkunci saat pesanan dibuat
- **Laporan**: filter rentang tanggal dan cetak lewat browser

---

## Slide 4 — Teknologi dan Database

**Teknologi**: PHP Native 8.1+, MySQL/MariaDB, PDO, Bootstrap 5 (CDN), XAMPP/Laragon

**Database `restoran_db`** (6 tabel):

```text
customers 1──* reservations *──1 restaurant_tables
customers 1──* restaurant_orders 1──* order_items *──1 menu_items
reservations 1──* restaurant_orders (opsional)
```

- Harga dan total memakai `DECIMAL(12,2)`
- Foreign key `RESTRICT` mencegah relasi rusak
- Pesanan dan item disimpan dalam **transaksi**

---

## Slide 5 — Contoh Pemrograman Terstruktur dan OOP

**Terstruktur** (`includes/functions.php`)

```php
function calculateSubtotal($price, $quantity): float
{
    return round((float) $price * (int) $quantity, 2);
}

function validateGuestCount($guestCount, $capacity): bool
{
    return isPositiveInteger($guestCount) && (int) $guestCount <= (int) $capacity;
}
```

**OOP** (`classes/`)

```text
abstract BaseModel  (PDO $db, count(), find())
 ├── Customer         ├── MenuItem
 ├── RestaurantTable  ├── Reservation  (cek kapasitas & bentrok)
 └── Order            (create() dengan transaksi)
```

> Catatan: jelaskan pewarisan, enkapsulasi (`protected`/`private`), dan prepared statement.

---

## Slide 6 — Demo Aplikasi

Skenario demo (± 5 menit), dengan data contoh hasil impor ulang `database.sql`:

1. Dashboard: tunjukkan reservasi hari ini, pesanan diproses, dan pendapatan hari ini.
2. Tambah pelanggan baru, lalu coba simpan dengan email yang salah → **ditolak** (validasi server).
3. Buat Reservasi dengan **Pelanggan baru** (isi nama & telepon), isi 5 tamu → meja M01 dan M02 otomatis tidak bisa dipilih; pilih M03 besok pukul 19:00 → **berhasil** dengan status Menunggu.
4. Buat reservasi lain di M03 besok pukul 20:00 → **ditolak** karena tumpang tindih dengan slot 19:00–21:00.
5. Di daftar Hari Ini, klik **Konfirmasi** pada reservasi Andi Wijaya, lalu klik **Pesanan**: pelanggan, reservasi, dan tanggal terisi otomatis. Isi beberapa menu dan tunjukkan pratinjau total → Simpan.
6. Di detail pesanan klik **Tandai Selesai** → status final. Coba **Batalkan** reservasi yang punya pesanan → **ditolak**.
7. Coba hapus pelanggan yang punya pesanan → **ditolak** dengan pesan jelas.
8. Buka Laporan → **Hari Ini** → klik **Cetak**.

---

## Slide 7 — Hasil Pengujian

**Unit test**: `php tests/test_functions.php`
- **68 PASS, 0 FAIL** (exit code 0)
- Mencakup: subtotal/total pesanan, jumlah tamu vs kapasitas, harga/kuantitas, reservasi bentrok (slot 2 jam), jam operasional & waktu lewat, serta alur status reservasi dan pesanan
- Jika ada pengujian gagal, exit code menjadi 1 (sudah diverifikasi)

**Uji end-to-end** (MariaDB + server PHP): **74 dari 74 skenario OK**
**Uji rollback transaksi**: pesanan gagal tidak tersimpan setengah

**Debugging (5 kasus)**: metode redirect pada skrip uji, README berencoding UTF-16, format waktu `HH:MM` vs `HH:MM:SS`, test lama gagal setelah aturan slot 2 jam, dan halaman melebar di layar ponsel

---

## Slide 8 — Kesimpulan

- Semua fitur utama di PRD sudah berjalan dan teruji.
- Kompetensi yang ditunjukkan: guidelines & best practices, pemrograman terstruktur, OOP, library Bootstrap, akses basis data (PDO dan transaksi), dokumentasi, debugging, dan unit test.
- Pengembangan lanjutan: login dan hak akses, proteksi CSRF, edit item pesanan, dan paginasi.

**Terima kasih — Sesi tanya jawab**
