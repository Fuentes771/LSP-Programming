<?php defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.'); ?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar no-print">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= e(pageUrl('dashboard')) ?>">
            <i class="bi bi-cup-hot-fill me-1"></i> Restoran Kita
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($pages as $key => [$label, $icon]): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $key === $page ? ' active' : '' ?>"
                           <?= $key === $page ? 'aria-current="page"' : '' ?>
                           href="<?= e(pageUrl($key)) ?>"><i class="bi <?= e($icon) ?> me-1"></i><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>
