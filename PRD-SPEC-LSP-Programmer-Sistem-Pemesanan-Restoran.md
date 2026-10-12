# PRD/SPEC — Sistem Pemesanan Restoran

## 1. Tujuan

Buat aplikasi web sederhana untuk tugas praktik demonstrasi **Sertifikasi LSP Programmer**. Aplikasi digunakan untuk mencatat data pelanggan, meja, reservasi, pesanan menu, dan melihat laporan.

Prioritas utama adalah aplikasi mudah dijalankan, mudah dipahami, dan dapat digunakan untuk menunjukkan kompetensi pemrograman yang diminta. **Jangan membuat fitur atau arsitektur yang tidak diperlukan.**

## 2. Teknologi

- PHP Native 8.1 atau lebih baru.
- MySQL/MariaDB.
- Bootstrap 5 melalui CDN untuk tampilan.
- PDO untuk koneksi dan query database.
- HTML dan CSS seperlunya.
- Unit test sederhana dengan PHP CLI; tidak wajib memakai framework testing.

Aplikasi harus dapat dijalankan secara lokal menggunakan XAMPP atau Laragon. Jangan gunakan Laravel, React, Docker, REST API, atau arsitektur yang kompleks.

## 3. Fitur Utama

### A. Dashboard
- Menampilkan jumlah pelanggan, meja, reservasi, dan pesanan.
- Menampilkan beberapa reservasi terbaru.

### B. Data Pelanggan
- Melihat daftar pelanggan.
- Menambah, mengubah, dan menghapus pelanggan.
- Data: nama, nomor telepon, dan email (email boleh kosong).

### C. Data Meja
- Melihat daftar meja.
- Menambah, mengubah, dan menghapus meja.
- Data: nomor meja, kapasitas, dan status (Aktif/Nonaktif).

### D. Reservasi Meja
- Membuat reservasi dengan memilih pelanggan dan meja.
- Mengisi tanggal, waktu, jumlah tamu, dan catatan opsional.
- Melihat, mengubah, dan membatalkan reservasi.
- Status reservasi: Menunggu, Dikonfirmasi, Selesai, atau Dibatalkan.
- Validasi: jumlah tamu tidak boleh melebihi kapasitas meja dan meja yang sama tidak boleh memiliki reservasi aktif pada tanggal serta waktu yang sama.

### E. Menu dan Pesanan
- Mengelola menu: nama menu, harga, dan status tersedia/tidak tersedia.
- Membuat pesanan untuk pelanggan, dengan memilih satu atau lebih menu dan jumlahnya.
- Menghitung subtotal setiap item dan total pesanan secara otomatis.
- Menampilkan detail pesanan beserta status: Diproses, Selesai, atau Dibatalkan.
- Harga yang disimpan pada detail pesanan harus mengikuti harga menu saat pesanan dibuat.

### F. Laporan
- Laporan reservasi berdasarkan rentang tanggal.
- Laporan pesanan berdasarkan rentang tanggal.
- Menampilkan jumlah reservasi, jumlah pesanan, dan total nilai pesanan.
- Menyediakan tombol cetak menggunakan fitur cetak browser. Tidak perlu membuat PDF atau ekspor Excel.

## 4. Aturan dan Batasan

- Aplikasi ditujukan untuk demonstrasi lokal, bukan sistem produksi yang terbuka ke internet.
- Tidak perlu login, hak akses bertingkat, pembayaran online, notifikasi, API, atau integrasi pihak ketiga.
- Gunakan bahasa antarmuka Indonesia dan tampilan Bootstrap yang rapi serta responsif.
- Tampilkan pesan berhasil atau gagal setelah pengguna melakukan aksi.
- Jangan biarkan data terkait dihapus jika penghapusan menyebabkan relasi rusak; tampilkan pesan yang mudah dipahami.
- Validasi input di server, bukan hanya di browser.
- Gunakan prepared statement PDO untuk query yang menerima input pengguna.

## 5. Rancangan Database

Buat file `database.sql` untuk membuat database dan tabel berikut:

