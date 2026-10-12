<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$menuModel = new MenuItem($db);
$errors = [];
$form = ['id' => '', 'name' => '', 'price' => '', 'is_available' => '1'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'delete') {
        $errors = $menuModel->delete((int) input('id'));
        $errors ? setFlash('danger', implode(' ', $errors)) : setFlash('success', 'Menu berhasil dihapus.');
        redirect(pageUrl('menu'));
    }

    $form = [
        'id'           => input('id'),
        'name'         => input('name'),
        'price'        => input('price'),
        'is_available' => input('is_available'),
    ];
    $id = $form['id'] !== '' ? (int) $form['id'] : null;
    $errors = $menuModel->save($form, $id);
    if (!$errors) {
        setFlash('success', $id ? 'Data menu berhasil diubah.' : 'Menu berhasil ditambahkan.');
        redirect(pageUrl('menu'));
    }
} elseif (isset($_GET['edit'])) {
    $menu = $menuModel->find((int) $_GET['edit']);
    if ($menu === null) {
        setFlash('danger', 'Data menu tidak ditemukan.');
        redirect(pageUrl('menu'));
    }
    $form = array_map('strval', $menu);
}

$isEdit = $form['id'] !== '';
$menus = $menuModel->all();

$pageTitle = 'Data Menu';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Data Menu</h1>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong><?= $isEdit ? 'Ubah Menu' : 'Tambah Menu' ?></strong></div>
            <div class="card-body">
                <?= showErrors($errors) ?>
                <form method="post">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= e($form['id']) ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Menu</label>
                        <input type="text" class="form-control" id="name" name="name" maxlength="100" required value="<?= e($form['name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Harga (Rp)</label>
                        <input type="number" class="form-control" id="price" name="price" min="1" step="0.01" required value="<?= e($form['price']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="is_available" class="form-label">Status</label>
                        <select class="form-select" id="is_available" name="is_available">
                            <option value="1" <?= $form['is_available'] === '1' ? 'selected' : '' ?>>Tersedia</option>
                            <option value="0" <?= $form['is_available'] === '0' ? 'selected' : '' ?>>Tidak Tersedia</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <?php if ($isEdit): ?>
                        <a href="<?= e(pageUrl('menu')) ?>" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong>Daftar Menu</strong></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Menu</th>
                        <th class="text-end">Harga</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($menus)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data menu.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($menus as $i => $menu): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($menu['name']) ?></td>
                            <td class="text-end"><?= e(formatRupiah($menu['price'])) ?></td>
                            <td>
                                <?php if ($menu['is_available']): ?>
                                    <span class="badge text-bg-success">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Tidak Tersedia</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(pageUrl('menu', ['edit' => $menu['id']])) ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
                                <?= actionButton('delete', (int) $menu['id'], 'Hapus', 'btn-outline-danger', 'Hapus menu ' . $menu['name'] . '?') ?>
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
