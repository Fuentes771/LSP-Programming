<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$menuModel = new MenuItem($db);
$errors = [];
$form = ['id' => '', 'name' => '', 'price' => '', 'is_available' => '1'];
$search = query('q');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'delete') {
        flashResult($menuModel->delete((int) input('id')), 'Menu berhasil dihapus.');
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
$menus = $menuModel->all($search);

$pageTitle = 'Data Menu';
require __DIR__ . '/../includes/header.php';
echo pageHeader('Data Menu', 'bi-journal-text', 'Perubahan harga tidak memengaruhi pesanan yang sudah dibuat.');
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-square' ?> me-1"></i><?= $isEdit ? 'Ubah Menu' : 'Tambah Menu' ?></div>
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
                        <label for="price" class="form-label">Harga</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="price" name="price" min="1" step="0.01" required value="<?= e($form['price']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="is_available" class="form-label">Status</label>
                        <select class="form-select" id="is_available" name="is_available">
                            <option value="1" <?= $form['is_available'] === '1' ? 'selected' : '' ?>>Tersedia</option>
                            <option value="0" <?= $form['is_available'] === '0' ? 'selected' : '' ?>>Tidak Tersedia</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                    <?php if ($isEdit): ?>
                        <a href="<?= e(pageUrl('menu')) ?>" class="btn btn-light">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-list-ul me-1"></i>Daftar Menu</span>
                <form method="get" class="d-flex gap-2" role="search">
                    <input type="hidden" name="page" value="menu">
                    <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari nama menu" value="<?= e($search) ?>" aria-label="Cari menu">
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <?php if (empty($menus)): ?>
                <?= emptyState($search !== '' ? 'Menu dengan kata kunci "' . $search . '" tidak ditemukan.' : 'Belum ada data menu.') ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Nama Menu</th>
                            <th class="text-end">Harga</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($menus as $menu): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($menu['name']) ?></td>
                                <td class="text-end"><?= e(formatRupiah($menu['price'])) ?></td>
                                <td>
                                    <?php if ($menu['is_available']): ?>
                                        <span class="badge rounded-pill text-bg-success"><i class="bi bi-check-circle me-1"></i>Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill text-bg-secondary"><i class="bi bi-slash-circle me-1"></i>Tidak Tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= e(pageUrl('menu', ['edit' => $menu['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Ubah"><i class="bi bi-pencil"></i></a>
                                    <?= actionButton('delete', (int) $menu['id'], 'Hapus', 'btn-outline-danger', 'Hapus menu ' . $menu['name'] . '?', [], 'bi-trash', true) ?>
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
