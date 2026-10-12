<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$reservationModel = new Reservation($db);
$tableModel = new RestaurantTable($db);
$customerModel = new Customer($db);
$today = date('Y-m-d');
$view = query('action', 'list');
$scope = in_array(query('scope'), ['today', 'upcoming', 'all'], true) ? query('scope') : 'upcoming';
$statusFilter = in_array(query('status'), RESERVATION_STATUSES, true) ? query('status') : '';
$errors = [];
$form = [
    'id' => '', 'customer_id' => '', 'table_id' => '', 'reservation_date' => $today,
    'reservation_time' => '', 'guest_count' => '', 'notes' => '',
];
$existing = null;
$customerMode = 'existing';
$newCustomer = ['name' => '', 'phone' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $listUrl = pageUrl('reservations', array_filter(['scope' => input('scope'), 'status' => input('status_filter')]));

    if (input('action') === 'status') {
        $newStatus = input('status');
        $messages = [
            'Dikonfirmasi' => 'Reservasi dikonfirmasi.',
            'Selesai'      => 'Reservasi ditandai selesai.',
            'Dibatalkan'   => 'Reservasi dibatalkan.',
        ];
        flashResult($reservationModel->changeStatus((int) input('id'), $newStatus), $messages[$newStatus] ?? 'Status reservasi diubah.');
        redirect($listUrl);
    }

    foreach (array_keys($form) as $field) {
        $form[$field] = input($field);
    }
    $id = $form['id'] !== '' ? (int) $form['id'] : null;
    if ($id === null) {
        // Reservasi baru: pelanggan bisa dipilih dari daftar atau langsung dibuat (pelanggan baru)
        $customerMode = input('customer_mode') === 'new' ? 'new' : 'existing';
        $newCustomer = ['name' => input('new_customer_name'), 'phone' => input('new_customer_phone'), 'email' => input('new_customer_email')];
        $result = $customerModel->runWithCustomer($customerMode, $form['customer_id'], $newCustomer,
            function (string $customerId) use ($form, $reservationModel): array {
                return $reservationModel->create(['customer_id' => $customerId] + $form);
            });
        $errors = $result['errors'];
    } else {
        $errors = $reservationModel->update($id, $form);
    }
    if (!$errors) {
        $successMessage = $id ? 'Reservasi berhasil diubah.' : 'Reservasi berhasil dibuat dengan status Menunggu.';
        if ($customerMode === 'new') {
            $successMessage .= ' Pelanggan baru "' . $newCustomer['name'] . '" ditambahkan ke data Pelanggan.';
        }
        setFlash('success', $successMessage);
        redirect(pageUrl('reservations', ['scope' => $form['reservation_date'] === $today ? 'today' : 'upcoming']));
    }
    $view = $id === null ? 'create' : 'edit';
    $existing = $id !== null ? $reservationModel->findWithRelations($id) : null;
} elseif ($view === 'edit') {
    $existing = $reservationModel->findWithRelations((int) query('id'));
    if ($existing === null) {
        setFlash('danger', 'Data reservasi tidak ditemukan.');
        redirect(pageUrl('reservations'));
    }
    if (!in_array($existing['status'], ACTIVE_RESERVATION_STATUSES, true)) {
        setFlash('danger', 'Reservasi yang sudah ' . $existing['status'] . ' tidak dapat diubah.');
        redirect(pageUrl('reservations'));
    }
    foreach (array_keys($form) as $field) {
        $form[$field] = (string) ($existing[$field] ?? '');
    }
    $form['reservation_time'] = formatTime($form['reservation_time']);
}

if ($view === 'create' || $view === 'edit') {
    $customers = $customerModel->all();
    $tables = $tableModel->active();
    $customerLocked = $existing !== null && (int) $existing['order_count'] > 0;
    $allowNewCustomer = $view === 'create';
    $pageTitle = $view === 'edit' ? 'Ubah Reservasi' : 'Buat Reservasi';
} else {
    $view = 'list';
    $reservations = $reservationModel->all($scope, $statusFilter);
    $pageTitle = 'Reservasi Meja';
}

