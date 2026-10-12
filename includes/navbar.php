<?php defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.'); ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark no-print">
    <div class="container">
        <a class="navbar-brand" href="<?= e(pageUrl('dashboard')) ?>">Restoran Kita</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($pages as $key => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $key === $page ? ' active' : '' ?>"
                           <?= $key === $page ? 'aria-current="page"' : '' ?>
                           href="<?= e(pageUrl($key)) ?>"><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>
