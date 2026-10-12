<?php
require_once __DIR__ . '/BaseModel.php';

/** Mengelola pesanan menu beserta detail item-nya. */
class Order extends BaseModel
{
    protected string $table = 'restaurant_orders';

    private const SELECT_WITH_CUSTOMER = '
        SELECT o.*, c.name AS customer_name,
               (SELECT COALESCE(SUM(quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
        FROM restaurant_orders o
        JOIN customers c ON c.id = o.customer_id';

    public function all(): array
    {
        return $this->db->query(self::SELECT_WITH_CUSTOMER . ' ORDER BY o.order_date DESC, o.id DESC')->fetchAll();
    }

    public function between(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(self::SELECT_WITH_CUSTOMER . '
            WHERE o.order_date BETWEEN ? AND ?
            ORDER BY o.order_date, o.id');
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    /** Detail satu pesanan beserta item-itemnya. */
    public function findWithItems(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT_WITH_CUSTOMER . ' WHERE o.id = ?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if ($order === false) {
            return null;
        }

        $itemStmt = $this->db->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
        $itemStmt->execute([$id]);
        $order['items'] = $itemStmt->fetchAll();
        return $order;
    }

    /**
     * Membuat pesanan baru. Pesanan dan seluruh item disimpan dalam satu transaksi.
     *
     * @param array $quantities kuantitas per menu_id dari form
     * @return array{errors: array, id: int|null}
     */
    public function create(array $data, array $quantities, MenuItem $menuItem): array
    {
        $errors = [];
        $customerId = $data['customer_id'] ?? '';
        $reservationId = $data['reservation_id'] ?? '';
        $orderDate = $data['order_date'] ?? '';

        if (!isPositiveInteger($customerId)) {
            $errors[] = 'Pelanggan wajib dipilih.';
        } else {
            $stmt = $this->db->prepare('SELECT id FROM customers WHERE id = ?');
            $stmt->execute([(int) $customerId]);
            if ($stmt->fetch() === false) {
                $errors[] = 'Pelanggan yang dipilih tidak ditemukan.';
            }
        }
        if (!isValidDate($orderDate)) {
            $errors[] = 'Tanggal pesanan tidak valid.';
        }
        if ($reservationId !== '') {
            $reservation = false;
            if (isPositiveInteger($reservationId)) {
                $stmt = $this->db->prepare('SELECT customer_id, status FROM reservations WHERE id = ?');
                $stmt->execute([(int) $reservationId]);
                $reservation = $stmt->fetch();
            }
            if ($reservation === false) {
                $errors[] = 'Reservasi yang dipilih tidak ditemukan.';
            } elseif ((int) $reservation['customer_id'] !== (int) $customerId) {
                $errors[] = 'Reservasi yang dipilih bukan milik pelanggan tersebut.';
            } elseif ($reservation['status'] === 'Dibatalkan') {
                $errors[] = 'Reservasi yang dipilih sudah dibatalkan.';
            }
        }

        $built = buildOrderItems($quantities, $menuItem->available());
        $errors = array_merge($errors, $built['errors']);
        if ($errors) {
            return ['errors' => $errors, 'id' => null];
        }

        $items = $built['items'];
        $total = calculateOrderTotal($items);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('
                INSERT INTO restaurant_orders (customer_id, reservation_id, order_date, status, total_amount)
                VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                (int) $customerId,
                $reservationId !== '' ? (int) $reservationId : null,
                $orderDate,
                'Diproses',
                $total,
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare('
                INSERT INTO order_items (order_id, menu_item_id, menu_name, price, quantity, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($items as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['menu_item_id'],
                    $item['menu_name'],
                    $item['price'],
                    $item['quantity'],
                    $item['subtotal'],
                ]);
            }

            $this->db->commit();
        } catch (PDOException $e) {
            // Batalkan seluruh perubahan agar tidak ada pesanan yang tersimpan setengah
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Gagal menyimpan pesanan: ' . $e->getMessage());
            return ['errors' => ['Pesanan gagal disimpan. Silakan coba lagi.'], 'id' => null];
        }

        return ['errors' => [], 'id' => $orderId];
    }

    public function updateStatus(int $id, string $status): array
    {
        if (!in_array($status, ORDER_STATUSES, true)) {
            return ['Status pesanan tidak valid.'];
        }
        if ($this->find($id) === null) {
            return ['Data pesanan tidak ditemukan.'];
        }

        $stmt = $this->db->prepare('UPDATE restaurant_orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        return [];
    }
}
