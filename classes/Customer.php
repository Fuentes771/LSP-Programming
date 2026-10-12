<?php
require_once __DIR__ . '/BaseModel.php';

/** Mengelola data pelanggan. */
class Customer extends BaseModel
{
    protected string $table = 'customers';

    /** Daftar pelanggan beserta jumlah reservasi & pesanan, dapat dicari berdasarkan nama/telepon. */
    public function all(string $search = ''): array
    {
        $stmt = $this->db->prepare('
            SELECT c.*,
                   (SELECT COUNT(*) FROM reservations r WHERE r.customer_id = c.id) AS reservation_count,
                   (SELECT COUNT(*) FROM restaurant_orders o WHERE o.customer_id = c.id) AS order_count
            FROM customers c
            WHERE c.name LIKE ? OR c.phone LIKE ?
            ORDER BY c.name');
        $keyword = '%' . $search . '%';
        $stmt->execute([$keyword, $keyword]);
        return $stmt->fetchAll();
    }

    /**
     * Menyimpan pelanggan baru (id null) atau mengubah pelanggan yang ada.
     * @return array daftar pesan error; kosong berarti berhasil
     */
    public function save(array $data, ?int $id = null): array
    {
        $errors = validateCustomerData($data);
        if ($errors) {
            return $errors;
        }
        if ($id !== null && $this->find($id) === null) {
            return ['Data pelanggan tidak ditemukan.'];
        }

        $params = [$data['name'], $data['phone'], $data['email'] !== '' ? $data['email'] : null];
        if ($id === null) {
            $stmt = $this->db->prepare('INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)');
        } else {
            $stmt = $this->db->prepare('UPDATE customers SET name = ?, phone = ?, email = ? WHERE id = ?');
            $params[] = $id;
        }
        $stmt->execute($params);
        return [];
    }

    public function delete(int $id): array
    {
        if ($this->find($id) === null) {
            return ['Data pelanggan tidak ditemukan.'];
        }
        if ($this->countRelated('reservations', 'customer_id', $id) > 0
            || $this->countRelated('restaurant_orders', 'customer_id', $id) > 0) {
            return ['Pelanggan tidak dapat dihapus karena masih memiliki data reservasi atau pesanan.'];
        }

        try {
            $stmt = $this->db->prepare('DELETE FROM customers WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            if ($this->isConstraintError($e)) {
                return ['Pelanggan tidak dapat dihapus karena masih digunakan oleh data lain.'];
            }
            throw $e;
        }
        return [];
    }
}
