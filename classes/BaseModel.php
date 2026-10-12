<?php
/**
 * Class induk untuk semua model: menyimpan koneksi PDO dan menyediakan
 * method umum yang dipakai berulang (hitung data, cari berdasarkan id).
 */
abstract class BaseModel
{
    protected PDO $db;

    /** Nama tabel, wajib diisi oleh class turunan. */
    protected string $table;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Menghitung jumlah baris pada tabel lain yang mereferensikan data ini (cek sebelum hapus). */
    protected function countRelated(string $table, string $column, int $id): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    /** True jika error PDO disebabkan pelanggaran constraint (duplikat / foreign key). */
    protected function isConstraintError(PDOException $e): bool
    {
        return $e->getCode() === '23000';
    }
}
