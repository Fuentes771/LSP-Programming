<?php
require_once __DIR__ . '/BaseModel.php';

/** Mengelola pesanan menu beserta detail item-nya. */
class Order extends BaseModel
{
    protected string $table = 'restaurant_orders';

    private DateTimeImmutable $now;

    private const SELECT_WITH_RELATIONS = '
        SELECT o.*, c.name AS customer_name,
               r.reservation_date, r.reservation_time, t.table_number,
               (SELECT COALESCE(SUM(quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
        FROM restaurant_orders o
        JOIN customers c ON c.id = o.customer_id
        LEFT JOIN reservations r ON r.id = o.reservation_id
        LEFT JOIN restaurant_tables t ON t.id = r.table_id';

    public function __construct(PDO $db, ?DateTimeInterface $now = null)
    {
        parent::__construct($db);
        $this->now = $now !== null ? DateTimeImmutable::createFromInterface($now) : new DateTimeImmutable();
    }

    public function all(string $status = ''): array
    {
        if (in_array($status, ORDER_STATUSES, true)) {
            $stmt = $this->db->prepare(self::SELECT_WITH_RELATIONS . ' WHERE o.status = ? ORDER BY o.order_date DESC, o.id DESC');
            $stmt->execute([$status]);
            return $stmt->fetchAll();
        }
        return $this->db->query(self::SELECT_WITH_RELATIONS . ' ORDER BY o.order_date DESC, o.id DESC')->fetchAll();
    }

    public function between(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(self::SELECT_WITH_RELATIONS . '
            WHERE o.order_date BETWEEN ? AND ?
            ORDER BY o.order_date, o.id');
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM restaurant_orders WHERE status = ?');
        $stmt->execute([$status]);
        return (int) $stmt->fetchColumn();
    }

    /** Total nilai pesanan (selain Dibatalkan) pada tanggal tertentu. */
    public function revenueOn(string $date): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(total_amount), 0) FROM restaurant_orders WHERE order_date = ? AND status <> ?');
        $stmt->execute([$date, 'Dibatalkan']);
        return (float) $stmt->fetchColumn();
    }

    /** Detail satu pesanan beserta item-itemnya. */
    public function findWithItems(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT_WITH_RELATIONS . ' WHERE o.id = ?');
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
     * Jika pesanan terkait reservasi, tanggal pesanan otomatis mengikuti tanggal reservasi.
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
        $today = $this->now->format('Y-m-d');

        if ($reservationId !== '') {
            // Pesanan dari reservasi: pelanggan dan tanggal otomatis mengikuti reservasi
            $reservation = false;
            if (isPositiveInteger($reservationId)) {
                $stmt = $this->db->prepare('SELECT customer_id, status, reservation_date FROM reservations WHERE id = ?');
                $stmt->execute([(int) $reservationId]);
                $reservation = $stmt->fetch();
            }
            if ($reservation === false) {
                $errors[] = 'Reservasi yang dipilih tidak ditemukan.';
            } else {
                $reservationErrors = validateReservationForOrder($reservation, $today);
                $errors = array_merge($errors, $reservationErrors);
                if (!$reservationErrors) {
                    $customerId = (string) $reservation['customer_id'];
                    $orderDate = $reservation['reservation_date'];
                }
            }
        } else {
            // Pesanan tanpa reservasi (walk-in): pelanggan wajib dipilih, tanggal tidak boleh melebihi hari ini
            if (!isPositiveInteger($customerId)) {
                $errors[] = 'Pelanggan wajib dipilih.';
            } else {
                $stmt = $this->db->prepare('SELECT id FROM customers WHERE id = ?');
                $stmt->execute([(int) $customerId]);
                if ($stmt->fetch() === false) {
                    $errors[] = 'Pelanggan yang dipilih tidak ditemukan.';
                }
            }
            $errors = array_merge($errors, validateOrderDate($orderDate, $today));
        }

        $built = buildOrderItems($quantities, $menuItem->available());
        $errors = array_merge($errors, $built['errors']);
        if ($errors) {
            return ['errors' => $errors, 'id' => null];
        }

        $items = $built['items'];
        $total = calculateOrderTotal($items);

        // Jika dipanggil di dalam transaksi lain (misalnya bersama pembuatan pelanggan baru),
        // transaksi luar yang menentukan commit/rollback
        $ownTransaction = !$this->db->inTransaction();
        try {
            if ($ownTransaction) {
                $this->db->beginTransaction();
            }

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

            if ($ownTransaction) {
                $this->db->commit();
            }
        } catch (PDOException $e) {
            // Batalkan seluruh perubahan agar tidak ada pesanan yang tersimpan setengah
            if ($ownTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Gagal menyimpan pesanan: ' . $e->getMessage());
            return ['errors' => ['Pesanan gagal disimpan. Silakan coba lagi.'], 'id' => null];
        }

        return ['errors' => [], 'id' => $orderId];
    }

    /**
     * Mengubah status sesuai alur: Diproses → Selesai atau Dibatalkan.
     * Jika pesanan yang terkait reservasi diselesaikan dan tidak ada pesanan lain yang masih Diproses,
     * reservasinya otomatis ikut diselesaikan (dalam satu transaksi).
     *
     * @return array{errors: array, reservation_completed: bool}
     */
    public function updateStatus(int $id, string $status): array
    {
        $order = $this->find($id);
        if ($order === null) {
            return ['errors' => ['Data pesanan tidak ditemukan.'], 'reservation_completed' => false];
        }
        if (!canChangeStatus($order['status'], $status, ORDER_TRANSITIONS)) {
            return ['errors' => ['Status pesanan tidak dapat diubah dari ' . $order['status'] . ' menjadi ' . $status . '.'], 'reservation_completed' => false];
        }

        $reservationCompleted = false;
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('UPDATE restaurant_orders SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);

            if ($status === 'Selesai' && $order['reservation_id'] !== null) {
                $reservationId = (int) $order['reservation_id'];
                $statusStmt = $this->db->prepare('SELECT status FROM reservations WHERE id = ?');
                $statusStmt->execute([$reservationId]);
                $openStmt = $this->db->prepare('SELECT COUNT(*) FROM restaurant_orders WHERE reservation_id = ? AND status = ?');
                $openStmt->execute([$reservationId, 'Diproses']);

                if (shouldCompleteReservation((string) $statusStmt->fetchColumn(), (int) $openStmt->fetchColumn())) {
                    $this->db->prepare('UPDATE reservations SET status = ? WHERE id = ?')->execute(['Selesai', $reservationId]);
                    $reservationCompleted = true;
                }
            }
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }

        return ['errors' => [], 'reservation_completed' => $reservationCompleted];
    }
}