1. `customers`: id, name, phone, email, created_at.
2. `restaurant_tables`: id, table_number, capacity, status.
3. `menu_items`: id, name, price, is_available.
4. `reservations`: id, customer_id, table_id, reservation_date, reservation_time, guest_count, status, notes, created_at.
5. `restaurant_orders`: id, customer_id, reservation_id (boleh kosong), order_date, status, total_amount, created_at.
6. `order_items`: id, order_id, menu_item_id, menu_name, price, quantity, subtotal.

Gunakan primary key dan foreign key yang sesuai. Simpan harga dan total sebagai tipe numerik yang sesuai, bukan teks. Gunakan transaksi database saat menyimpan pesanan beserta detail item agar data tidak tersimpan setengah jika terjadi kegagalan.

## 6. Struktur Folder Sederhana

```text
restoran/
├── config/
│   └── database.php
├── classes/
│   ├── Customer.php
│   ├── Reservation.php
│   └── Order.php
├── includes/
│   ├── header.php
│   ├── navbar.php
│   ├── footer.php
│   └── functions.php
├── pages/
│   ├── dashboard.php
│   ├── customers.php
│   ├── tables.php
│   ├── menu.php
│   ├── reservations.php
│   ├── orders.php
│   └── reports.php
├── tests/
│   └── test_functions.php
├── database.sql
├── index.php
└── README.md
```

Boleh menyesuaikan struktur selama tetap sederhana, mudah dipahami, dan tidak mencampur seluruh kode ke dalam satu file besar.

## 7. Ketentuan Penulisan Kode

- Gunakan nama variabel, fungsi, class, dan kolom yang konsisten.
- Pisahkan koneksi database, tampilan, fungsi bantuan, dan logika utama.
- Terapkan **pemrograman terstruktur** melalui fungsi untuk validasi, perhitungan total, dan kebutuhan berulang.
- Terapkan **OOP** melalui class seperti `Customer`, `Reservation`, dan `Order`, yang memiliki tanggung jawab jelas.
- Hindari kode berulang jika dapat dijadikan fungsi.
- Tambahkan komentar hanya untuk bagian yang perlu dijelaskan.
- Tangani error database dengan benar; jangan tampilkan password atau detail sensitif ke halaman pengguna.
- Escape data saat menampilkannya ke HTML untuk mengurangi risiko XSS.
- Jangan menulis query database dengan menggabungkan langsung input pengguna.

## 8. Debugging dan Unit Test

Buat `tests/test_functions.php` yang dapat dijalankan dari terminal:

```bash
php tests/test_functions.php
```

Uji sekurang-kurangnya:
- Perhitungan subtotal dan total pesanan.
- Validasi jumlah tamu terhadap kapasitas meja.
- Validasi nilai harga dan kuantitas agar tidak negatif atau nol jika tidak diperbolehkan.
- Penolakan reservasi bentrok pada meja, tanggal, dan waktu yang sama.

Tes harus menampilkan hasil **PASS/FAIL** serta keluar dengan status gagal jika ada pengujian yang gagal. Pisahkan logika yang akan diuji agar dapat dijalankan tanpa harus membuka browser. Perbaiki error yang ditemukan dan catat contoh proses debugging di laporan.

## 9. Dokumen yang Wajib Dibuat

### A. Source Code
- Seluruh file aplikasi.
- `database.sql`.
- Unit test.

### B. `README.md`
Berisi kebutuhan software, cara membuat database, cara mengatur koneksi, cara menjalankan aplikasi di XAMPP/Laragon, dan cara menjalankan unit test.

### C. Laporan Pengembangan
Buat `docs/laporan.md` dengan susunan sederhana:
1. Pendahuluan dan tujuan.
2. Kebutuhan aplikasi.
3. Teknologi yang digunakan.
4. Perancangan singkat dan struktur database.
5. Implementasi fitur.
6. Penerapan pemrograman terstruktur, OOP, library, dan database.
7. Debugging yang dilakukan.
8. Hasil pengujian unit.
9. Kesimpulan.

