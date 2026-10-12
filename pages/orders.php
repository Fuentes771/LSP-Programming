<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$orderModel = new Order($db);
$menuModel = new MenuItem($db);
$customerModel = new Customer($db);
$today = date('Y-m-d');
$view = query('action', 'list');
$statusFilter = in_array(query('status'), ORDER_STATUSES, true) ? query('status') : '';
$errors = [];
$form = ['customer_id' => '', 'reservation_id' => '', 'order_date' => $today];
$quantities = [];
$customerMode = 'existing';
$newCustomer = ['name' => '', 'phone' => '', 'email' => ''];
$orderType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'status') {
        $id = (int) input('id');
        $newStatus = input('status');
        $result = $orderModel->updateStatus($id, $newStatus);
        $message = $newStatus === 'Selesai' ? 'Pesanan ditandai selesai.' : 'Pesanan dibatalkan.';
        if ($result['reservation_completed']) {
            $message .= ' Reservasi terkait otomatis ditandai selesai.';
        }
        flashResult($result['errors'], $message);
        redirect(pageUrl('orders', ['action' => 'view', 'id' => $id]));
    }

    foreach (array_keys($form) as $field) {
        $form[$field] = input($field);
    }
    $quantities = is_array($_POST['quantity'] ?? null) ? $_POST['quantity'] : [];
    $orderType = input('order_type') === 'reservation' ? 'reservation' : 'walkin';
    $orderId = null;

    if ($orderType === 'reservation') {
        // Pesanan dari reservasi: pelanggan, meja, dan tanggal diambil dari reservasi
        if ($form['reservation_id'] === '') {
            $errors = ['Pilih reservasi yang sudah Dikonfirmasi, atau pilih jenis pesanan "Tanpa reservasi".'];
        } else {
            $created = $orderModel->create(['customer_id' => '', 'reservation_id' => $form['reservation_id'], 'order_date' => ''], $quantities, $menuModel);
            $errors = $created['errors'];
            $orderId = $created['id'];
        }
    } else {
        // Pesanan tanpa reservasi (walk-in): pelanggan terdaftar atau pelanggan baru
        $form['reservation_id'] = '';
        $customerMode = input('customer_mode') === 'new' ? 'new' : 'existing';
        $newCustomer = ['name' => input('new_customer_name'), 'phone' => input('new_customer_phone'), 'email' => input('new_customer_email')];
        $result = $customerModel->runWithCustomer($customerMode, $form['customer_id'], $newCustomer,
            function (string $customerId) use ($form, $quantities, $orderModel, $menuModel, &$orderId): array {
                $created = $orderModel->create(['customer_id' => $customerId] + $form, $quantities, $menuModel);
                $orderId = $created['id'];
                return $created['errors'];
            });
        $errors = $result['errors'];
    }

    if (!$errors) {
        $successMessage = 'Pesanan berhasil disimpan.';
        if ($orderType === 'walkin' && $customerMode === 'new') {
            $successMessage .= ' Pelanggan baru "' . $newCustomer['name'] . '" ditambahkan ke data Pelanggan.';
        }
        setFlash('success', $successMessage);
        redirect(pageUrl('orders', ['action' => 'view', 'id' => $orderId]));
    }
    $view = 'create';
}

if ($view === 'view') {
    $order = $orderModel->findWithItems((int) query('id'));
    if ($order === null) {
        setFlash('danger', 'Data pesanan tidak ditemukan.');
        redirect(pageUrl('orders'));
    }
    $pageTitle = 'Detail Pesanan #' . $order['id'];
} elseif ($view === 'create') {
    $customers = $customerModel->all();
    $reservations = (new Reservation($db))->forOrder();
    $menus = $menuModel->available();

    // Dibuka dari tombol "Pesanan" di halaman reservasi: reservasi langsung terpilih
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && query('reservation_id') !== '') {
        $form['reservation_id'] = query('reservation_id');
        $orderType = 'reservation';
    }
    // Jenis pesanan default: dari reservasi jika ada reservasi yang siap dipesan
    if ($orderType === '') {
        $orderType = $reservations ? 'reservation' : 'walkin';
    }
    $pageTitle = 'Buat Pesanan';
} else {
    $view = 'list';
    $orders = $orderModel->all($statusFilter);
    $pageTitle = 'Pesanan';
}

