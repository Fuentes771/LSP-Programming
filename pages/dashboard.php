<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$today = date('Y-m-d');
$reservationModel = new Reservation($db);
$orderModel = new Order($db);

$stats = [
    ['label' => 'Pelanggan', 'value' => (new Customer($db))->count(),        'icon' => 'bi-people',         'color' => 'primary', 'page' => 'customers'],
    ['label' => 'Meja',      'value' => (new RestaurantTable($db))->count(), 'icon' => 'bi-grid-3x3-gap',   'color' => 'success', 'page' => 'tables'],
    ['label' => 'Reservasi', 'value' => $reservationModel->count(),          'icon' => 'bi-calendar-check', 'color' => 'warning', 'page' => 'reservations'],
    ['label' => 'Pesanan',   'value' => $orderModel->count(),                'icon' => 'bi-receipt',        'color' => 'info',    'page' => 'orders'],
];
$todayStats = [
    ['label' => 'Reservasi hari ini',  'value' => (string) $reservationModel->countOn($today),        'icon' => 'bi-calendar-event'],
    ['label' => 'Pesanan diproses',    'value' => (string) $orderModel->countByStatus('Diproses'),    'icon' => 'bi-fire'],
    ['label' => 'Pendapatan hari ini', 'value' => formatRupiah($orderModel->revenueOn($today)),       'icon' => 'bi-cash-coin'],
];
$todayReservations = $reservationModel->all('today');

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';

$actions = '<a href="' . e(pageUrl('reservations', ['action' => 'create'])) . '" class="btn btn-primary me-2"><i class="bi bi-calendar-plus me-1"></i>Buat Reservasi</a>'
    . '<a href="' . e(pageUrl('orders', ['action' => 'create'])) . '" class="btn btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Buat Pesanan</a>';
echo pageHeader('Dashboard', 'bi-speedometer2', 'Ringkasan hari ini, ' . formatDate($today), $actions);
?>

<div class="row g-3 mb-3">
    <?php foreach ($stats as $stat): ?>
        <div class="col-6 col-lg-3">
            <a href="<?= e(pageUrl($stat['page'])) ?>" class="text-decoration-none">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon bg-<?= e($stat['color']) ?>-subtle text-<?= e($stat['color']) ?>-emphasis">
                            <i class="bi <?= e($stat['icon']) ?>"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total <?= e($stat['label']) ?></div>
                            <div class="stat-value text-body"><?= (int) $stat['value'] ?></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($todayStats as $stat): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi <?= e($stat['icon']) ?> fs-3 text-primary"></i>
                    <div>
                        <div class="text-muted small"><?= e($stat['label']) ?></div>
                        <div class="fs-5 fw-semibold"><?= e($stat['value']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-event me-1"></i> Reservasi Hari Ini</span>
        <a href="<?= e(pageUrl('reservations')) ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <?php if (empty($todayReservations)): ?>
        <?= emptyState('Tidak ada reservasi untuk hari ini.') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Jam</th>
                    <th>Pelanggan</th>
                    <th>Meja</th>
                    <th>Tamu</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($todayReservations as $reservation): ?>
                    <tr>
                        <td class="fw-semibold"><?= e(formatTime($reservation['reservation_time'])) ?></td>
                        <td><?= e($reservation['customer_name']) ?></td>
                        <td><?= e($reservation['table_number']) ?></td>
                        <td><?= (int) $reservation['guest_count'] ?> orang</td>
                        <td><?= statusBadge($reservation['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