Isi laporan harus sesuai dengan implementasi dan hasil pengujian yang benar-benar dilakukan. Jangan mengarang hasil pengujian.

### D. Slide Presentasi
Buat `docs/presentasi.md` berisi outline 6–8 slide yang bisa dipindahkan ke PowerPoint:
1. Judul dan identitas peserta.
2. Latar belakang dan tujuan.
3. Fitur aplikasi.
4. Teknologi dan database.
5. Contoh penerapan pemrograman terstruktur dan OOP.
6. Demo aplikasi.
7. Hasil pengujian.
8. Kesimpulan.

Tidak perlu membuat desain presentasi yang rumit. Jika lingkungan mendukung pembuatan `.pptx`, buat PPT sederhana dari outline tersebut; jika tidak, pastikan outline siap digunakan.

## 10. Pemetaan Unit Kompetensi LSP

| Kode | Bukti dalam proyek |
|---|---|
| J.620100.016.01 — Menulis kode dengan guidelines dan best practices | Penamaan konsisten, validasi, penanganan error, prepared statement, dan escaping output. |
| J.620100.017.02 — Mengimplementasikan pemrograman terstruktur | Fungsi modular untuk validasi dan perhitungan. |
| J.620100.018.02 — Mengimplementasikan pemrograman berorientasi objek | Class `Customer`, `Reservation`, dan `Order`. |
| J.620100.019.02 — Menggunakan library/komponen pre-existing | Bootstrap 5 untuk komponen antarmuka. |
| J.620100.021.02 — Menerapkan akses basis data | MySQL/MariaDB melalui PDO, relasi tabel, prepared statement, dan transaksi pesanan. |
| J.620100.023.02 — Membuat dokumen kode program | README, komentar seperlunya, laporan, dan outline presentasi. |
| J.620100.025.02 — Melakukan debugging | Catat minimal satu contoh masalah, penyebab, perbaikan, dan verifikasinya jika ditemukan saat pengembangan. |
| J.620100.033.02 — Melaksanakan pengujian unit program | Skrip unit test dan hasil PASS/FAIL yang dapat dijalankan ulang. |

## 11. Kriteria Selesai (Acceptance Criteria)

Aplikasi dinyatakan selesai jika:

- [ ] Dapat dijalankan secara lokal mengikuti README.
- [ ] Database dapat dibuat melalui `database.sql`.
- [ ] Data pelanggan, meja, dan menu dapat dikelola.
- [ ] Reservasi dapat dibuat, diubah, dibatalkan, dan divalidasi.
- [ ] Pesanan dengan beberapa item dapat disimpan dan totalnya dihitung dengan benar.
- [ ] Laporan reservasi dan pesanan dapat difilter berdasarkan tanggal dan dicetak.
- [ ] Input pengguna divalidasi dan query menggunakan prepared statement.
- [ ] Contoh pemrograman terstruktur, OOP, dan penggunaan Bootstrap dapat ditunjukkan saat demonstrasi.
- [ ] Unit test dapat dijalankan dan hasil aktualnya dicantumkan di laporan.
- [ ] README, laporan, dan outline presentasi tersedia.

## 12. Instruksi untuk Claude Code

1. Baca seluruh PRD/SPEC ini sebelum mulai menulis kode.
2. Implementasikan aplikasi yang sederhana dan berfungsi; jangan menambahkan fitur di luar spesifikasi tanpa alasan yang jelas.
3. Buat database SQL, source code, unit test, README, laporan, dan outline presentasi.
4. Pastikan query, nama tabel, relasi, dan nama file konsisten di seluruh proyek.
5. Jalankan unit test dan pemeriksaan yang memungkinkan di lingkungan kerja. Jangan mengklaim pengujian berhasil jika belum dijalankan.
6. Jika ada pengujian atau fitur yang tidak dapat dijalankan karena keterbatasan lingkungan, jelaskan langkah manual untuk memverifikasinya.
7. Di akhir, tampilkan ringkasan fitur, struktur file, cara menjalankan aplikasi, cara menjalankan test, dan bagian yang belum terverifikasi.
