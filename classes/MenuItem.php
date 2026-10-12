<?php
require_once __DIR__ . '/BaseModel.php';

/** Mengelola data menu makanan dan minuman. */
class MenuItem extends BaseModel
{
    protected string $table = 'menu_items';

    public function all(string $search = ''): array
    {
        $stmt = $this->db->prepare('SELECT * FROM menu_items WHERE name LIKE ? ORDER BY name');
        $stmt->execute(['%' . $search . '%']);
        return $stmt->fetchAll();
    }

    /** Menu yang tersedia, diindeks berdasarkan id (dipakai saat membuat pesanan). */
    public function available(): array
    {
        $rows = $this->db->query('SELECT * FROM menu_items WHERE is_available = 1 ORDER BY name')->fetchAll();
        return array_column($rows, null, 'id');
    }

    public function save(array $data, ?int $id = null): array
    {
        $errors = validateMenuData($data);
        if ($errors) {
            return $errors;
        }
        if ($id !== null && $this->find($id) === null) {
            return ['Data menu tidak ditemukan.'];
        }

        $params = [$data['name'], (float) $data['price'], (int) $data['is_available']];
        if ($id === null) {
            $stmt = $this->db->prepare('INSERT INTO menu_items (name, price, is_available) VALUES (?, ?, ?)');
        } else {
            $stmt = $this->db->prepare('UPDATE menu_items SET name = ?, price = ?, is_available = ? WHERE id = ?');
            $params[] = $id;
        }
        $stmt->execute($params);
        return [];
    }

    public function delete(int $id): array
    {
        if ($this->find($id) === null) {
            return ['Data menu tidak ditemukan.'];
        }
        if ($this->countRelated('order_items', 'menu_item_id', $id) > 0) {
            return ['Menu tidak dapat dihapus karena sudah pernah dipesan. Ubah statusnya menjadi Tidak Tersedia.'];
        }

        try {
            $stmt = $this->db->prepare('DELETE FROM menu_items WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            if ($this->isConstraintError($e)) {
                return ['Menu tidak dapat dihapus karena masih digunakan oleh data lain.'];
            }
            throw $e;
        }
        return [];
    }
}
