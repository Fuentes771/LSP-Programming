<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$customerModel = new Customer($db);
$errors = [];
$form = ['id' => '', 'name' => '', 'phone' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'delete') {
        $errors = $customerModel->delete((int) input('id'));
        $errors ? setFlash('danger', implode(' ', $errors)) : setFlash('success', 'Pelanggan berhasil dihapus.');
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
$customers = $customerModel->all();

$pageTitle = 'Data Pelanggan';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Data Pelanggan</h1>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong><?= $isEdit ? 'Ubah Pelanggan' : 'Tambah Pelanggan' ?></strong></div>
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
                        <input type="tel" class="form-control" id="phone" name="phone" maxlength="16" required value="<?= e($form['phone']) ?>" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-muted">(opsional)</span></label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="100" value="<?= e($form['email']) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <?php if ($isEdit): ?>
                        <a href="<?= e(pageUrl('customers')) ?>" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong>Daftar Pelanggan</strong></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Telepon</th>
                        <th>Email</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($customers)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data pelanggan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($customers as $i => $customer): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($customer['name']) ?></td>
                            <td><?= e($customer['phone']) ?></td>
                            <td><?= e($customer['email'] ?? '-') ?></td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(pageUrl('customers', ['edit' => $customer['id']])) ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
                                <?= actionButton('delete', (int) $customer['id'], 'Hapus', 'btn-outline-danger', 'Hapus pelanggan ' . $customer['name'] . '?') ?>
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
