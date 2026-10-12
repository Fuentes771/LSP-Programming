<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$reservationModel = new Reservation($db);
$tableModel = new RestaurantTable($db);
$errors = [];
$form = [
    'id' => '', 'customer_id' => '', 'table_id' => '', 'reservation_date' => date('Y-m-d'),
    'reservation_time' => '', 'guest_count' => '', 'status' => 'Menunggu', 'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'cancel') {
        $errors = $reservationModel->cancel((int) input('id'));
        $errors ? setFlash('danger', implode(' ', $errors)) : setFlash('success', 'Reservasi berhasil dibatalkan.');
        redirect(pageUrl('reservations'));
    }

    foreach (array_keys($form) as $field) {
        $form[$field] = input($field);
    }
    $id = $form['id'] !== '' ? (int) $form['id'] : null;
    $errors = $reservationModel->save($form, $id);
    if (!$errors) {
        setFlash('success', $id ? 'Reservasi berhasil diubah.' : 'Reservasi berhasil dibuat.');
        redirect(pageUrl('reservations'));
    }
} elseif (isset($_GET['edit'])) {
    $reservation = $reservationModel->find((int) $_GET['edit']);
    if ($reservation === null) {
        setFlash('danger', 'Data reservasi tidak ditemukan.');
        redirect(pageUrl('reservations'));
    }
    $form = array_map('strval', $reservation);
    $form['reservation_time'] = formatTime($form['reservation_time']);
}

$isEdit = $form['id'] !== '';
$customers = (new Customer($db))->all();
$tables = $tableModel->active();

// Saat mengubah reservasi lama, meja yang sudah Nonaktif tetap ditampilkan sebagai pilihan
if ($form['table_id'] !== '' && !in_array($form['table_id'], array_map('strval', array_column($tables, 'id')), true)) {
    $currentTable = $tableModel->find((int) $form['table_id']);
    if ($currentTable !== null) {
        $tables[] = $currentTable;
    }
}
$reservations = $reservationModel->all();

$pageTitle = 'Reservasi Meja';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Reservasi Meja</h1>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong><?= $isEdit ? 'Ubah Reservasi' : 'Buat Reservasi' ?></strong></div>
            <div class="card-body">
                <?= showErrors($errors) ?>
                <form method="post">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= e($form['id']) ?>">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Pelanggan</label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">-- Pilih Pelanggan --</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= (int) $customer['id'] ?>" <?= $form['customer_id'] === (string) $customer['id'] ? 'selected' : '' ?>>
                                    <?= e($customer['name']) ?> (<?= e($customer['phone']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="table_id" class="form-label">Meja</label>
                        <select class="form-select" id="table_id" name="table_id" required>
                            <option value="">-- Pilih Meja --</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?= (int) $table['id'] ?>" <?= $form['table_id'] === (string) $table['id'] ? 'selected' : '' ?>>
                                    <?= e($table['table_number']) ?> - kapasitas <?= (int) $table['capacity'] ?> orang<?= $table['status'] !== 'Aktif' ? ' (Nonaktif)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="reservation_date" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="reservation_date" name="reservation_date" required value="<?= e($form['reservation_date']) ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="reservation_time" class="form-label">Waktu</label>
                            <input type="time" class="form-control" id="reservation_time" name="reservation_time" required value="<?= e($form['reservation_time']) ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label for="guest_count" class="form-label">Jumlah Tamu</label>
                            <input type="number" class="form-control" id="guest_count" name="guest_count" min="1" required value="<?= e($form['guest_count']) ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach (RESERVATION_STATUSES as $status): ?>
                                    <option value="<?= e($status) ?>" <?= $form['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"><?= e($form['notes']) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <?php if ($isEdit): ?>
                        <a href="<?= e(pageUrl('reservations')) ?>" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong>Daftar Reservasi</strong></div>
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
                        <th>Catatan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">Belum ada reservasi.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?= e($reservation['customer_name']) ?></td>
                            <td><?= e($reservation['table_number']) ?></td>
                            <td class="text-nowrap"><?= e(formatDate($reservation['reservation_date'])) ?></td>
                            <td><?= e(formatTime($reservation['reservation_time'])) ?></td>
                            <td><?= (int) $reservation['guest_count'] ?></td>
                            <td><span class="badge text-bg-<?= e(statusBadgeClass($reservation['status'])) ?>"><?= e($reservation['status']) ?></span></td>
                            <td><?= e($reservation['notes'] ?? '-') ?></td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(pageUrl('reservations', ['edit' => $reservation['id']])) ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
                                <?php if (in_array($reservation['status'], ACTIVE_RESERVATION_STATUSES, true)): ?>
                                    <?= actionButton('cancel', (int) $reservation['id'], 'Batalkan', 'btn-outline-danger', 'Batalkan reservasi ' . $reservation['customer_name'] . '?') ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
