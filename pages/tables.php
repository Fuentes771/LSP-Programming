<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$tableModel = new RestaurantTable($db);
$errors = [];
$form = ['id' => '', 'table_number' => '', 'capacity' => '', 'status' => 'Aktif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'delete') {
        $errors = $tableModel->delete((int) input('id'));
        $errors ? setFlash('danger', implode(' ', $errors)) : setFlash('success', 'Meja berhasil dihapus.');
        redirect(pageUrl('tables'));
    }

    $form = [
        'id'           => input('id'),
        'table_number' => input('table_number'),
        'capacity'     => input('capacity'),
        'status'       => input('status'),
    ];
    $id = $form['id'] !== '' ? (int) $form['id'] : null;
    $errors = $tableModel->save($form, $id);
    if (!$errors) {
        setFlash('success', $id ? 'Data meja berhasil diubah.' : 'Meja berhasil ditambahkan.');
        redirect(pageUrl('tables'));
    }
} elseif (isset($_GET['edit'])) {
    $table = $tableModel->find((int) $_GET['edit']);
    if ($table === null) {
        setFlash('danger', 'Data meja tidak ditemukan.');
        redirect(pageUrl('tables'));
    }
    $form = array_map('strval', $table);
}

$isEdit = $form['id'] !== '';
$tables = $tableModel->all();

$pageTitle = 'Data Meja';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Data Meja</h1>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong><?= $isEdit ? 'Ubah Meja' : 'Tambah Meja' ?></strong></div>
            <div class="card-body">
                <?= showErrors($errors) ?>
                <form method="post">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= e($form['id']) ?>">
                    <div class="mb-3">
                        <label for="table_number" class="form-label">Nomor Meja</label>
                        <input type="text" class="form-control" id="table_number" name="table_number" maxlength="10" required value="<?= e($form['table_number']) ?>" placeholder="M05">
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Kapasitas (orang)</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" min="1" required value="<?= e($form['capacity']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <?php foreach (TABLE_STATUSES as $status): ?>
                                <option value="<?= e($status) ?>" <?= $form['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <?php if ($isEdit): ?>
                        <a href="<?= e(pageUrl('tables')) ?>" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong>Daftar Meja</strong></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nomor Meja</th>
                        <th>Kapasitas</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($tables)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data meja.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tables as $i => $table): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($table['table_number']) ?></td>
                            <td><?= (int) $table['capacity'] ?> orang</td>
                            <td><span class="badge text-bg-<?= e(statusBadgeClass($table['status'])) ?>"><?= e($table['status']) ?></span></td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(pageUrl('tables', ['edit' => $table['id']])) ?>" class="btn btn-sm btn-outline-primary">Ubah</a>
                                <?= actionButton('delete', (int) $table['id'], 'Hapus', 'btn-outline-danger', 'Hapus meja ' . $table['table_number'] . '?') ?>
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