require __DIR__ . '/../includes/header.php';
?>

<?php if ($view === 'list'): ?>
    <?= pageHeader('Reservasi Meja', 'bi-calendar-check', 'Alur status: Menunggu → Dikonfirmasi (tamu datang) → Selesai.',
        '<a href="' . e(pageUrl('reservations', ['action' => 'create'])) . '" class="btn btn-primary"><i class="bi bi-calendar-plus me-1"></i>Buat Reservasi</a>') ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <ul class="nav nav-pills">
            <?php foreach (['today' => 'Hari Ini', 'upcoming' => 'Akan Datang', 'all' => 'Semua'] as $key => $label): ?>
                <li class="nav-item">
                    <a class="nav-link<?= $scope === $key ? ' active' : '' ?>" href="<?= e(pageUrl('reservations', array_filter(['scope' => $key, 'status' => $statusFilter]))) ?>"><?= e($label) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <form method="get" class="d-flex gap-2">
            <input type="hidden" name="page" value="reservations">
            <input type="hidden" name="scope" value="<?= e($scope) ?>">
            <select name="status" class="form-select form-select-sm" aria-label="Filter status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                <?php foreach (RESERVATION_STATUSES as $status): ?>
                    <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button></noscript>
        </form>
    </div>

    <div class="card">
        <?php if (empty($reservations)): ?>
            <?= emptyState('Tidak ada reservasi pada filter ini.',
                '<a href="' . e(pageUrl('reservations', ['action' => 'create'])) . '" class="btn btn-sm btn-primary">Buat Reservasi</a>') ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Jadwal</th>
                        <th>Pelanggan</th>
                        <th>Meja</th>
                        <th>Tamu</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <?php
                        $rid = (int) $reservation['id'];
                        $status = $reservation['status'];
                        $extra = ['scope' => $scope, 'status_filter' => $statusFilter];
                        $isPastOrToday = $reservation['reservation_date'] <= $today;
                        $canOrder = in_array($status, ORDERABLE_RESERVATION_STATUSES, true) && $isPastOrToday
                            && $reservation['reservation_date'] >= date('Y-m-d', strtotime('-30 days'));
                        ?>
                        <tr>
                            <td class="text-nowrap">
                                <div class="fw-semibold"><?= e(formatDate($reservation['reservation_date'])) ?></div>
                                <div class="small text-muted"><i class="bi bi-clock me-1"></i><?= e(formatTime($reservation['reservation_time'])) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($reservation['customer_name']) ?></div>
                                <div class="small text-muted"><?= e($reservation['customer_phone']) ?></div>
                                <?php if (!empty($reservation['notes'])): ?>
                                    <div class="small text-muted fst-italic"><i class="bi bi-chat-left-text me-1"></i><?= e($reservation['notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap"><?= e($reservation['table_number']) ?> <span class="small text-muted">(<?= (int) $reservation['capacity'] ?> org)</span></td>
                            <td><?= (int) $reservation['guest_count'] ?></td>
                            <td>
                                <?= statusBadge($status) ?>
                                <?php if ((int) $reservation['order_count'] > 0): ?>
                                    <div class="small text-muted mt-1"><i class="bi bi-receipt me-1"></i><?= (int) $reservation['order_count'] ?> pesanan</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    <?php if ($status === 'Menunggu'): ?>
                                        <?= actionButton('status', $rid, 'Konfirmasi', 'btn-success', '', $extra + ['status' => 'Dikonfirmasi'], 'bi-check2') ?>
                                    <?php endif; ?>
                                    <?php if ($status === 'Dikonfirmasi' && $isPastOrToday): ?>
                                        <?= actionButton('status', $rid, 'Selesai', 'btn-outline-success', 'Tandai reservasi ini selesai?', $extra + ['status' => 'Selesai'], 'bi-flag') ?>
                                    <?php endif; ?>
                                    <?php if ($canOrder): ?>
                                        <a href="<?= e(pageUrl('orders', ['action' => 'create', 'reservation_id' => $rid])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-receipt me-1"></i>Pesanan</a>
                                    <?php endif; ?>
                                    <?php if (in_array($status, ACTIVE_RESERVATION_STATUSES, true)): ?>
                                        <a href="<?= e(pageUrl('reservations', ['action' => 'edit', 'id' => $rid])) ?>" class="btn btn-sm btn-outline-secondary" title="Ubah"><i class="bi bi-pencil"></i><span class="visually-hidden">Ubah</span></a>
                                        <?= actionButton('status', $rid, 'Batalkan', 'btn-outline-danger', 'Batalkan reservasi ' . $reservation['customer_name'] . '?', $extra + ['status' => 'Dibatalkan'], 'bi-x-lg', true) ?>
                                    <?php endif; ?>
                                    <?php if (in_array($status, ['Selesai', 'Dibatalkan'], true) && !$canOrder): ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <?= pageHeader($pageTitle, $view === 'edit' ? 'bi-pencil-square' : 'bi-calendar-plus',
        $view === 'edit' ? 'Status saat ini: ' . $existing['status'] : 'Reservasi baru otomatis berstatus Menunggu.') ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <?= showErrors($errors) ?>
                    <form method="post">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?= e($form['id']) ?>">

                        <?php require __DIR__ . '/../includes/customer_picker.php'; ?>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="guest_count" class="form-label">Jumlah Tamu</label>
                                <input type="number" class="form-control" id="guest_count" name="guest_count" min="1" required value="<?= e($form['guest_count']) ?>">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="table_id" class="form-label">Meja</label>
                                <select class="form-select" id="table_id" name="table_id" required>
                                    <option value="">-- Pilih Meja --</option>
                                    <?php foreach ($tables as $table): ?>
                                        <?php $label = $table['table_number'] . ' · kapasitas ' . $table['capacity'] . ' orang'; ?>
                                        <option value="<?= (int) $table['id'] ?>" data-capacity="<?= (int) $table['capacity'] ?>" data-label="<?= e($label) ?>"
                                            <?= $form['table_id'] === (string) $table['id'] ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Meja yang kapasitasnya kurang dari jumlah tamu otomatis tidak dapat dipilih.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reservation_date" class="form-label">Tanggal</label>
                                <input type="date" class="form-control" id="reservation_date" name="reservation_date" required
                                       min="<?= e(min($today, $existing['reservation_date'] ?? $today)) ?>" value="<?= e($form['reservation_date']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reservation_time" class="form-label">Jam Mulai</label>
                                <input type="time" class="form-control" id="reservation_time" name="reservation_time" required
                                       min="<?= e(OPENING_TIME) ?>" max="<?= e(latestReservationStart()) ?>" step="900" value="<?= e($form['reservation_time']) ?>">
                                <div class="form-text"><?= e(OPENING_TIME) ?>–<?= e(latestReservationStart()) ?>, kelipatan 15 menit.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Catatan <span class="text-muted">(opsional)</span></label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Contoh: ulang tahun, kursi bayi"><?= e($form['notes']) ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Reservasi</button>
                        <a href="<?= e(pageUrl('reservations')) ?>" class="btn btn-light">Batal</a>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-info-circle me-1"></i>Aturan Reservasi</div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item"><i class="bi bi-shop me-2 text-primary"></i>Restoran buka <?= e(OPENING_TIME) ?>–<?= e(CLOSING_TIME) ?>.</li>
                    <li class="list-group-item"><i class="bi bi-hourglass-split me-2 text-primary"></i>Setiap reservasi memakai meja selama <?= RESERVATION_DURATION_MINUTES / 60 ?> jam.</li>
                    <li class="list-group-item"><i class="bi bi-calendar-x me-2 text-primary"></i>Meja yang sama tidak bisa dipesan pada rentang waktu yang tumpang tindih.</li>
                    <li class="list-group-item"><i class="bi bi-people me-2 text-primary"></i>Jumlah tamu tidak boleh melebihi kapasitas meja.</li>
                    <li class="list-group-item"><i class="bi bi-clock-history me-2 text-primary"></i>Tanggal dan jam yang sudah lewat tidak dapat dipilih.</li>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
