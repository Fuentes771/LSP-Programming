<?php
// Unit test sederhana tanpa framework. Jalankan: php tests/test_functions.php

require_once __DIR__ . '/../includes/functions.php';

$passed = 0;
$failed = 0;

function check(string $label, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] {$label}" . PHP_EOL;
    } else {
        $failed++;
        echo "[FAIL] {$label}" . PHP_EOL;
    }
}

function section(string $title): void
{
    echo PHP_EOL . "== {$title} ==" . PHP_EOL;
}

// ---------------------------------------------------------------------------
section('Perhitungan subtotal dan total pesanan');

check('Subtotal 25.000 x 2 = 50.000', calculateSubtotal(25000, 2) === 50000.0);
check('Subtotal menerima input string ("15000", "3") = 45.000', calculateSubtotal('15000', '3') === 45000.0);
check('Subtotal harga desimal 12.500,50 x 3 = 37.501,50', calculateSubtotal(12500.50, 3) === 37501.5);

$items = [
    ['price' => 25000, 'quantity' => 2],
    ['price' => 20000, 'quantity' => 1],
    ['price' => 5000,  'quantity' => 3],
];
check('Total 3 item (50.000 + 20.000 + 15.000) = 85.000', calculateOrderTotal($items) === 85000.0);
check('Total pesanan tanpa item = 0', calculateOrderTotal([]) === 0.0);

$menus = [
    1 => ['id' => 1, 'name' => 'Nasi Goreng', 'price' => '25000.00'],
    2 => ['id' => 2, 'name' => 'Es Teh', 'price' => '5000.00'],
];
$result = buildOrderItems([1 => '2', 2 => '0'], $menus);
check('buildOrderItems mengabaikan kuantitas 0', count($result['items']) === 1 && empty($result['errors']));
check('buildOrderItems memakai harga dari data menu', $result['items'][0]['price'] === 25000.0);
check('buildOrderItems menghitung subtotal item', $result['items'][0]['subtotal'] === 50000.0);
check('buildOrderItems menolak jika tidak ada menu dipilih', !empty(buildOrderItems([1 => '', 2 => '0'], $menus)['errors']));
check('buildOrderItems menolak kuantitas negatif', !empty(buildOrderItems([1 => '-1'], $menus)['errors']));
check('buildOrderItems menolak menu yang tidak tersedia', !empty(buildOrderItems([99 => '1'], $menus)['errors']));

// ---------------------------------------------------------------------------
section('Validasi jumlah tamu terhadap kapasitas meja');

check('4 tamu pada meja kapasitas 4 diterima', validateGuestCount(4, 4) === true);
check('2 tamu pada meja kapasitas 6 diterima', validateGuestCount('2', 6) === true);
check('5 tamu pada meja kapasitas 4 ditolak', validateGuestCount(5, 4) === false);
check('0 tamu ditolak', validateGuestCount(0, 4) === false);
check('Jumlah tamu negatif ditolak', validateGuestCount('-2', 4) === false);

// ---------------------------------------------------------------------------
section('Validasi harga dan kuantitas');

check('Harga 15000 valid', validatePrice('15000') === true);
check('Harga desimal 12500.50 valid', validatePrice('12500.50') === true);
check('Harga 0 ditolak', validatePrice('0') === false);
check('Harga negatif ditolak', validatePrice('-5000') === false);
check('Harga bukan angka ditolak', validatePrice('abc') === false);
check('Harga kosong ditolak', validatePrice('') === false);
check('Kuantitas 3 valid', validateQuantity('3') === true);
check('Kuantitas 0 ditolak', validateQuantity('0') === false);
check('Kuantitas negatif ditolak', validateQuantity('-1') === false);
check('Kuantitas desimal 1.5 ditolak', validateQuantity('1.5') === false);
check('Kuantitas bukan angka ditolak', validateQuantity('dua') === false);

// ---------------------------------------------------------------------------
section('Penolakan reservasi bentrok');

$existing = [
    ['id' => 1, 'table_id' => 2, 'reservation_date' => '2026-10-20', 'reservation_time' => '19:00:00', 'status' => 'Menunggu'],
    ['id' => 2, 'table_id' => 3, 'reservation_date' => '2026-10-20', 'reservation_time' => '19:00:00', 'status' => 'Dibatalkan'],
    ['id' => 3, 'table_id' => 1, 'reservation_date' => '2026-10-20', 'reservation_time' => '12:00:00', 'status' => 'Dikonfirmasi'],
];

check('Meja, tanggal, dan waktu sama (format HH:MM dari form) ditolak', hasReservationConflict($existing, 2, '2026-10-20', '19:00') === true);
check('Bentrok dengan reservasi Dikonfirmasi ditolak', hasReservationConflict($existing, 1, '2026-10-20', '12:00:00') === true);
check('Reservasi lama berstatus Dibatalkan tidak dianggap bentrok', hasReservationConflict($existing, 3, '2026-10-20', '19:00') === false);
check('Meja berbeda pada waktu sama diterima', hasReservationConflict($existing, 4, '2026-10-20', '19:00') === false);
check('Waktu berbeda pada meja sama diterima', hasReservationConflict($existing, 2, '2026-10-20', '20:00') === false);
check('Tanggal berbeda pada meja sama diterima', hasReservationConflict($existing, 2, '2026-10-21', '19:00') === false);
check('Mengubah reservasi itu sendiri tidak dianggap bentrok', hasReservationConflict($existing, 2, '2026-10-20', '19:00', 1) === false);

// ---------------------------------------------------------------------------
section('Validasi data lain');

check('Data pelanggan valid tanpa email diterima', validateCustomerData(['name' => 'Budi', 'phone' => '081234567890', 'email' => '']) === []);
check('Email pelanggan tidak valid ditolak', validateCustomerData(['name' => 'Budi', 'phone' => '081234567890', 'email' => 'budi@']) !== []);
check('Nama pelanggan kosong ditolak', validateCustomerData(['name' => '', 'phone' => '081234567890', 'email' => '']) !== []);
check('Tanggal 2026-02-30 ditolak', isValidDate('2026-02-30') === false);
check('Waktu 25:00 ditolak', isValidTime('25:00') === false);
check('Rentang tanggal terbalik ditolak', validateDateRange('2026-10-31', '2026-10-01') !== []);
check('Escape HTML mencegah tag script', e('<script>alert(1)</script>') === '&lt;script&gt;alert(1)&lt;/script&gt;');

// ---------------------------------------------------------------------------
$total = $passed + $failed;
echo PHP_EOL . "Hasil: {$passed} PASS, {$failed} FAIL dari {$total} pengujian." . PHP_EOL;

exit($failed > 0 ? 1 : 0);
