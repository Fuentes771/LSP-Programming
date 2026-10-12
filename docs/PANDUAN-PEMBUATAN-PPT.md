# Panduan Pembuatan Presentasi PPTX — Sistem Pemesanan Restoran

> Dokumen ini adalah **brief lengkap** untuk Claude (atau pembuat presentasi lain) yang akan membuat **file PowerPoint (`.pptx`)** untuk presentasi uji kompetensi **Sertifikasi LSP Programmer**.
>
> Semua informasi yang dibutuhkan ada di dokumen ini. Pembuat **tidak perlu** mengakses kode sumber. Jangan menambah fitur, angka, atau hasil pengujian yang tidak tertulis di sini.
>
> Dokumen pendamping: `PANDUAN-PEMBUATAN-DOCX.md` (laporan & diagram). Jika file diagram PNG dari panduan tersebut sudah ada, gunakan di slide yang disebutkan.

---

## Daftar Isi Panduan

0. [Instruksi untuk Pembuat Presentasi](#0-instruksi-untuk-pembuat-presentasi)
1. [Fakta Proyek (Sumber Isi Slide)](#1-fakta-proyek-sumber-isi-slide)
2. [Desain Visual](#2-desain-visual)
3. [Rincian Slide demi Slide](#3-rincian-slide-demi-slide)
4. [Slide Cadangan (Opsional)](#4-slide-cadangan-opsional)
5. [Aset Gambar](#5-aset-gambar)
6. [Naskah Presentasi dan Alokasi Waktu](#6-naskah-presentasi-dan-alokasi-waktu)
7. [Gambaran Hasil Akhir](#7-gambaran-hasil-akhir)
8. [Checklist Penerimaan](#8-checklist-penerimaan)

---

## 0. Instruksi untuk Pembuat Presentasi

**Peran:** Anda adalah desainer presentasi teknis. Presentasi dibawakan peserta di depan **asesor LSP** selama **±10 menit** (termasuk demo aplikasi), lalu dilanjutkan tanya jawab.

**Output yang diminta:**

| No | File | Keterangan |
|---|---|---|
| 1 | `Presentasi-Sistem-Pemesanan-Restoran.pptx` | **8 slide utama** + slide cadangan opsional (Bagian 4), rasio **16:9** |
| 2 | (opsional) `Presentasi-Sistem-Pemesanan-Restoran.pdf` | Ekspor PDF untuk cadangan bila proyektor/komputer bermasalah |

**Aturan wajib:**

1. **8 slide utama** sesuai urutan di Bagian 3. Jangan menambah slide utama; materi tambahan masuk slide cadangan setelah slide penutup.
2. **Jangan mengarang.** Angka pengujian (41 PASS/0 FAIL, 48/48 skenario, rollback OK) adalah data asli. Salin apa adanya.
3. **Identitas peserta memakai placeholder**: `[Nama Peserta]`, `[No. Peserta]`, `[Tanggal Uji]`, `[Nama Asesor]`, `[Logo LSP / Instansi]`.
4. **Sedikit teks, banyak visual.** Maksimal **±6 poin per slide** dan **±8 kata per poin**. Penjelasan detail ditaruh di **speaker notes**.
5. **Speaker notes wajib** di setiap slide, berbahasa Indonesia, dari naskah Bagian 6.
6. **Bahasa Indonesia** baku dan singkat. Istilah teknis boleh dalam bahasa Inggris.
7. Semua teks harus **editable** (bukan gambar teks). Diagram/screenshot boleh berupa gambar.
8. Jika gambar (diagram/screenshot) belum tersedia, gunakan **kotak placeholder** berbingkai putus-putus berlabel jelas, misalnya `[Gambar: Use Case Diagram]`, dengan ukuran dan posisi final, agar mudah diganti.

---

## 1. Fakta Proyek (Sumber Isi Slide)

### 1.1 Identitas Proyek

| Item | Keterangan |
|---|---|
| Judul | **Sistem Pemesanan Restoran** |
| Subjudul | Aplikasi Web PHP Native & MySQL |
| Konteks | Praktik demonstrasi Sertifikasi Kompetensi Skema Programmer (LSP/BNSP) |
| Pengguna | Petugas Restoran (satu aktor, tanpa login, untuk demonstrasi lokal) |
| Brand di aplikasi | "Restoran Kita" |

### 1.2 Latar Belakang & Tujuan

- **Masalah:** pencatatan manual rawan meja terpesan ganda, jumlah tamu melebihi kapasitas, total pesanan salah hitung, dan laporan sulit direkap.
- **Tujuan 1:** aplikasi web sederhana untuk mencatat pelanggan, meja, menu, reservasi, pesanan, dan laporan.
- **Tujuan 2:** menunjukkan kompetensi pemrograman (terstruktur, OOP, library, database, debugging, unit test).

### 1.3 Fitur

| Fitur | Poin singkat untuk slide |
|---|---|
| Dashboard | Ringkasan jumlah data + 5 reservasi terbaru |
| Pelanggan, Meja, Menu | Tambah, ubah, hapus; data berelasi dilindungi |
| Reservasi | Validasi kapasitas & jadwal bentrok; bisa dibatalkan |
| Pesanan | Banyak menu per pesanan; subtotal & total otomatis; harga terkunci saat dipesan |
| Laporan | Filter rentang tanggal; total tanpa pesanan dibatalkan; cetak lewat browser |

### 1.4 Aturan Bisnis Kunci (untuk dibahas)

- Reservasi **aktif** = `Menunggu` atau `Dikonfirmasi`.
- **Jumlah tamu ≤ kapasitas meja**; meja `Nonaktif` tidak bisa direservasi.
- Tidak boleh ada dua reservasi aktif di **meja + tanggal + waktu yang sama**.
- Harga pesanan diambil dari **database** (bukan form), lalu **disalin** ke detail pesanan.
- Pesanan + item disimpan dalam **satu transaksi** (`BEGIN` → `COMMIT`, atau `ROLLBACK` jika gagal).
- Data yang masih dipakai (misalnya pelanggan yang punya pesanan) **tidak bisa dihapus**; pesan yang tampil jelas.

### 1.5 Teknologi

| Teknologi | Keterangan |
|---|---|
| PHP Native 8.1+ | Bahasa server, tanpa framework (diuji PHP 8.3.6) |
| MySQL / MariaDB | Database relasional (diuji MariaDB 10.11.14) |
| PDO | Koneksi, *prepared statement*, transaksi |
| Bootstrap 5.3.3 (CDN) | Tampilan responsif: navbar, card, tabel, form, alert, badge |
| XAMPP / Laragon | Server lokal |
| PHP CLI | Menjalankan unit test |

### 1.6 Database (`restoran_db`, 6 tabel)

| Tabel | Isi |
|---|---|
| `customers` | Pelanggan: name, phone, email (opsional) |
| `restaurant_tables` | Meja: table_number (unik), capacity, status Aktif/Nonaktif |
| `menu_items` | Menu: name, price DECIMAL(12,2), is_available |
| `reservations` | Reservasi: customer_id, table_id, tanggal, waktu, guest_count, status, notes |
| `restaurant_orders` | Pesanan: customer_id, reservation_id (opsional), order_date, status, total_amount |
| `order_items` | Detail pesanan: menu_name & price (salinan), quantity, subtotal |

Relasi: `customers` 1–N `reservations`; `restaurant_tables` 1–N `reservations`; `customers` 1–N `restaurant_orders`; `reservations` 0..1–N `restaurant_orders`; `restaurant_orders` 1–N `order_items`; `menu_items` 1–N `order_items`. *Foreign key* `RESTRICT` (kecuali `order_items.order_id` = `CASCADE`).

### 1.7 Struktur Terstruktur & OOP

- **Terstruktur:** `includes/functions.php` berisi fungsi validasi & perhitungan murni (tanpa database), misalnya `calculateSubtotal()`, `calculateOrderTotal()`, `validateGuestCount()`, `hasReservationConflict()`.
- **OOP:** `abstract class BaseModel` (koneksi PDO, `count()`, `find()`) diwarisi oleh `Customer`, `RestaurantTable`, `MenuItem`, `Reservation`, dan `Order`.
- Konsep yang ditunjukkan: abstraksi, pewarisan, enkapsulasi (`protected`/`private`), *dependency injection* (PDO lewat constructor).

Cuplikan kode untuk slide (pilih yang pendek):

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

```php
abstract class BaseModel
{
    protected PDO $db;
    protected string $table;

    public function __construct(PDO $db) { $this->db = $db; }

    public function count(): int { /* SELECT COUNT(*) ... */ }
}

class Reservation extends BaseModel { /* cek kapasitas & bentrok */ }
```

### 1.8 Hasil Pengujian (DATA ASLI — jangan diubah)

| Pengujian | Hasil |
|---|---|
| Unit test `php tests/test_functions.php` | **41 PASS, 0 FAIL** (exit code 0) |
| Rincian unit test | Perhitungan subtotal/total **11**, jumlah tamu vs kapasitas **5**, harga & kuantitas **11**, reservasi bentrok **7**, validasi lain **7** |
| Bukti runner mendeteksi gagal | Fungsi sengaja dirusak pada salinan → **36 PASS, 5 FAIL**, exit code 1 |
| Uji end-to-end (HTTP, MariaDB + server PHP) | **48 dari 48 skenario OK**, tanpa warning PHP |
| Uji rollback transaksi | Insert item dipaksa gagal → pesanan **tidak tersimpan setengah** (ROLLBACK OK) |
| Contoh angka uji | Pesanan 3 item: total **Rp 85.000**; harga menu diubah Rp25.000 → Rp27.000, pesanan lama tetap Rp25.000 |
| Belum diuji | XAMPP/Laragon di Windows |

### 1.9 Debugging (3 kasus asli)

| Kasus | Ringkas |
|---|---|
| Uji E2E gagal di detail pesanan | Aplikasi benar; skrip uji `curl -X POST -L` tetap POST saat redirect → opsi diperbaiki → 48/48 OK |
| README ber-encoding UTF-16 | `grep` gagal membaca → dikonversi ke UTF-8 dengan `iconv` → terbaca normal |
| Format waktu `HH:MM` vs `HH:MM:SS` | Diantisipasi dengan `normalizeTime()`; tanpa fungsi itu unit test langsung FAIL (40 PASS, 1 FAIL) |

### 1.10 Pemetaan Unit Kompetensi

| Kode | Bukti singkat |
|---|---|
| J.620100.016.01 | Penamaan konsisten, validasi, *prepared statement*, escaping |
| J.620100.017.02 | Fungsi validasi & perhitungan |
| J.620100.018.02 | `BaseModel` + 5 class turunan |
| J.620100.019.02 | Bootstrap 5, PDO |
| J.620100.021.02 | MySQL, relasi FK, transaksi |
| J.620100.023.02 | README, laporan, presentasi |
| J.620100.025.02 | 3 kasus debugging |
| J.620100.033.02 | 41 unit test PASS/FAIL |

---

## 2. Desain Visual

### 2.1 Gaya Umum

Bersih, profesional, bernuansa restoran yang hangat tetapi tetap teknis. Banyak ruang kosong, satu ide utama per slide.

| Aspek | Ketentuan |
|---|---|
| Rasio | **16:9** (13,333 × 7,5 inci) |
| Font judul | **Poppins SemiBold** atau **Montserrat SemiBold**; fallback **Arial Bold** |
| Font isi | **Inter**, **Open Sans**, atau **Calibri**; fallback **Arial** |
| Font kode | **Consolas** / **Courier New** |
| Ukuran | Judul slide 32–36 pt; isi 18–22 pt (minimal 16 pt); kode 14–16 pt; footer 10–11 pt |
| Perataan | Rata kiri untuk teks; judul rata kiri di area atas |
| Margin aman | 0,5 inci di semua sisi |

### 2.2 Palet Warna

| Peran | Warna | Hex |
|---|---|---|
| Utama (judul, aksen bar) | Merah bata / terracotta | `#B23A2B` |
| Sekunder (ikon, garis) | Oranye hangat | `#E8833A` |
| Gelap (teks utama, latar slide judul) | Arang | `#2B2B2B` |
| Terang (latar konten) | Krem sangat muda | `#FFF8F1` atau putih `#FFFFFF` |
| Netral (kartu, tabel) | Abu muda | `#F2F2F2` |
| Sukses (PASS, OK) | Hijau | `#2E7D32` |
| Gagal/peringatan (FAIL, ditolak) | Merah | `#C62828` |

Gunakan warna sukses/gagal **hanya** untuk status pengujian dan contoh validasi.

### 2.3 Elemen Berulang (Master Slide)

- Bar aksen tipis warna utama di kiri atas atau garis bawah judul.
- **Footer** di setiap slide konten: kiri "Sistem Pemesanan Restoran · Sertifikasi LSP Programmer", kanan **nomor slide**.
- Slide judul dan slide penutup tanpa footer.
- Ikon garis sederhana (gaya *outline*) satu warna, misalnya ikon pengguna, meja, menu, kalender, struk, grafik, database, kode, centang. Gunakan set ikon yang konsisten.

### 2.4 Hindari

- Paragraf panjang dan teks di bawah 16 pt.
- Animasi berlebihan (cukup transisi *Fade* sederhana atau tanpa transisi).
- Clip-art, foto stok yang tidak relevan, dan efek 3D/bayangan berat.
- Mengisi slide dengan tabel besar (maksimal ±6 baris per tabel di slide).

---

## 3. Rincian Slide demi Slide

Setiap slide berisi: **judul**, **tata letak**, **isi**, **visual**, dan **speaker notes** (lihat Bagian 6 untuk naskah lengkap).

### Slide 1 — Judul dan Identitas Peserta

- **Tata letak:** latar gelap `#2B2B2B` atau gradasi utama, teks putih. Kiri: judul & identitas. Kanan: ilustrasi/ikon besar (piring + garpu, atau mockup layar aplikasi) **atau** placeholder `[Logo LSP / Instansi]` di pojok kanan atas.
- **Isi:**
  - Judul: **Sistem Pemesanan Restoran**
  - Subjudul: Aplikasi Web PHP Native & MySQL
  - Garis pemisah tipis warna sekunder
  - Nama: `[Nama Peserta]` · No. Peserta: `[No. Peserta]`
  - Skema: Programmer · Asesor: `[Nama Asesor]` · `[Tanggal Uji]`
- **Visual:** mockup laptop berisi screenshot dashboard (jika ada), atau ikon restoran besar.

### Slide 2 — Latar Belakang dan Tujuan

- **Tata letak:** dua kolom. Kiri: kartu **"Masalah"** (ikon peringatan). Kanan: kartu **"Tujuan"** (ikon target).
- **Isi kiri (Masalah):**
  - Meja bisa terpesan ganda
  - Tamu melebihi kapasitas meja
  - Total pesanan salah hitung
  - Laporan sulit direkap
- **Isi kanan (Tujuan):**
  - Mencatat pelanggan, meja, menu, reservasi, pesanan
  - Validasi otomatis & total akurat
  - Laporan per periode yang bisa dicetak
  - Bukti kompetensi LSP Programmer
- **Visual:** panah dari "Masalah" ke "Tujuan" (solusi).

### Slide 3 — Fitur Aplikasi

- **Tata letak:** grid **6 kartu** (3 × 2) dengan ikon, judul fitur, dan 1 baris keterangan. Opsional: gambar kecil Use Case Diagram di sudut kanan bawah dengan label "Use Case".
- **Isi kartu:**

| Ikon | Judul | Keterangan |
|---|---|---|
| grafik | Dashboard | Ringkasan data & reservasi terbaru |
| pengguna | Pelanggan | Kelola data, email opsional |
| meja | Meja & Menu | Kapasitas, status, harga |
| kalender | Reservasi | Cek kapasitas & jadwal bentrok |
| struk | Pesanan | Banyak menu, total otomatis |
| printer | Laporan | Filter tanggal & cetak |

- **Visual:** ikon konsisten di setiap kartu. Opsional: `01-use-case.png`.

### Slide 4 — Teknologi dan Database

- **Tata letak:** kiri (±35%): daftar teknologi dengan logo/ikon kecil. Kanan (±65%): **ERD** (`08-erd.png`) atau diagram relasi sederhana 6 kotak tabel.
- **Isi kiri:**
  - PHP Native 8.1+
  - MySQL / MariaDB
  - PDO (*prepared statement*, transaksi)
  - Bootstrap 5.3.3
  - XAMPP / Laragon
- **Isi kanan:** ERD 6 tabel + 3 catatan kecil di bawahnya:
  - Harga `DECIMAL(12,2)`
  - *Foreign key* mencegah relasi rusak
  - Pesanan & item disimpan dalam transaksi
- **Visual:** jika `08-erd.png` belum ada, gambar 6 kotak tabel dengan garis relasi sesuai Bagian 1.6 (cukup nama tabel + PK/FK utama).

### Slide 5 — Contoh Pemrograman Terstruktur dan OOP

- **Tata letak:** dua kolom kode. Kiri: **"Terstruktur"**, kotak kode `calculateSubtotal` + `validateGuestCount`. Kanan: **"OOP"**, diagram hierarki class (kotak `BaseModel` di atas, 5 class di bawah dengan garis pewarisan) + 4 label konsep.
- **Isi kanan (label konsep):** Abstraksi · Pewarisan · Enkapsulasi · *Dependency Injection*
- **Visual:** kotak kode berlatar gelap (`#2B2B2B`, teks terang, font Consolas 14–16 pt) atau terang (`#F2F2F2`). Highlight sintaks sederhana bila memungkinkan.
- **Catatan:** kode harus tetap terbaca dari belakang ruangan; potong kode jika terlalu panjang.

### Slide 6 — Demo Aplikasi

- **Tata letak:** kiri: **daftar langkah demo bernomor**. Kanan: **1 screenshot besar** (halaman Reservasi atau Buat Pesanan) atau kolase 3 screenshot kecil (Dashboard, Reservasi, Laporan).
- **Isi (langkah demo):**
  1. Dashboard: ringkasan data
  2. Tambah pelanggan (coba email salah → ditolak)
  3. Reservasi M02 untuk 5 tamu → **ditolak** (kapasitas 4)
  4. Reservasi bentrok jam sama → **ditolak**
  5. Buat pesanan 3 menu → total otomatis
  6. Hapus pelanggan berelasi → **ditolak**
  7. Laporan → filter tanggal → **Cetak**
- **Visual:** label merah kecil "Ditolak" dan hijau "Berhasil" di samping langkah terkait. Tulisan kecil di bawah: "Demo langsung di XAMPP/Laragon".

### Slide 7 — Hasil Pengujian

- **Tata letak:** baris atas: **3 kartu angka besar**. Baris bawah: kiri = tabel rincian unit test (5 baris), kanan = ringkasan debugging (3 poin).
- **Kartu angka:**

| Angka besar | Label |
|---|---|
| **41 / 41** | Unit test PASS |
| **48 / 48** | Skenario end-to-end OK |
| **✓** | Rollback transaksi berhasil |

- **Tabel rincian unit test:** Perhitungan subtotal & total (11) · Jumlah tamu vs kapasitas (5) · Harga & kuantitas (11) · Reservasi bentrok (7) · Validasi lain (7)
- **Debugging (3 poin singkat):**
  - Metode redirect pada skrip uji
  - Encoding README UTF-16 → UTF-8
  - Format waktu `HH:MM` vs `HH:MM:SS`
- **Visual:** warna hijau untuk angka PASS/OK. Opsional: potongan screenshot terminal output test (tampilan teks monospace "Hasil: 41 PASS, 0 FAIL dari 41 pengujian.").

### Slide 8 — Kesimpulan

- **Tata letak:** kiri: 3–4 poin kesimpulan. Kanan: daftar unit kompetensi dalam format ringkas (8 baris kecil berikon centang). Bawah: **"Terima kasih — Sesi Tanya Jawab"**.
- **Isi kesimpulan:**
  - Semua fitur utama berjalan & teruji
  - Validasi server menjaga data tetap konsisten
  - Terstruktur + OOP + PDO + Bootstrap diterapkan
  - Pengembangan lanjut: login, CSRF, pencarian/paginasi
- **Isi kanan:** 8 kode unit kompetensi (Bagian 1.10), masing-masing dengan centang dan 2–4 kata bukti.
- **Visual:** latar bisa sama dengan slide judul (gelap) untuk menutup presentasi.

---

## 4. Slide Cadangan (Opsional)

Letakkan **setelah slide 8**, dengan judul berawalan "Lampiran". Slide ini hanya dibuka jika asesor bertanya.

| No | Judul | Isi |
|---|---|---|
| L1 | Lampiran — Use Case Diagram | `01-use-case.png` ukuran penuh |
| L2 | Lampiran — Activity Diagram Reservasi | `02-activity-reservasi.png` |
| L3 | Lampiran — Flowchart Simpan Pesanan (Transaksi) | `07-flowchart-pesanan.png` |
| L4 | Lampiran — Struktur Folder | pohon folder (lihat di bawah) dalam kotak monospace |
| L5 | Lampiran — Keamanan Dasar | *Prepared statement* · escaping XSS (`htmlspecialchars`) · whitelist halaman · aksi hapus via POST + konfirmasi · error DB tidak ditampilkan |

Struktur folder untuk L4:

```text
LSP-Programming/
├── config/database.php
├── classes/   BaseModel, Customer, RestaurantTable, MenuItem, Reservation, Order
├── includes/  functions.php, header.php, navbar.php, footer.php
├── pages/     dashboard, customers, tables, menu, reservations, orders, reports
├── tests/test_functions.php
├── database.sql
└── index.php
```

---

## 5. Aset Gambar

| Aset | Dipakai di | Sumber | Jika belum ada |
|---|---|---|---|
| Screenshot Dashboard | Slide 1 (mockup), Slide 6 | Tangkapan layar aplikasi | Placeholder `[Screenshot: Dashboard]` |
| Screenshot Reservasi (pesan bentrok/kapasitas) | Slide 6 | Tangkapan layar aplikasi | Placeholder `[Screenshot: Reservasi ditolak]` |
| Screenshot Buat/Detail Pesanan | Slide 6 | Tangkapan layar aplikasi | Placeholder `[Screenshot: Detail Pesanan]` |
| Screenshot Laporan | Slide 6 | Tangkapan layar aplikasi | Placeholder `[Screenshot: Laporan]` |
| Screenshot terminal unit test | Slide 7 (opsional) | Output `php tests/test_functions.php` | Kotak teks monospace berisi "Hasil: 41 PASS, 0 FAIL dari 41 pengujian." |
| `01-use-case.png` | Slide 3 (opsional), L1 | Dari panduan DOCX/draw.io | Placeholder `[Gambar: Use Case Diagram]` |
| `08-erd.png` | Slide 4 | Dari panduan DOCX/draw.io | Gambar ulang 6 kotak tabel + garis relasi |
| `02-activity-reservasi.png`, `07-flowchart-pesanan.png` | L2, L3 | Dari panduan DOCX/draw.io | Placeholder |
| Logo LSP / instansi | Slide 1 | Disediakan peserta | Placeholder `[Logo LSP / Instansi]` |
| Ikon | Slide 2, 3, 4, 7, 8 | Set ikon garis sederhana (gaya konsisten) | Bentuk lingkaran berisi huruf/simbol sederhana |

---

## 6. Naskah Presentasi dan Alokasi Waktu

Total **±10 menit** (presentasi ±6 menit + demo ±4 menit). Masukkan naskah ini ke **speaker notes** masing-masing slide.

| Slide | Waktu | Speaker notes (naskah) |
|---|---|---|
| 1 | 0:30 | "Selamat pagi/siang, Bapak/Ibu Asesor. Saya [Nama Peserta], nomor peserta [No. Peserta]. Pada kesempatan ini saya mempresentasikan aplikasi Sistem Pemesanan Restoran, aplikasi web berbasis PHP Native dan MySQL yang saya buat untuk praktik demonstrasi skema Programmer." |
| 2 | 0:45 | "Masalah yang sering terjadi pada pencatatan manual adalah meja terpesan dua kali, jumlah tamu melebihi kapasitas, dan total pesanan salah hitung. Karena itu tujuan aplikasi ini adalah mencatat data restoran dengan validasi otomatis dan laporan yang mudah dicetak, sekaligus menjadi bukti kompetensi pemrograman saya." |
| 3 | 1:00 | "Aplikasi memiliki enam bagian utama. Dashboard menampilkan ringkasan. Data pelanggan, meja, dan menu dapat dikelola; data yang masih berelasi tidak bisa dihapus. Reservasi divalidasi terhadap kapasitas meja dan jadwal bentrok. Pesanan dapat berisi beberapa menu dengan total yang dihitung otomatis, dan harga dikunci saat pesanan dibuat. Terakhir, laporan dapat difilter per tanggal dan dicetak." |
| 4 | 1:00 | "Teknologi yang saya gunakan adalah PHP Native, MySQL atau MariaDB, PDO, dan Bootstrap 5 dari CDN, dijalankan dengan XAMPP atau Laragon. Database terdiri dari enam tabel yang saling berelasi dengan foreign key. Harga disimpan sebagai DECIMAL agar perhitungan uang tepat, dan pesanan beserta itemnya disimpan dalam satu transaksi sehingga tidak ada data yang tersimpan setengah." |
| 5 | 1:15 | "Pemrograman terstruktur saya terapkan lewat fungsi-fungsi kecil di functions.php, misalnya calculateSubtotal untuk menghitung subtotal dan validateGuestCount untuk memeriksa jumlah tamu terhadap kapasitas. Fungsi ini tidak mengakses database sehingga bisa diuji langsung. Untuk OOP, saya membuat class abstrak BaseModel yang menyimpan koneksi PDO dan method umum, lalu diwarisi oleh lima class: Customer, RestaurantTable, MenuItem, Reservation, dan Order. Di sini terlihat abstraksi, pewarisan, enkapsulasi, dan dependency injection." |
| 6 | 4:00 | "Sekarang saya demonstrasikan aplikasinya." Lalu ikuti 7 langkah demo di slide. Sorot pesan "Jumlah tamu (5) melebihi kapasitas meja M02 (4 orang)", pesan bentrok jadwal, total pesanan otomatis, pesan penolakan hapus pelanggan, dan tampilan cetak laporan tanpa navbar. |
| 7 | 1:00 | "Untuk pengujian, saya membuat 41 unit test yang dijalankan dari terminal dan semuanya PASS. Runner akan keluar dengan status gagal jika ada test yang gagal; ini sudah saya buktikan dengan sengaja merusak fungsi pada salinan. Saya juga menguji 48 skenario aplikasi lewat HTTP dan semuanya berhasil, serta menguji rollback transaksi pesanan. Selama pengembangan ada tiga kasus debugging yang saya dokumentasikan, salah satunya perbedaan format waktu antara form dan database." |
| 8 | 0:30 | "Kesimpulannya, semua fitur utama sesuai spesifikasi telah berjalan dan teruji, serta menunjukkan penerapan delapan unit kompetensi yang diujikan. Pengembangan selanjutnya bisa berupa login, proteksi CSRF, dan pencarian data. Terima kasih, saya siap menjawab pertanyaan." |

**Persiapan demo (catatan untuk peserta, boleh dimasukkan ke speaker notes slide 6):**
- Impor ulang `database.sql` sebelum presentasi agar data contoh bersih.
- Buka aplikasi di tab browser terpisah sebelum mulai.
- Siapkan screenshot di slide 6 sebagai cadangan jika demo bermasalah.

---

## 7. Gambaran Hasil Akhir

### 7.1 Slide 1 (Judul)

```text
┌──────────────────────────────────────────────────────────────┐
│ [Logo LSP]                                 (latar gelap)     │
│                                                              │
│  SISTEM PEMESANAN RESTORAN                ┌──────────────┐   │
│  Aplikasi Web PHP Native & MySQL          │  [Mockup     │   │
│  ───────────                              │   laptop +   │   │
│  [Nama Peserta] · [No. Peserta]           │   dashboard] │   │
│  Skema Programmer · [Nama Asesor]         └──────────────┘   │
│  [Tanggal Uji]                                               │
└──────────────────────────────────────────────────────────────┘
```

### 7.2 Slide 3 (Fitur)

```text
┌──────────────────────────────────────────────────────────────┐
│ ▌Fitur Aplikasi                                              │
│                                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                    │
│  │ 📊       │  │ 👤       │  │ 🍽       │                    │
│  │Dashboard │  │Pelanggan │  │Meja&Menu │                    │
│  │Ringkasan │  │Kelola    │  │Kapasitas │                    │
│  └──────────┘  └──────────┘  └──────────┘                    │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                    │
│  │ 📅       │  │ 🧾       │  │ 🖨       │                    │
│  │Reservasi │  │Pesanan   │  │Laporan   │                    │
│  │Cek bentrok│ │Total auto│  │Cetak     │                    │
│  └──────────┘  └──────────┘  └──────────┘                    │
│ Sistem Pemesanan Restoran · Sertifikasi LSP Programmer    3  │
└──────────────────────────────────────────────────────────────┘
```

(Emoji di atas hanya gambaran; gunakan ikon garis sederhana di PPTX.)

### 7.3 Slide 7 (Hasil Pengujian)

```text
┌──────────────────────────────────────────────────────────────┐
│ ▌Hasil Pengujian                                             │
│                                                              │
│   ┌─────────┐      ┌─────────┐      ┌─────────┐              │
│   │ 41 / 41 │      │ 48 / 48 │      │    ✓    │              │
│   │Unit test│      │  E2E OK │      │Rollback │              │
│   └─────────┘      └─────────┘      └─────────┘              │
│                                                              │
│  Rincian unit test          │  Debugging                     │
│  Subtotal & total ...... 11 │  • Metode redirect skrip uji   │
│  Tamu vs kapasitas ...... 5 │  • README UTF-16 → UTF-8       │
│  Harga & kuantitas ..... 11 │  • Format waktu HH:MM          │
│  Reservasi bentrok ...... 7 │                                │
│  Validasi lain .......... 7 │                                │
│ Sistem Pemesanan Restoran · Sertifikasi LSP Programmer    7  │
└──────────────────────────────────────────────────────────────┘
```

### 7.4 Urutan Akhir File

```text
1  Judul & Identitas
2  Latar Belakang & Tujuan
3  Fitur Aplikasi
4  Teknologi & Database
5  Pemrograman Terstruktur & OOP
6  Demo Aplikasi
7  Hasil Pengujian
8  Kesimpulan · Terima Kasih
—  L1–L5 Lampiran (opsional)
```

---

## 8. Checklist Penerimaan

- [ ] File `.pptx` 16:9, **8 slide utama** sesuai urutan Bagian 3 (+ lampiran opsional setelah slide 8).
- [ ] Placeholder identitas tetap placeholder (`[Nama Peserta]`, dst.); tidak ada nama karangan.
- [ ] Setiap slide punya **speaker notes** dari Bagian 6.
- [ ] Angka pengujian **identik**: 41 PASS/0 FAIL; rincian 11-5-11-7-7; 48/48 E2E; rollback OK.
- [ ] Tidak ada fitur yang tidak ada di aplikasi (tidak ada login, pembayaran online, API, ekspor PDF/Excel).
- [ ] Teks minimal 16 pt, maksimal ±6 poin per slide, kode terbaca jelas.
- [ ] Palet warna & font konsisten di semua slide; footer & nomor slide ada di slide konten.
- [ ] Gambar yang belum tersedia diganti placeholder berlabel dengan posisi & ukuran final.
- [ ] Semua teks dapat diedit (bukan gambar teks).
- [ ] (Opsional) Ekspor PDF tersedia sebagai cadangan.
