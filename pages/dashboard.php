<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$reservationModel = new Reservation($db);
$stats = [
    ['label' => 'Pelanggan', 'value' => (new Customer($db))->count(),        'color' => 'primary', 'page' => 'customers'],
    ['label' => 'Meja',      'value' => (new RestaurantTable($db))->count(), 'color' => 'success', 'page' => 'tables'],
    ['label' => 'Reservasi', 'value' => $reservationModel->count(),          'color' => 'warning', 'page' => 'reservations'],
    ['label' => 'Pesanan',   'value' => (new Order($db))->count(),           'color' => 'danger',  'page' => 'orders'],
];
$latestReservations = $reservationModel->latest(5);

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Dashboard</h1>

<div class="row g-3 mb-4">
    <?php foreach ($stats as $stat): ?>
        <div class="col-6 col-md-3">
            <a href="<?= e(pageUrl($stat['page'])) ?>" class="text-decoration-none">
                <div class="card stat-card border-<?= e($stat['color']) ?> shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted">Jumlah <?= e($stat['label']) ?></div>
                        <div class="display-6 text-<?= e($stat['color']) ?>"><?= (int) $stat['value'] ?></div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Reservasi Terbaru</strong>
        <a href="<?= e(pageUrl('reservations')) ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th>Pelanggan</th>
                <th>Meja</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Tamu</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($latestReservations)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">Belum ada reservasi.</td></tr>
            <?php endif; ?>
            <?php foreach ($latestReservations as $reservation): ?>
                <tr>
                    <td><?= e($reservation['customer_name']) ?></td>
                    <td><?= e($reservation['table_number']) ?></td>
                    <td class="text-nowrap"><?= e(formatDate($reservation['reservation_date'])) ?></td>
                    <td><?= e(formatTime($reservation['reservation_time'])) ?></td>
                    <td><?= (int) $reservation['guest_count'] ?></td>
                    <td><span class="badge text-bg-<?= e(statusBadgeClass($reservation['status'])) ?>"><?= e($reservation['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
