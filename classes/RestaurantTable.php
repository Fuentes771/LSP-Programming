<?php
require_once __DIR__ . '/BaseModel.php';

/** Mengelola data meja restoran. */
class RestaurantTable extends BaseModel
{
    protected string $table = 'restaurant_tables';

    public function all(): array
    {
        return $this->db->query('SELECT * FROM restaurant_tables ORDER BY table_number')->fetchAll();
    }

    /** Meja berstatus Aktif yang dapat dipilih untuk reservasi. */
    public function active(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM restaurant_tables WHERE status = ? ORDER BY table_number');
        $stmt->execute(['Aktif']);
        return $stmt->fetchAll();
    }

    public function save(array $data, ?int $id = null): array
    {
        $errors = validateTableData($data);
        if ($errors) {
            return $errors;
        }
        if ($id !== null && $this->find($id) === null) {
            return ['Data meja tidak ditemukan.'];
        }

        $params = [$data['table_number'], (int) $data['capacity'], $data['status']];
        try {
            if ($id === null) {
                $stmt = $this->db->prepare('INSERT INTO restaurant_tables (table_number, capacity, status) VALUES (?, ?, ?)');
            } else {
                $stmt = $this->db->prepare('UPDATE restaurant_tables SET table_number = ?, capacity = ?, status = ? WHERE id = ?');
                $params[] = $id;
            }
            $stmt->execute($params);
        } catch (PDOException $e) {
            if ($this->isConstraintError($e)) {
                return ['Nomor meja "' . $data['table_number'] . '" sudah digunakan.'];
            }
            throw $e;
        }
        return [];
    }

    public function delete(int $id): array
    {
        if ($this->find($id) === null) {
            return ['Data meja tidak ditemukan.'];
        }
        if ($this->countRelated('reservations', 'table_id', $id) > 0) {
            return ['Meja tidak dapat dihapus karena masih memiliki data reservasi. Ubah statusnya menjadi Nonaktif.'];
        }

        try {
            $stmt = $this->db->prepare('DELETE FROM restaurant_tables WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            if ($this->isConstraintError($e)) {
                return ['Meja tidak dapat dihapus karena masih digunakan oleh data lain.'];
            }
            throw $e;
        }
        return [];
    }
}
