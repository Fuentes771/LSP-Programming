<?php
defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.');
/**
 * Pemilih pelanggan untuk form reservasi & pesanan: pilih pelanggan terdaftar atau isi pelanggan baru.
 * Variabel yang dibutuhkan: $customers, $form['customer_id'], $customerMode ('existing'|'new'), $newCustomer.
 * Opsional: $customerLocked (pelanggan tidak boleh diganti), $allowNewCustomer (tampilkan pilihan pelanggan baru).
 */
$customerLocked = $customerLocked ?? false;
$allowNewCustomer = ($allowNewCustomer ?? true) && !$customerLocked;
$customerMode = $allowNewCustomer ? $customerMode : 'existing';
?>
<div class="mb-3 customer-picker">
    <label class="form-label" for="customer_id">Pelanggan</label>

    <?php if ($allowNewCustomer): ?>
        <div class="btn-group btn-group-sm w-100 mb-2" role="group" aria-label="Jenis pelanggan">
            <input type="radio" class="btn-check" name="customer_mode" id="customer_mode_existing" value="existing" <?= $customerMode === 'existing' ? 'checked' : '' ?>>
            <label class="btn btn-outline-primary" for="customer_mode_existing"><i class="bi bi-person-check me-1"></i>Pelanggan terdaftar</label>
            <input type="radio" class="btn-check" name="customer_mode" id="customer_mode_new" value="new" <?= $customerMode === 'new' ? 'checked' : '' ?>>
            <label class="btn btn-outline-primary" for="customer_mode_new"><i class="bi bi-person-plus me-1"></i>Pelanggan baru</label>
        </div>
    <?php endif; ?>

    <div data-customer-section="existing">
        <?php if ($customerLocked): ?>
            <input type="hidden" name="customer_id" value="<?= e($form['customer_id']) ?>">
        <?php endif; ?>
        <select class="form-select" id="customer_id" <?= $customerLocked ? 'disabled' : 'name="customer_id" data-required' ?>>
            <option value="">-- Pilih Pelanggan --</option>
            <?php foreach ($customers as $customer): ?>
                <option value="<?= (int) $customer['id'] ?>" <?= $form['customer_id'] === (string) $customer['id'] ? 'selected' : '' ?>>
                    <?= e($customer['name']) ?> (<?= e($customer['phone']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($customerLocked): ?>
            <div class="form-text">Pelanggan tidak dapat diganti karena reservasi ini sudah memiliki pesanan.</div>
        <?php elseif ($allowNewCustomer): ?>
            <div class="form-text">Belum terdaftar? Pilih <strong>Pelanggan baru</strong> di atas.</div>
        <?php endif; ?>
    </div>

    <?php if ($allowNewCustomer): ?>
        <div data-customer-section="new" class="border rounded-3 p-3 bg-body-tertiary">
            <div class="row g-2">
                <div class="col-md-6">
                    <label for="new_customer_name" class="form-label small mb-1">Nama</label>
                    <input type="text" class="form-control form-control-sm" id="new_customer_name" name="new_customer_name" maxlength="100" data-required value="<?= e($newCustomer['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label for="new_customer_phone" class="form-label small mb-1">Nomor Telepon</label>
                    <input type="tel" class="form-control form-control-sm" id="new_customer_phone" name="new_customer_phone" maxlength="16" pattern="\+?[0-9]{8,15}" placeholder="08xxxxxxxxxx" data-required value="<?= e($newCustomer['phone']) ?>">
                </div>
                <div class="col-12">
                    <label for="new_customer_email" class="form-label small mb-1">Email <span class="text-muted">(opsional)</span></label>
                    <input type="email" class="form-control form-control-sm" id="new_customer_email" name="new_customer_email" maxlength="100" value="<?= e($newCustomer['email']) ?>">
                </div>
            </div>
            <div class="form-text"><i class="bi bi-info-circle me-1"></i>Pelanggan baru otomatis tersimpan ke data Pelanggan saat form disimpan.</div>
        </div>
    <?php endif; ?>
</div>
