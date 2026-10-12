# Screenshot Aplikasi

Tangkapan layar Sistem Pemesanan Restoran (versi terbaru di `main`), diambil dari data contoh `database.sql`. Gambar-gambar ini bisa langsung dipakai di laporan DOCX (Bab V) dan presentasi PPT; lampirkan bersama `docs/PANDUAN-PEMBUATAN-DOCX.md` / `docs/PANDUAN-PEMBUATAN-PPT.md`.

| File | Isi | Saran pemakaian |
|---|---|---|
| `01-dashboard.png` | Dashboard: total data, reservasi hari ini, pesanan diproses, pendapatan hari ini | Gambar 5.1, Slide 1 & 6 |
| `02-pelanggan.png` | Data pelanggan + form tambah + pencarian | Gambar 5.2 |
| `03-meja.png` | Data meja (kapasitas, status, jumlah reservasi aktif) | Gambar 5.3 |
| `04-menu.png` | Data menu (harga, ketersediaan) | Gambar 5.4 |
| `05-reservasi-daftar.png` | Daftar reservasi tab Semua, tombol aksi sesuai status | Gambar 5.5, Slide 6 |
| `06-reservasi-form-pelanggan-baru.png` | Form reservasi dengan pilihan **Pelanggan baru** | Gambar 5.6 |
| `07-reservasi-error-tumpang-tindih.png` | Penolakan reservasi yang tumpang tindih (slot 2 jam) | Gambar 5.7, Slide 6 |
| `08-pesanan-dari-reservasi.png` | Form pesanan **Dari reservasi**: pelanggan, meja, jadwal, tamu otomatis + pratinjau total | Gambar 5.8, Slide 6 |
| `09-pesanan-walkin.png` | Form pesanan **Tanpa reservasi (walk-in)** | Gambar 5.9 |
| `10-pesanan-detail.png` | Detail pesanan Diproses (Tandai Selesai / Batalkan) | Gambar 5.10 |
| `11-pesanan-selesai-reservasi-otomatis.png` | Setelah Tandai Selesai: "Reservasi terkait otomatis ditandai selesai" | Gambar 5.11, Slide 6 |
| `12-reservasi-selesai-otomatis.png` | Reservasi tersebut kini berstatus Selesai | Gambar 5.12 |
| `13-laporan.png` | Laporan reservasi & pesanan (bulan ini) | Gambar 5.13 |
| `14-laporan-cetak.png` | Tampilan cetak laporan (navbar & form disembunyikan) | Gambar 5.14 |
| `15-ponsel-reservasi.png` | Tampilan responsif di layar ponsel (390 px) | Gambar 5.15 |
| `16-unit-test.png` | Output `php tests/test_functions.php`: 78 PASS, 0 FAIL | Bab VIII, Slide 7 |

Catatan:
- Tampilan desktop 1280 px, locale Indonesia (format tanggal 13/10/2026, jam 19.00). Tanggal di gambar mengikuti hari pengambilan (12-10-2026), karena data contoh memakai tanggal relatif.
- Saat diambil, Bootstrap 5.3.3 dan Bootstrap Icons 1.11.3 disajikan dari salinan paket npm versi yang sama dengan CDN, sehingga tampilannya identik.

## Mengambil ulang

```bash
mysql -u root -p < database.sql        # data contoh bersih
php -S localhost:8000                  # jalankan aplikasi
npm install playwright                 # sekali saja
node docs/screenshots/capture.js
mysql -u root -p < database.sql        # skrip mengubah data, impor ulang setelahnya
```

Jika komputer tidak bisa mengakses CDN, isi variabel `ASSETS_DIR` (lihat komentar di `capture.js`).
