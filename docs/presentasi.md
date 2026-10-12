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

- **Dashboard**: ringkasan jumlah data dan reservasi terbaru
- **Pelanggan, Meja, Menu**: tambah, ubah, hapus (data berelasi dilindungi)
- **Reservasi**: validasi kapasitas meja dan jadwal bentrok, bisa dibatalkan
- **Pesanan**: beberapa menu per pesanan, subtotal dan total otomatis, harga terkunci saat pesanan dibuat
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

Skenario demo (± 5 menit):

1. Dashboard: tunjukkan ringkasan data.
2. Tambah pelanggan baru, lalu coba simpan dengan email yang salah (validasi server).
3. Buat reservasi meja M02 (kapasitas 4) untuk 5 tamu → **ditolak**; ubah menjadi 4 tamu → berhasil.
4. Buat reservasi lain di meja, tanggal, dan jam yang sama → **ditolak (bentrok)**.
5. Buat pesanan 3 menu dan tunjukkan subtotal serta total otomatis.
6. Coba hapus pelanggan yang punya pesanan → **ditolak** dengan pesan jelas.
7. Buka laporan, filter tanggal, lalu klik **Cetak**.

---

## Slide 7 — Hasil Pengujian

**Unit test**: `php tests/test_functions.php`
- **41 PASS, 0 FAIL** (exit code 0)
- Mencakup: subtotal/total pesanan, jumlah tamu vs kapasitas, harga/kuantitas tidak boleh ≤ 0, dan penolakan reservasi bentrok
- Jika ada pengujian gagal, exit code menjadi 1 (sudah diverifikasi)

**Uji end-to-end** (MariaDB + server PHP): **48 skenario OK**
**Uji rollback transaksi**: pesanan gagal tidak tersimpan setengah

**Debugging**: kesalahan metode redirect pada skrip uji, README berencoding UTF-16, dan antisipasi format waktu `HH:MM` vs `HH:MM:SS`

---

## Slide 8 — Kesimpulan

- Semua fitur utama di PRD sudah berjalan dan teruji.
- Kompetensi yang ditunjukkan: guidelines & best practices, pemrograman terstruktur, OOP, library Bootstrap, akses basis data (PDO dan transaksi), dokumentasi, debugging, dan unit test.
- Pengembangan lanjutan: login dan hak akses, proteksi CSRF, pencarian dan paginasi.

**Terima kasih — Sesi tanya jawab**
