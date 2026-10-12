<?php
require_once __DIR__ . '/BaseModel.php';

/** Mengelola data meja restoran. */
class RestaurantTable extends BaseModel
{
    protected string $table = 'restaurant_tables';

    /** Daftar meja beserta jumlah reservasi aktif mulai hari ini, dapat dicari berdasarkan nomor meja. */
    public function all(string $search = ''): array
    {
        $stmt = $this->db->prepare('
            SELECT t.*,
                   (SELECT COUNT(*) FROM reservations r
                    WHERE r.table_id = t.id AND r.status IN (?, ?) AND r.reservation_date >= ?) AS upcoming_count
            FROM restaurant_tables t
            WHERE t.table_number LIKE ?
            ORDER BY t.table_number');
        $stmt->execute([...ACTIVE_RESERVATION_STATUSES, date('Y-m-d'), '%' . $search . '%']);
        return $stmt->fetchAll();
    }

    /** Meja berstatus Aktif yang dapat dipilih untuk reservasi. */
    public function active(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM restaurant_tables WHERE status = ? ORDER BY capacity, table_number');
        $stmt->execute(['Aktif']);
        return $stmt->fetchAll();
    }

    /** Jumlah reservasi aktif (Menunggu/Dikonfirmasi) pada meja ini mulai hari ini. */
    private function countUpcomingReservations(int $id): int
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM reservations
            WHERE table_id = ? AND status IN (?, ?) AND reservation_date >= ?');
        $stmt->execute([$id, ...ACTIVE_RESERVATION_STATUSES, date('Y-m-d')]);
        return (int) $stmt->fetchColumn();
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
        if ($id !== null && $data['status'] === 'Nonaktif' && $this->countUpcomingReservations($id) > 0) {
            return ['Meja tidak dapat dinonaktifkan karena masih memiliki reservasi aktif yang akan datang. Selesaikan atau batalkan reservasinya terlebih dahulu.'];
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
