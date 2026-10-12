<?php
// Sesuaikan dengan pengaturan MySQL/MariaDB lokal (default XAMPP: root tanpa password)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'restoran_db');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Membuat (sekali) dan mengembalikan koneksi PDO.
 * Detail error hanya dicatat ke log server, tidak ditampilkan ke pengguna.
 */
function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('Koneksi database gagal: ' . $e->getMessage());
            http_response_code(500);
            exit('Koneksi ke database gagal. Periksa pengaturan di config/database.php dan pastikan MySQL berjalan.');
        }
    }

    return $pdo;
}
