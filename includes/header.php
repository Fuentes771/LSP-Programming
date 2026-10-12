<?php defined('APP_RUNNING') or exit('Akses langsung tidak diizinkan.'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Sistem Pemesanan Restoran') ?> - Sistem Pemesanan Restoran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stat-card .display-6 { font-weight: 600; }
        .table td, .table th { vertical-align: middle; }
        @media print {
            .no-print { display: none !important; }
            body.bg-light { background: #fff !important; }
            .card { border: 0 !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-light">
<?php require __DIR__ . '/navbar.php'; ?>
<main class="container py-4">
    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show no-print" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    <?php endif; ?>
