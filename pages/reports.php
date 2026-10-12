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

$today = date('Y-m-d');
$periods = [
    'Hari Ini'   => [$today, $today],
    '7 Hari'     => [date('Y-m-d', strtotime('-6 days')), $today],
    'Bulan Ini'  => [date('Y-m-01'), $today],
    'Bulan Lalu' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
];
echo pageHeader('Laporan Reservasi & Pesanan', 'bi-bar-chart-line', 'Pilih periode lalu cetak melalui browser.',
    '<button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak Laporan</button>');
?>

<form method="get" class="card card-body mb-4 no-print">
    <input type="hidden" name="page" value="reports">
    <div class="row g-3 align-items-end">
        <div class="col-sm-6 col-md-3">
            <label for="start_date" class="form-label">Dari Tanggal</label>
            <input type="date" class="form-control" id="start_date" name="start_date" required value="<?= e($startDate) ?>">
        </div>
        <div class="col-sm-6 col-md-3">
            <label for="end_date" class="form-label">Sampai Tanggal</label>
            <input type="date" class="form-control" id="end_date" name="end_date" required value="<?= e($endDate) ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
        </div>
        <div class="col-md-4 d-flex flex-wrap gap-1">
            <?php foreach ($periods as $label => [$from, $to]): ?>
                <a class="btn btn-sm <?= $startDate === $from && $endDate === $to ? 'btn-dark' : 'btn-outline-secondary' ?>"
                   href="<?= e(pageUrl('reports', ['start_date' => $from, 'end_date' => $to])) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</form>

<?php if ($errors): ?>
    <div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div>
<?php else: ?>
    <p class="text-muted">Periode: <strong><?= e(formatDate($startDate)) ?></strong> s.d. <strong><?= e(formatDate($endDate)) ?></strong></p>

    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Jumlah Reservasi', (string) count($reservations), 'bi-calendar-check', ''],
            ['Jumlah Pesanan', (string) count($orders), 'bi-receipt', ''],
            ['Total Nilai Pesanan', formatRupiah($totalOrderValue), 'bi-cash-coin', 'Tidak termasuk pesanan Dibatalkan'],
        ] as [$label, $value, $icon, $note]): ?>
            <div class="col-md-4">
                <div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-subtle text-primary-emphasis"><i class="bi <?= e($icon) ?>"></i></div>
                    <div>
                        <div class="text-muted small"><?= e($label) ?></div>
                        <div class="stat-value"><?= e($value) ?></div>
                        <?php if ($note !== ''): ?><div class="small text-muted"><?= e($note) ?></div><?php endif; ?>
                    </div>
                </div></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-calendar-check me-1"></i>Laporan Reservasi</div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead>
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
                        <td><?= statusBadge($reservation['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="bi bi-receipt me-1"></i>Laporan Pesanan</div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead>
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
                        <td><?= statusBadge($order['status']) ?></td>
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