require __DIR__ . '/../includes/header.php';
?>

<?php if ($view === 'list'): ?>
    <?= pageHeader('Daftar Pesanan', 'bi-receipt', 'Alur status: Diproses → Selesai, atau Dibatalkan.',
        '<a href="' . e(pageUrl('orders', ['action' => 'create'])) . '" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Pesanan</a>') ?>

    <ul class="nav nav-pills mb-3">
        <?php foreach (['' => 'Semua'] + array_combine(ORDER_STATUSES, ORDER_STATUSES) as $key => $label): ?>
            <li class="nav-item">
                <a class="nav-link<?= $statusFilter === $key ? ' active' : '' ?>" href="<?= e(pageUrl('orders', array_filter(['status' => $key]))) ?>"><?= e($label) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="card">
        <?php if (empty($orders)): ?>
            <?= emptyState('Belum ada pesanan pada filter ini.',
                '<a href="' . e(pageUrl('orders', ['action' => 'create'])) . '" class="btn btn-sm btn-primary">Buat Pesanan</a>') ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>No.</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Reservasi</th>
                        <th class="text-center">Item</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $row): ?>
                        <tr>
                            <td class="fw-semibold">#<?= (int) $row['id'] ?></td>
                            <td class="text-nowrap"><?= e(formatDate($row['order_date'])) ?></td>
                            <td><?= e($row['customer_name']) ?></td>
                            <td class="small text-muted text-nowrap">
                                <?= $row['reservation_id'] ? 'Meja ' . e($row['table_number']) . ' · ' . e(formatTime($row['reservation_time'])) : 'Tanpa reservasi' ?>
                            </td>
                            <td class="text-center"><?= (int) $row['item_count'] ?></td>
                            <td class="text-end fw-semibold text-nowrap"><?= e(formatRupiah($row['total_amount'])) ?></td>
                            <td><?= statusBadge($row['status']) ?></td>
                            <td class="text-end">
                                <a href="<?= e(pageUrl('orders', ['action' => 'view', 'id' => $row['id']])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($view === 'create'): ?>
    <?= pageHeader('Buat Pesanan', 'bi-plus-square', 'Pilih reservasi yang sudah dikonfirmasi (pelanggan & meja otomatis) atau pesanan tanpa reservasi, lalu isi menu.') ?>
    <?= showErrors($errors) ?>

    <form method="post" id="order-form">
        <input type="hidden" name="action" value="create">
        <div class="row g-4">
            <div class="col-lg-8">
                <?php
                $selectedReservation = null;
                foreach ($reservations as $reservation) {
                    if ((string) $reservation['id'] === $form['reservation_id']) {
                        $selectedReservation = $reservation;
                    }
                }
                ?>
                <div class="card mb-4">
                    <div class="card-header"><i class="bi bi-person-lines-fill me-1"></i>Data Pesanan</div>
                    <div class="card-body">
                        <div class="btn-group w-100 mb-3" role="group" aria-label="Jenis pesanan">
                            <input type="radio" class="btn-check" name="order_type" id="order_type_reservation" value="reservation" <?= $orderType === 'reservation' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="order_type_reservation"><i class="bi bi-calendar-check me-1"></i>Dari reservasi</label>
                            <input type="radio" class="btn-check" name="order_type" id="order_type_walkin" value="walkin" <?= $orderType === 'walkin' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="order_type_walkin"><i class="bi bi-person-walking me-1"></i>Tanpa reservasi (walk-in)</label>
                        </div>

                        <fieldset data-order-section="reservation">
                            <?php if (empty($reservations)): ?>
                                <div class="alert alert-warning small mb-0">
                                    <i class="bi bi-info-circle me-1"></i>Belum ada reservasi berstatus <strong>Dikonfirmasi</strong> untuk hari ini.
                                    Konfirmasi reservasi tamu yang sudah datang di halaman <a href="<?= e(pageUrl('reservations', ['scope' => 'today'])) ?>">Reservasi</a>, atau pilih "Tanpa reservasi".
                                </div>
                            <?php else: ?>
                                <label for="reservation_id" class="form-label">Reservasi</label>
                                <select class="form-select" id="reservation_id" name="reservation_id" required>
                                    <option value="">-- Pilih Reservasi yang Sudah Dikonfirmasi --</option>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <option value="<?= (int) $reservation['id'] ?>"
                                                data-customer="<?= e($reservation['customer_name']) ?>"
                                                data-phone="<?= e($reservation['customer_phone']) ?>"
                                                data-table="<?= e($reservation['table_number']) ?>"
                                                data-schedule="<?= e(formatDate($reservation['reservation_date']) . ' ' . formatTime($reservation['reservation_time'])) ?>"
                                                data-guests="<?= (int) $reservation['guest_count'] ?>"
                                            <?= $form['reservation_id'] === (string) $reservation['id'] ? 'selected' : '' ?>>
                                            <?= e($reservation['customer_name']) ?> · Meja <?= e($reservation['table_number']) ?> · <?= e(formatDate($reservation['reservation_date'])) ?> <?= e(formatTime($reservation['reservation_time'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Hanya reservasi berstatus Dikonfirmasi. Pelanggan, meja, dan tanggal pesanan otomatis mengikuti reservasi.</div>

                                <div id="reservation-info" class="row g-2 mt-2 small" <?= $selectedReservation ? '' : 'hidden' ?>>
                                    <?php foreach ([
                                        ['customer', 'bi-person', 'Pelanggan', $selectedReservation ? $selectedReservation['customer_name'] . ' (' . $selectedReservation['customer_phone'] . ')' : ''],
                                        ['table', 'bi-grid-3x3-gap', 'Meja', $selectedReservation['table_number'] ?? ''],
                                        ['schedule', 'bi-clock', 'Jadwal', $selectedReservation ? formatDate($selectedReservation['reservation_date']) . ' ' . formatTime($selectedReservation['reservation_time']) : ''],
                                        ['guests', 'bi-people', 'Tamu', $selectedReservation ? $selectedReservation['guest_count'] . ' orang' : ''],
                                    ] as [$field, $icon, $label, $value]): ?>
                                        <div class="col-6 col-md-3">
                                            <div class="border rounded-3 p-2 bg-body-tertiary h-100">
                                                <div class="text-muted"><i class="bi <?= e($icon) ?> me-1"></i><?= e($label) ?></div>
                                                <div class="fw-semibold" data-info="<?= e($field) ?>"><?= e($value) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </fieldset>

                        <fieldset data-order-section="walkin">
                            <?php require __DIR__ . '/../includes/customer_picker.php'; ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="order_date" class="form-label">Tanggal Pesanan</label>
                                    <input type="date" class="form-control" id="order_date" name="order_date" required max="<?= e($today) ?>" value="<?= e($form['order_date']) ?>">
                                    <div class="form-text">Tidak boleh melebihi hari ini.</div>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-journal-text me-1"></i>Pilih Menu</div>
                    <?php if (empty($menus)): ?>
                        <?= emptyState('Tidak ada menu yang tersedia.', '<a href="' . e(pageUrl('menu')) . '" class="btn btn-sm btn-primary">Kelola Menu</a>') ?>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Menu</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($menus as $menu): ?>
                                    <?php $qty = $quantities[$menu['id']] ?? ''; ?>
                                    <tr class="menu-row" data-price="<?= e($menu['price']) ?>">
                                        <td><label for="qty-<?= (int) $menu['id'] ?>" class="fw-semibold"><?= e($menu['name']) ?></label></td>
                                        <td class="text-end text-nowrap"><?= e(formatRupiah($menu['price'])) ?></td>
                                        <td>
                                            <div class="input-group input-group-sm qty-group mx-auto">
                                                <button type="button" class="btn btn-outline-secondary qty-minus" aria-label="Kurangi"><i class="bi bi-dash"></i></button>
                                                <input type="number" class="form-control qty-input" min="0" max="<?= MAX_ORDER_QUANTITY ?>" step="1"
                                                       id="qty-<?= (int) $menu['id'] ?>" name="quantity[<?= (int) $menu['id'] ?>]"
                                                       value="<?= e(is_scalar($qty) ? $qty : '') ?>" placeholder="0">
                                                <button type="button" class="btn btn-outline-secondary qty-plus" aria-label="Tambah"><i class="bi bi-plus"></i></button>
                                            </div>
                                        </td>
                                        <td class="text-end text-nowrap row-subtotal">-</td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card order-summary">
                    <div class="card-header"><i class="bi bi-calculator me-1"></i>Ringkasan</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Jumlah item</span>
                            <span id="order-items">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-top pt-2 mb-3">
                            <span class="fw-semibold">Total</span>
                            <span class="fs-4 fw-bold text-primary" id="order-total">Rp 0</span>
                        </div>
                        <p class="small text-muted">Pratinjau ini dihitung di browser. Total final dihitung ulang oleh sistem dari harga menu di database.</p>
                        <button type="submit" class="btn btn-primary w-100 mb-2"><i class="bi bi-check2-circle me-1"></i>Simpan Pesanan</button>
                        <a href="<?= e(pageUrl('orders')) ?>" class="btn btn-light w-100">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

<?php else: ?>
    <?php
    $actions = '<button type="button" class="btn btn-outline-secondary me-2" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button>'
        . '<a href="' . e(pageUrl('orders')) . '" class="btn btn-light">Kembali</a>';
    echo pageHeader('Pesanan #' . $order['id'], 'bi-receipt', 'Dibuat ' . formatDate($order['created_at']) . ' ' . formatTime(substr($order['created_at'], 11)), $actions);
    ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="text-muted small">Status</dt>
                        <dd><?= statusBadge($order['status']) ?></dd>
                        <dt class="text-muted small">Pelanggan</dt>
                        <dd class="fw-semibold"><?= e($order['customer_name']) ?></dd>
                        <dt class="text-muted small">Tanggal Pesanan</dt>
                        <dd><?= e(formatDate($order['order_date'])) ?></dd>
                        <dt class="text-muted small">Reservasi</dt>
                        <dd class="mb-0">
                            <?php if ($order['reservation_id']): ?>
                                Meja <?= e($order['table_number']) ?> · <?= e(formatDate($order['reservation_date'])) ?> <?= e(formatTime($order['reservation_time'])) ?>
                            <?php else: ?>
                                <span class="text-muted">Tanpa reservasi</span>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
            </div>
            <?php if (ORDER_TRANSITIONS[$order['status']] ?? []): ?>
                <div class="card no-print">
                    <div class="card-header"><i class="bi bi-arrow-repeat me-1"></i>Ubah Status</div>
                    <div class="card-body d-grid gap-2">
                        <?= actionButton('status', (int) $order['id'], 'Tandai Selesai', 'btn-success w-100', ($order['reservation_id'] ? 'Tandai pesanan ini selesai? Reservasi terkait ikut diselesaikan jika tidak ada pesanan lain yang masih diproses.' : 'Tandai pesanan ini selesai?'), ['status' => 'Selesai'], 'bi-check2-circle') ?>
                        <?= actionButton('status', (int) $order['id'], 'Batalkan Pesanan', 'btn-outline-danger w-100', 'Batalkan pesanan ini? Tindakan tidak dapat diubah kembali.', ['status' => 'Dibatalkan'], 'bi-x-circle') ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-light border small no-print">
                    <i class="bi bi-lock me-1"></i>Pesanan berstatus <?= e($order['status']) ?> sudah final dan tidak dapat diubah.
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><i class="bi bi-list-check me-1"></i>Item Pesanan</div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Menu</th>
                            <th class="text-end">Harga</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td><?= e($item['menu_name']) ?></td>
                                <td class="text-end"><?= e(formatRupiah($item['price'])) ?></td>
                                <td class="text-center"><?= (int) $item['quantity'] ?></td>
                                <td class="text-end"><?= e(formatRupiah($item['subtotal'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end fs-5 text-primary"><?= e(formatRupiah($order['total_amount'])) ?></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
