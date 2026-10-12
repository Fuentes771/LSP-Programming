<?php
// Titik masuk aplikasi: memuat konfigurasi, fungsi, class, lalu halaman sesuai parameter ?page=
session_start();
define('APP_RUNNING', true);
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Customer.php';
require_once __DIR__ . '/classes/RestaurantTable.php';
require_once __DIR__ . '/classes/MenuItem.php';
require_once __DIR__ . '/classes/Reservation.php';
require_once __DIR__ . '/classes/Order.php';

// Daftar halaman yang diizinkan (whitelist) => [judul menu navigasi, ikon Bootstrap Icons]
$pages = [
    'dashboard'    => ['Dashboard', 'bi-speedometer2'],
    'customers'    => ['Pelanggan', 'bi-people'],
    'tables'       => ['Meja', 'bi-grid-3x3-gap'],
    'menu'         => ['Menu', 'bi-journal-text'],
    'reservations' => ['Reservasi', 'bi-calendar-check'],
    'orders'       => ['Pesanan', 'bi-receipt'],
    'reports'      => ['Laporan', 'bi-bar-chart-line'],
];

$page = $_GET['page'] ?? 'dashboard';
if (!is_string($page) || !array_key_exists($page, $pages)) {
    $page = 'dashboard';
}

$db = getConnection();

// Output ditampung agar redirect tetap bisa dilakukan dan error dapat ditampilkan dengan rapi
ob_start();
try {
    require __DIR__ . '/pages/' . $page . '.php';
} catch (PDOException $e) {
    ob_clean();
    error_log('Kesalahan database: ' . $e->getMessage());
    $pageTitle = 'Terjadi Kesalahan';
    require __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-danger">Terjadi kesalahan saat mengakses database. Silakan coba lagi.</div>';
    require __DIR__ . '/includes/footer.php';
}
ob_end_flush();
