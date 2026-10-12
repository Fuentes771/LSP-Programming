<?php
// Fungsi bantuan, validasi, dan perhitungan (pemrograman terstruktur).
// File ini tidak bergantung pada database sehingga dapat diuji lewat CLI.

const TABLE_STATUSES = ['Aktif', 'Nonaktif'];
const RESERVATION_STATUSES = ['Menunggu', 'Dikonfirmasi', 'Selesai', 'Dibatalkan'];
const ACTIVE_RESERVATION_STATUSES = ['Menunggu', 'Dikonfirmasi'];
// Reservasi yang sudah dikonfirmasi (tamu datang) sehingga boleh dibuatkan pesanan
const ORDERABLE_RESERVATION_STATUSES = ['Dikonfirmasi'];
const ORDER_STATUSES = ['Diproses', 'Selesai', 'Dibatalkan'];

// Perubahan status yang diizinkan: status asal => daftar status tujuan
const RESERVATION_TRANSITIONS = [
    'Menunggu'     => ['Dikonfirmasi', 'Dibatalkan'],
    'Dikonfirmasi' => ['Selesai', 'Dibatalkan'],
    'Selesai'      => [],
    'Dibatalkan'   => [],
];
const ORDER_TRANSITIONS = [
    'Diproses'   => ['Selesai', 'Dibatalkan'],
    'Selesai'    => [],
    'Dibatalkan' => [],
];

// Aturan operasional restoran
const OPENING_TIME = '10:00';
const CLOSING_TIME = '22:00';
const RESERVATION_DURATION_MINUTES = 120;
const MAX_ORDER_QUANTITY = 100;

// ---------------------------------------------------------------------------
// Tampilan dan navigasi
// ---------------------------------------------------------------------------

/** Escape teks sebelum dicetak ke HTML (mencegah XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatRupiah($amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function formatDate(?string $date): string
{
    if (empty($date)) {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp === false ? '-' : date('d-m-Y', $timestamp);
}

function formatTime(?string $time): string
{
    return empty($time) ? '-' : substr($time, 0, 5);
}

/** Warna badge Bootstrap untuk setiap status. */
function statusBadgeClass(string $status): string
{
    $map = [
        'Aktif'        => 'success',
        'Nonaktif'     => 'secondary',
        'Menunggu'     => 'warning',
        'Dikonfirmasi' => 'info',
        'Diproses'     => 'warning',
        'Selesai'      => 'success',
        'Dibatalkan'   => 'danger',
    ];
    return $map[$status] ?? 'secondary';
}

/** Badge status lengkap dengan ikon. */
function statusBadge(string $status): string
{
    $icons = [
        'Aktif'        => 'bi-check-circle',
        'Nonaktif'     => 'bi-slash-circle',
        'Menunggu'     => 'bi-hourglass-split',
        'Dikonfirmasi' => 'bi-check2-circle',
        'Diproses'     => 'bi-fire',
        'Selesai'      => 'bi-check-circle-fill',
        'Dibatalkan'   => 'bi-x-circle',
    ];
    return '<span class="badge rounded-pill text-bg-' . statusBadgeClass($status) . '">'
        . '<i class="bi ' . ($icons[$status] ?? 'bi-circle') . ' me-1"></i>' . e($status) . '</span>';
}

/** Judul halaman dengan ikon, keterangan singkat, dan tombol aksi utama di kanan. */
function pageHeader(string $title, string $icon, string $subtitle = '', string $actionHtml = ''): string
{
    return '<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">'
        . '<div><h1 class="h3 mb-0"><i class="bi ' . e($icon) . ' text-primary me-2"></i>' . e($title) . '</h1>'
        . ($subtitle !== '' ? '<p class="text-muted mb-0 small">' . e($subtitle) . '</p>' : '')
        . '</div>'
        . ($actionHtml !== '' ? '<div class="no-print">' . $actionHtml . '</div>' : '')
        . '</div>';
}

