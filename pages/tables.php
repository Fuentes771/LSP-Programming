<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');

$tableModel = new RestaurantTable($db);
$errors = [];
$form = ['id' => '', 'table_number' => '', 'capacity' => '', 'status' => 'Aktif'];
$search = query('q');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (input('action') === 'delete') {
        flashResult($tableModel->delete((int) input('id')), 'Meja berhasil dihapus.');
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
$tables = $tableModel->all($search);

$pageTitle = 'Data Meja';
require __DIR__ . '/../includes/header.php';
echo pageHeader('Data Meja', 'bi-grid-3x3-gap', 'Meja Nonaktif tidak dapat dipilih untuk reservasi.');
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-square' ?> me-1"></i><?= $isEdit ? 'Ubah Meja' : 'Tambah Meja' ?></div>
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
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                    <?php if ($isEdit): ?>
                        <a href="<?= e(pageUrl('tables')) ?>" class="btn btn-light">Batal</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-list-ul me-1"></i>Daftar Meja</span>
                <form method="get" class="d-flex gap-2" role="search">
                    <input type="hidden" name="page" value="tables">
                    <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari nomor meja" value="<?= e($search) ?>" aria-label="Cari meja">
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <?php if (empty($tables)): ?>
                <?= emptyState($search !== '' ? 'Meja dengan kata kunci "' . $search . '" tidak ditemukan.' : 'Belum ada data meja.') ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Nomor Meja</th>
                            <th>Kapasitas</th>
                            <th>Status</th>
                            <th class="text-center">Reservasi Aktif</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tables as $table): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($table['table_number']) ?></td>
                                <td><i class="bi bi-people text-muted me-1"></i><?= (int) $table['capacity'] ?> orang</td>
                                <td><?= statusBadge($table['status']) ?></td>
                                <td class="text-center"><?= (int) $table['upcoming_count'] ?></td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= e(pageUrl('tables', ['edit' => $table['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Ubah"><i class="bi bi-pencil"></i></a>
                                    <?= actionButton('delete', (int) $table['id'], 'Hapus', 'btn-outline-danger', 'Hapus meja ' . $table['table_number'] . '?', [], 'bi-trash', true) ?>
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
