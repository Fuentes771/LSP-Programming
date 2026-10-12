<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$customerModel = new Customer($db);
$errors = [];
$form = ['id' => '', 'name' => '', 'phone' => '', 'email' => ''];
$search = query('q');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'delete') {
        flashResult($customerModel->delete((int) input('id')), 'Pelanggan berhasil dihapus.');
        redirect(pageUrl('customers'));
    }

    $form = ['id' => input('id'), 'name' => input('name'), 'phone' => input('phone'), 'email' => input('email')];
    $id = $form['id'] !== '' ? (int) $form['id'] : null;
    $errors = $customerModel->save($form, $id);
    if (!$errors) {
        setFlash('success', $id ? 'Data pelanggan berhasil diubah.' : 'Pelanggan berhasil ditambahkan.');
        redirect(pageUrl('customers'));
    }
} elseif (isset($_GET['edit'])) {
    $customer = $customerModel->find((int) $_GET['edit']);
    if ($customer === null) {
        setFlash('danger', 'Data pelanggan tidak ditemukan.');
        redirect(pageUrl('customers'));
    }
    $form = array_map('strval', $customer);
}

$isEdit = $form['id'] !== '';
$customers = $customerModel->all($search);

$pageTitle = 'Data Pelanggan';
require __DIR__ . '/../includes/header.php';
echo pageHeader('Data Pelanggan', 'bi-people', 'Kelola data pelanggan restoran.');
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-person-plus' ?> me-1"></i><?= $isEdit ? 'Ubah Pelanggan' : 'Tambah Pelanggan' ?></div>
            <div class="card-body">
                <?= showErrors($errors) ?>
                <form method="post">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= e($form['id']) ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama</label>
                        <input type="text" class="form-control" id="name" name="name" maxlength="100" required value="<?= e($form['name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Nomor Telepon</label>
                        <input type="tel" class="form-control" id="phone" name="phone" maxlength="16" required pattern="\+?[0-9]{8,15}" value="<?= e($form['phone']) ?>" placeholder="08xxxxxxxxxx">
                        <div class="form-text">8–15 digit angka.</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-muted">(opsional)</span></label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="100" value="<?= e($form['email']) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                    <?php if ($isEdit): ?>
                        <a href="<?= e(pageUrl('customers')) ?>" class="btn btn-light">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-list-ul me-1"></i>Daftar Pelanggan</span>
                <form method="get" class="d-flex gap-2" role="search">
                    <input type="hidden" name="page" value="customers">
                    <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari nama / telepon" value="<?= e($search) ?>" aria-label="Cari pelanggan">
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <?php if (empty($customers)): ?>
                <?= emptyState($search !== '' ? 'Pelanggan dengan kata kunci "' . $search . '" tidak ditemukan.' : 'Belum ada data pelanggan.') ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kontak</th>
                            <th class="text-center">Reservasi</th>
                            <th class="text-center">Pesanan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($customer['name']) ?></td>
                                <td>
                                    <div><i class="bi bi-telephone text-muted me-1"></i><?= e($customer['phone']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?= e($customer['email'] ?? '-') ?></div>
                                </td>
                                <td class="text-center"><?= (int) $customer['reservation_count'] ?></td>
                                <td class="text-center"><?= (int) $customer['order_count'] ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= e(pageUrl('customers', ['edit' => $customer['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Ubah"><i class="bi bi-pencil"></i></a>
                                    <?= actionButton('delete', (int) $customer['id'], 'Hapus', 'btn-outline-danger', 'Hapus pelanggan ' . $customer['name'] . '?', [], 'bi-trash', true) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
