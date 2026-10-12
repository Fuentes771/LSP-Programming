<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$startDate = is_string($_GET['start_date'] ?? null) ? $_GET['start_date'] : date('Y-m-01');
$endDate = is_string($_GET['end_date'] ?? null) ? $_GET['end_date'] : date('Y-m-d');
$errors = validateDateRange($startDate, $endDate);

$reservations = [];
$orders = [];
if (!$errors) {
    $reservations = (new Reservation($db))->between($startDate, $endDate);
    $orders = (new Order($db))->between($startDate, $endDate);
}

// Pesanan yang dibatalkan tidak dihitung dalam total nilai pesanan
$validOrders = array_filter($orders, fn(array $order) => $order['status'] !== 'Dibatalkan');
$totalOrderValue = round(array_sum(array_map('floatval', array_column($validOrders, 'total_amount'))), 2);

$pageTitle = 'Laporan';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Laporan Reservasi &amp; Pesanan</h1>
    <button type="button" class="btn btn-outline-dark no-print" onclick="window.print()">Cetak Laporan</button>
</div>

<form method="get" class="card card-body shadow-sm mb-4 no-print">
    <input type="hidden" name="page" value="reports">
    <div class="row g-3 align-items-end">
        <div class="col-sm-5 col-md-4">
            <label for="start_date" class="form-label">Dari Tanggal</label>
            <input type="date" class="form-control" id="start_date" name="start_date" required value="<?= e($startDate) ?>">
        </div>
        <div class="col-sm-5 col-md-4">
            <label for="end_date" class="form-label">Sampai Tanggal</label>
            <input type="date" class="form-control" id="end_date" name="end_date" required value="<?= e($endDate) ?>">
        </div>
        <div class="col-sm-2 col-md-4">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </div>
    </div>
</form>

<?php if ($errors): ?>
    <div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div>
<?php else: ?>
    <p class="text-muted">Periode: <strong><?= e(formatDate($startDate)) ?></strong> s.d. <strong><?= e(formatDate($endDate)) ?></strong></p>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100"><div class="card-body">
                <div class="text-muted">Jumlah Reservasi</div>
                <div class="display-6"><?= count($reservations) ?></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100"><div class="card-body">
                <div class="text-muted">Jumlah Pesanan</div>
                <div class="display-6"><?= count($orders) ?></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100"><div class="card-body">
                <div class="text-muted">Total Nilai Pesanan</div>
                <div class="display-6"><?= e(formatRupiah($totalOrderValue)) ?></div>
                <div class="small text-muted">Tidak termasuk pesanan Dibatalkan</div>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><strong>Laporan Reservasi</strong></div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Pelanggan</th>
                    <th>Meja</th>
                    <th class="text-center">Tamu</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($reservations)): ?>
                    <tr><td colspan="7" class="text-center text-muted">Tidak ada reservasi pada periode ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($reservations as $i => $reservation): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e(formatDate($reservation['reservation_date'])) ?></td>
                        <td><?= e(formatTime($reservation['reservation_time'])) ?></td>
                        <td><?= e($reservation['customer_name']) ?></td>
                        <td><?= e($reservation['table_number']) ?></td>
                        <td class="text-center"><?= (int) $reservation['guest_count'] ?></td>
                        <td><?= e($reservation['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Laporan Pesanan</strong></div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>No. Pesanan</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Status</th>
                    <th class="text-end">Total</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Tidak ada pesanan pada periode ini.</td></tr>
                <?php endif; ?>
                <?php foreach (array_values($orders) as $i => $order): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>#<?= (int) $order['id'] ?></td>
                        <td><?= e(formatDate($order['order_date'])) ?></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= e($order['status']) ?></td>
                        <td class="text-end"><?= e(formatRupiah($order['total_amount'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="table-light">
                    <th colspan="5" class="text-end">Total Nilai Pesanan (tanpa Dibatalkan)</th>
                    <th class="text-end"><?= e(formatRupiah($totalOrderValue)) ?></th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
