<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$orderModel = new Order($db);
$menuModel = new MenuItem($db);
$view = $_GET['action'] ?? 'list';
$errors = [];
$form = ['customer_id' => '', 'reservation_id' => '', 'order_date' => date('Y-m-d')];
$quantities = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'status') {
        $id = (int) input('id');
        $errors = $orderModel->updateStatus($id, input('status'));
        $errors ? setFlash('danger', implode(' ', $errors)) : setFlash('success', 'Status pesanan berhasil diubah.');
        redirect(pageUrl('orders', ['action' => 'view', 'id' => $id]));
    }

    foreach (array_keys($form) as $field) {
        $form[$field] = input($field);
    }
    $quantities = is_array($_POST['quantity'] ?? null) ? $_POST['quantity'] : [];
    $result = $orderModel->create($form, $quantities, $menuModel);
    if (!$result['errors']) {
        setFlash('success', 'Pesanan berhasil disimpan.');
        redirect(pageUrl('orders', ['action' => 'view', 'id' => $result['id']]));
    }
    $errors = $result['errors'];
    $view = 'create';
}

if ($view === 'view') {
    $order = $orderModel->findWithItems((int) ($_GET['id'] ?? 0));
    if ($order === null) {
        setFlash('danger', 'Data pesanan tidak ditemukan.');
        redirect(pageUrl('orders'));
    }
    $pageTitle = 'Detail Pesanan';
} elseif ($view === 'create') {
    $customers = (new Customer($db))->all();
    $reservations = (new Reservation($db))->selectable();
    $menus = $menuModel->available();
    $pageTitle = 'Buat Pesanan';
} else {
    $view = 'list';
    $orders = $orderModel->all();
    $pageTitle = 'Pesanan';
}

require __DIR__ . '/../includes/header.php';
?>

<?php if ($view === 'list'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Daftar Pesanan</h1>
        <a href="<?= e(pageUrl('orders', ['action' => 'create'])) ?>" class="btn btn-primary">+ Buat Pesanan</a>
    </div>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                <tr>
                    <th>No. Pesanan</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th class="text-center">Jumlah Item</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Belum ada pesanan.</td></tr>
                <?php endif; ?>
                <?php foreach ($orders as $row): ?>
                    <tr>
                        <td>#<?= (int) $row['id'] ?></td>
                        <td><?= e(formatDate($row['order_date'])) ?></td>
                        <td><?= e($row['customer_name']) ?></td>
                        <td class="text-center"><?= (int) $row['item_count'] ?></td>
                        <td class="text-end"><?= e(formatRupiah($row['total_amount'])) ?></td>
                        <td><span class="badge text-bg-<?= e(statusBadgeClass($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                        <td class="text-end">
                            <a href="<?= e(pageUrl('orders', ['action' => 'view', 'id' => $row['id']])) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($view === 'create'): ?>
    <h1 class="h3 mb-4">Buat Pesanan</h1>
    <?= showErrors($errors) ?>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white"><strong>Data Pesanan</strong></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Pelanggan</label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">-- Pilih Pelanggan --</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= (int) $customer['id'] ?>" <?= $form['customer_id'] === (string) $customer['id'] ? 'selected' : '' ?>>
                                        <?= e($customer['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reservation_id" class="form-label">Reservasi <span class="text-muted">(opsional)</span></label>
                            <select class="form-select" id="reservation_id" name="reservation_id">
                                <option value="">-- Tanpa Reservasi --</option>
                                <?php foreach ($reservations as $reservation): ?>
                                    <option value="<?= (int) $reservation['id'] ?>" <?= $form['reservation_id'] === (string) $reservation['id'] ? 'selected' : '' ?>>
                                        <?= e($reservation['customer_name']) ?> - Meja <?= e($reservation['table_number']) ?>,
                                        <?= e(formatDate($reservation['reservation_date'])) ?> <?= e(formatTime($reservation['reservation_time'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Reservasi harus milik pelanggan yang dipilih.</div>
                        </div>
                        <div class="mb-3">
                            <label for="order_date" class="form-label">Tanggal Pesanan</label>
                            <input type="date" class="form-control" id="order_date" name="order_date" required value="<?= e($form['order_date']) ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white"><strong>Pilih Menu</strong> <span class="text-muted small">(isi jumlah, kosongkan jika tidak dipesan)</span></div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Menu</th>
                                <th class="text-end">Harga</th>
                                <th style="width: 130px;">Jumlah</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($menus)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">Tidak ada menu yang tersedia.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($menus as $menu): ?>
                                <tr>
                                    <td><label for="qty-<?= (int) $menu['id'] ?>"><?= e($menu['name']) ?></label></td>
                                    <td class="text-end"><?= e(formatRupiah($menu['price'])) ?></td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" min="0" step="1"
                                               id="qty-<?= (int) $menu['id'] ?>" name="quantity[<?= (int) $menu['id'] ?>]"
                                               value="<?= e(is_scalar($quantities[$menu['id']] ?? null) ? $quantities[$menu['id']] : '') ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-body text-muted small">
                        Subtotal dan total dihitung otomatis oleh sistem berdasarkan harga menu saat pesanan disimpan.
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Simpan Pesanan</button>
                    <a href="<?= e(pageUrl('orders')) ?>" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </form>

<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detail Pesanan #<?= (int) $order['id'] ?></h1>
        <a href="<?= e(pageUrl('orders')) ?>" class="btn btn-secondary no-print">Kembali</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <dl class="mb-0">
                        <dt>Pelanggan</dt>
                        <dd><?= e($order['customer_name']) ?></dd>
                        <dt>Tanggal Pesanan</dt>
                        <dd><?= e(formatDate($order['order_date'])) ?></dd>
                        <dt>Reservasi</dt>
                        <dd><?= $order['reservation_id'] ? 'Reservasi #' . (int) $order['reservation_id'] : '-' ?></dd>
                        <dt>Status</dt>
                        <dd class="mb-0"><span class="badge text-bg-<?= e(statusBadgeClass($order['status'])) ?>"><?= e($order['status']) ?></span></dd>
                    </dl>
                </div>
            </div>
            <div class="card shadow-sm no-print">
                <div class="card-header bg-white"><strong>Ubah Status</strong></div>
                <div class="card-body">
                    <form method="post" class="d-flex gap-2">
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                        <select class="form-select" name="status" aria-label="Status pesanan">
                            <?php foreach (ORDER_STATUSES as $status): ?>
                                <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Item Pesanan</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
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
                        <tr class="table-light">
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end"><?= e(formatRupiah($order['total_amount'])) ?></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