/** Tampilan saat tabel tidak memiliki data. */
function emptyState(string $text, string $actionHtml = ''): string
{
    return '<div class="empty-state text-center text-muted py-5">'
        . '<i class="bi bi-inbox display-6 d-block mb-2"></i>'
        . '<p class="mb-2">' . e($text) . '</p>' . $actionHtml . '</div>';
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/** Simpan hasil aksi sebagai pesan flash: daftar error (gagal) atau pesan sukses. */
function flashResult(array $errors, string $successMessage): void
{
    $errors ? setFlash('danger', implode(' ', $errors)) : setFlash('success', $successMessage);
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Membuat URL halaman aplikasi, contoh: pageUrl('customers', ['edit' => 3]). */
function pageUrl(string $page, array $params = []): string
{
    return 'index.php?' . http_build_query(['page' => $page] + $params);
}

/** Menampilkan daftar pesan error validasi dalam alert Bootstrap. */
function showErrors(array $errors): string
{
    if (empty($errors)) {
        return '';
    }
    $html = '<div class="alert alert-danger"><strong><i class="bi bi-exclamation-triangle me-1"></i>Data gagal disimpan:</strong><ul class="mb-0">';
    foreach ($errors as $error) {
        $html .= '<li>' . e($error) . '</li>';
    }
    return $html . '</ul></div>';
}

/**
 * Tombol aksi kecil berbentuk form POST (hapus, batalkan, ubah status).
 * $extra berisi field tersembunyi tambahan, misalnya ['status' => 'Selesai'].
 */
function actionButton(string $action, int $id, string $label, string $btnClass, string $confirmMessage = '', array $extra = [], string $icon = '', bool $iconOnly = false): string
{
    $onsubmit = $confirmMessage !== '' ? ' onsubmit="return confirm(' . e(json_encode($confirmMessage)) . ');"' : '';
    $html = '<form method="post" class="d-inline"' . $onsubmit . '>'
        . '<input type="hidden" name="action" value="' . e($action) . '">'
        . '<input type="hidden" name="id" value="' . $id . '">';
    foreach ($extra as $name => $value) {
        $html .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
    }
    $iconHtml = $icon !== '' ? '<i class="bi ' . e($icon) . ($iconOnly ? '' : ' me-1') . '"></i>' : '';
    $labelHtml = $iconOnly ? '<span class="visually-hidden">' . e($label) . '</span>' : e($label);
    return $html . '<button type="submit" class="btn btn-sm ' . e($btnClass) . '" title="' . e($label) . '">'
        . $iconHtml . $labelHtml . '</button></form>';
}

/** Ambil nilai input POST yang sudah di-trim. */
function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

/** Ambil nilai query string (GET) yang sudah di-trim. */
function query(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

// ---------------------------------------------------------------------------
// Validasi dasar
// ---------------------------------------------------------------------------

function isRequired($value): bool
{
    return trim((string) $value) !== '';
}

function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Nomor telepon: 8–15 digit, boleh diawali tanda +. */
function isValidPhone(string $phone): bool
{
    return preg_match('/^\+?[0-9]{8,15}$/', $phone) === 1;
}

function isValidDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

/** Menerima format HH:MM atau HH:MM:SS. */
function isValidTime(string $time): bool
{
    return preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time) === 1;
}

/** Samakan format waktu menjadi HH:MM:SS agar input form (HH:MM) dapat dibandingkan dengan data DB. */
function normalizeTime(string $time): string
{
    return strlen($time) === 5 ? $time . ':00' : $time;
}

/** Ubah waktu HH:MM(:SS) menjadi jumlah menit sejak pukul 00:00. */
function timeToMinutes(string $time): int
{
    [$hour, $minute] = explode(':', $time);
    return (int) $hour * 60 + (int) $minute;
}

/** Bilangan bulat positif (> 0), misalnya kuantitas, kapasitas, jumlah tamu. */
function isPositiveInteger($value): bool
{
    if (is_int($value)) {
        return $value > 0;
    }
    if (!is_string($value) || preg_match('/^[0-9]+$/', trim($value)) !== 1) {
        return false;
    }
    return (int) $value > 0;
}

/** Harga harus berupa angka dan lebih dari nol. */
function validatePrice($price): bool
{
    return is_numeric($price) && (float) $price > 0;
}

function validateQuantity($quantity): bool
{
    return isPositiveInteger($quantity) && (int) $quantity <= MAX_ORDER_QUANTITY;
}

function validateCapacity($capacity): bool
{
    return isPositiveInteger($capacity);
}

/** Jumlah tamu minimal 1 dan tidak boleh melebihi kapasitas meja. */
function validateGuestCount($guestCount, $capacity): bool
{
    return isPositiveInteger($guestCount) && (int) $guestCount <= (int) $capacity;
}

/** Cek apakah perpindahan status diizinkan menurut tabel transisi. */
function canChangeStatus(string $from, string $to, array $transitions): bool
{
    return in_array($to, $transitions[$from] ?? [], true);
}

// ---------------------------------------------------------------------------
// Perhitungan pesanan
// ---------------------------------------------------------------------------

function calculateSubtotal($price, $quantity): float
{
    return round((float) $price * (int) $quantity, 2);
}

/** @param array<int, array{price: float|string, quantity: int|string}> $items */
function calculateOrderTotal(array $items): float
{
    $total = 0.0;
    foreach ($items as $item) {
        $total += calculateSubtotal($item['price'], $item['quantity']);
    }
    return round($total, 2);
}

/**
 * Menyusun item pesanan dari kuantitas yang diinput (menu_id => qty).
 * Harga diambil dari data menu (bukan dari form) agar tidak dapat dimanipulasi.
 * Kuantitas kosong/0 dianggap tidak dipesan.
 *
 * @param array $quantities input kuantitas per menu_id
 * @param array $menus      menu tersedia, diindeks berdasarkan id
 * @return array{items: array, errors: array}
 */
function buildOrderItems(array $quantities, array $menus): array
{
    $items = [];
    $errors = [];

    foreach ($quantities as $menuId => $quantity) {
        $quantity = is_scalar($quantity) ? trim((string) $quantity) : 'invalid';
        if ($quantity === '' || $quantity === '0') {
            continue;
        }
        if (!isset($menus[$menuId])) {
            $errors[] = 'Menu yang dipilih tidak tersedia.';
            continue;
        }
        $menu = $menus[$menuId];
        if (!validateQuantity($quantity)) {
            $errors[] = 'Jumlah untuk menu "' . $menu['name'] . '" harus bilangan bulat 1–' . MAX_ORDER_QUANTITY . '.';
            continue;
        }
        $items[] = [
            'menu_item_id' => (int) $menu['id'],
            'menu_name'    => $menu['name'],
            'price'        => (float) $menu['price'],
            'quantity'     => (int) $quantity,
            'subtotal'     => calculateSubtotal($menu['price'], $quantity),
        ];
    }

    if (empty($items) && empty($errors)) {
        $errors[] = 'Pilih minimal satu menu dengan jumlah lebih dari 0.';
    }

    return ['items' => $items, 'errors' => $errors];
}

/**
 * Cek apakah reservasi boleh dibuatkan pesanan: harus sudah Dikonfirmasi
 * dan tanggalnya sudah tiba (bukan reservasi untuk hari-hari berikutnya).
 */
function validateReservationForOrder(array $reservation, string $today): array
{
    if (!in_array($reservation['status'], ORDERABLE_RESERVATION_STATUSES, true)) {
        return ['Pesanan hanya dapat dibuat untuk reservasi yang sudah Dikonfirmasi (status saat ini: '
            . $reservation['status'] . '). Konfirmasi reservasinya terlebih dahulu.'];
    }
    if ($reservation['reservation_date'] > $today) {
        return ['Pesanan untuk reservasi baru dapat dibuat pada tanggal reservasinya.'];
    }
    return [];
}

/**
 * Reservasi otomatis diselesaikan ketika pesanannya selesai, asalkan reservasi masih Dikonfirmasi
 * dan tidak ada pesanan lain untuk reservasi tersebut yang masih Diproses.
 */
function shouldCompleteReservation(string $reservationStatus, int $openOrderCount): bool
{
    return $reservationStatus === 'Dikonfirmasi' && $openOrderCount === 0;
}

/** Tanggal pesanan tanpa reservasi harus valid dan tidak boleh melebihi hari ini. */
function validateOrderDate(string $date, string $today): array
{
    if (!isValidDate($date)) {
        return ['Tanggal pesanan tidak valid.'];
    }
    if ($date > $today) {
        return ['Tanggal pesanan tidak boleh melebihi hari ini.'];
    }
    return [];
}

// ---------------------------------------------------------------------------
// Reservasi
// ---------------------------------------------------------------------------

/** Jam mulai reservasi terakhir agar selesai tepat saat restoran tutup (22:00 - 2 jam = 20:00). */
function latestReservationStart(): string
{
    $minutes = timeToMinutes(CLOSING_TIME) - RESERVATION_DURATION_MINUTES;
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

/** Reservasi harus dimulai setelah restoran buka dan selesai (mulai + durasi) sebelum tutup. */
function isWithinOperatingHours(string $time): bool
{
    $start = timeToMinutes($time);
    return $start >= timeToMinutes(OPENING_TIME)
        && $start + RESERVATION_DURATION_MINUTES <= timeToMinutes(CLOSING_TIME);
}

/** True jika tanggal + jam sudah lewat dibandingkan waktu sekarang ($now disuntikkan agar mudah diuji). */
function isPastDateTime(string $date, string $time, DateTimeInterface $now): bool
{
    $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date . ' ' . normalizeTime($time), $now->getTimezone());
    return $dateTime !== false && $dateTime < $now;
}

/**
 * Cek apakah ada reservasi aktif lain pada meja dan tanggal yang sama
 * yang waktunya tumpang tindih (setiap reservasi memakai meja selama RESERVATION_DURATION_MINUTES).
 *
 * @param array    $existing  daftar reservasi (id, table_id, reservation_date, reservation_time, status)
 * @param int|null $excludeId id reservasi yang sedang diubah (diabaikan dari pengecekan)
 */
function hasReservationConflict(array $existing, int $tableId, string $date, string $time, ?int $excludeId = null): bool
{
    $start = timeToMinutes(normalizeTime($time));

    foreach ($existing as $reservation) {
        if ($excludeId !== null && (int) $reservation['id'] === $excludeId) {
            continue;
        }
        if (
            (int) $reservation['table_id'] === $tableId
            && $reservation['reservation_date'] === $date
            && in_array($reservation['status'], ACTIVE_RESERVATION_STATUSES, true)
            && abs(timeToMinutes(normalizeTime($reservation['reservation_time'])) - $start) < RESERVATION_DURATION_MINUTES
        ) {
            return true;
        }
    }
    return false;
}

// ---------------------------------------------------------------------------
// Validasi data per entitas (mengembalikan daftar pesan error; kosong = valid)
// ---------------------------------------------------------------------------

function validateCustomerData(array $data): array
{
    $errors = [];
    if (!isRequired($data['name'] ?? '')) {
        $errors[] = 'Nama pelanggan wajib diisi.';
    } elseif (mb_strlen($data['name']) > 100) {
        $errors[] = 'Nama pelanggan maksimal 100 karakter.';
    }
    if (!isValidPhone($data['phone'] ?? '')) {
        $errors[] = 'Nomor telepon harus 8–15 digit angka.';
    }
    $email = $data['email'] ?? '';
    if ($email !== '' && !isValidEmail($email)) {
        $errors[] = 'Format email tidak valid.';
    }
    return $errors;
}

function validateTableData(array $data): array
{
    $errors = [];
    if (!isRequired($data['table_number'] ?? '')) {
        $errors[] = 'Nomor meja wajib diisi.';
    } elseif (mb_strlen($data['table_number']) > 10) {
        $errors[] = 'Nomor meja maksimal 10 karakter.';
    }
    if (!validateCapacity($data['capacity'] ?? '')) {
        $errors[] = 'Kapasitas harus bilangan bulat lebih dari 0.';
    }
    if (!in_array($data['status'] ?? '', TABLE_STATUSES, true)) {
        $errors[] = 'Status meja tidak valid.';
    }
    return $errors;
}

function validateMenuData(array $data): array
{
    $errors = [];
    if (!isRequired($data['name'] ?? '')) {
        $errors[] = 'Nama menu wajib diisi.';
    } elseif (mb_strlen($data['name']) > 100) {
        $errors[] = 'Nama menu maksimal 100 karakter.';
    }
    if (!validatePrice($data['price'] ?? '')) {
        $errors[] = 'Harga harus berupa angka lebih dari 0.';
    }
    if (!in_array((string) ($data['is_available'] ?? ''), ['0', '1'], true)) {
        $errors[] = 'Status ketersediaan menu tidak valid.';
    }
    return $errors;
}

/**
 * Validasi data reservasi yang tidak membutuhkan database:
 * format input, jam operasional, dan (jika $checkPast) tidak boleh di waktu yang sudah lewat.
 * Kapasitas meja dan jadwal bentrok dicek di class Reservation.
 */
function validateReservationData(array $data, DateTimeInterface $now, bool $checkPast = true): array
{
    $errors = [];
    if (!isPositiveInteger($data['customer_id'] ?? '')) {
        $errors[] = 'Pelanggan wajib dipilih.';
    }
    if (!isPositiveInteger($data['table_id'] ?? '')) {
        $errors[] = 'Meja wajib dipilih.';
    }
    $date = $data['reservation_date'] ?? '';
    $time = $data['reservation_time'] ?? '';
    $validDate = isValidDate($date);
    $validTime = isValidTime($time);
    if (!$validDate) {
        $errors[] = 'Tanggal reservasi tidak valid.';
    }
    if (!$validTime) {
        $errors[] = 'Waktu reservasi tidak valid.';
    } elseif (!isWithinOperatingHours($time)) {
        $errors[] = 'Waktu reservasi harus antara ' . OPENING_TIME . ' dan ' . latestReservationStart()
            . ' (restoran buka ' . OPENING_TIME . '–' . CLOSING_TIME . ', durasi reservasi '
            . (RESERVATION_DURATION_MINUTES / 60) . ' jam).';
    }
    if ($checkPast && $validDate && $validTime && isPastDateTime($date, $time, $now)) {
        $errors[] = 'Tanggal dan waktu reservasi sudah lewat.';
    }
    if (!isPositiveInteger($data['guest_count'] ?? '')) {
        $errors[] = 'Jumlah tamu harus bilangan bulat lebih dari 0.';
    }
    return $errors;
}

function validateDateRange(string $startDate, string $endDate): array
{
    $errors = [];
    if (!isValidDate($startDate) || !isValidDate($endDate)) {
        $errors[] = 'Tanggal awal dan akhir harus diisi dengan format yang benar.';
    } elseif ($startDate > $endDate) {
        $errors[] = 'Tanggal awal tidak boleh melebihi tanggal akhir.';
    }
    return $errors;
}
