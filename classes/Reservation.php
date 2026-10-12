<?php
require_once __DIR__ . '/BaseModel.php';

/** Mengelola reservasi meja beserta aturan kapasitas dan jadwal bentrok. */
class Reservation extends BaseModel
{
    protected string $table = 'reservations';

    private const SELECT_WITH_RELATIONS = '
        SELECT r.*, c.name AS customer_name, t.table_number, t.capacity
        FROM reservations r
        JOIN customers c ON c.id = r.customer_id
        JOIN restaurant_tables t ON t.id = r.table_id';

    public function all(): array
    {
        return $this->db->query(self::SELECT_WITH_RELATIONS . ' ORDER BY r.reservation_date DESC, r.reservation_time DESC')->fetchAll();
    }

    public function latest(int $limit = 5): array
    {
        $stmt = $this->db->prepare(self::SELECT_WITH_RELATIONS . ' ORDER BY r.created_at DESC, r.id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Reservasi yang belum dibatalkan, untuk dipilih (opsional) saat membuat pesanan. */
    public function selectable(): array
    {
        $stmt = $this->db->prepare(self::SELECT_WITH_RELATIONS . ' WHERE r.status <> ? ORDER BY r.reservation_date DESC, r.reservation_time DESC');
        $stmt->execute(['Dibatalkan']);
        return $stmt->fetchAll();
    }

    public function between(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(self::SELECT_WITH_RELATIONS . '
            WHERE r.reservation_date BETWEEN ? AND ?
            ORDER BY r.reservation_date, r.reservation_time');
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }

    /** Semua reservasi pada meja dan tanggal tertentu (bahan pengecekan bentrok). */
    private function onTableAndDate(int $tableId, string $date): array
    {
        $stmt = $this->db->prepare('
            SELECT id, table_id, reservation_date, reservation_time, status
            FROM reservations
            WHERE table_id = ? AND reservation_date = ?');
        $stmt->execute([$tableId, $date]);
        return $stmt->fetchAll();
    }

    public function save(array $data, ?int $id = null): array
    {
        $errors = validateReservationData($data);
        if ($errors) {
            return $errors;
        }
        if ($id !== null && $this->find($id) === null) {
            return ['Data reservasi tidak ditemukan.'];
        }

        $customerStmt = $this->db->prepare('SELECT id FROM customers WHERE id = ?');
        $customerStmt->execute([(int) $data['customer_id']]);
        if ($customerStmt->fetch() === false) {
            return ['Pelanggan yang dipilih tidak ditemukan.'];
        }

        $tableId = (int) $data['table_id'];
        $tableStmt = $this->db->prepare('SELECT * FROM restaurant_tables WHERE id = ?');
        $tableStmt->execute([$tableId]);
        $table = $tableStmt->fetch();
        if ($table === false) {
            return ['Meja yang dipilih tidak ditemukan.'];
        }

        $isActive = in_array($data['status'], ACTIVE_RESERVATION_STATUSES, true);
        if ($isActive && $table['status'] !== 'Aktif') {
            $errors[] = 'Meja ' . $table['table_number'] . ' sedang Nonaktif dan tidak dapat direservasi.';
        }
        if (!validateGuestCount($data['guest_count'], $table['capacity'])) {
            $errors[] = 'Jumlah tamu (' . (int) $data['guest_count'] . ') melebihi kapasitas meja '
                . $table['table_number'] . ' (' . $table['capacity'] . ' orang).';
        }
        if ($isActive && hasReservationConflict(
            $this->onTableAndDate($tableId, $data['reservation_date']),
            $tableId,
            $data['reservation_date'],
            $data['reservation_time'],
            $id
        )) {
            $errors[] = 'Meja ' . $table['table_number'] . ' sudah memiliki reservasi aktif pada tanggal dan waktu tersebut.';
        }
        if ($errors) {
            return $errors;
        }

        $params = [
            (int) $data['customer_id'],
            $tableId,
            $data['reservation_date'],
            normalizeTime($data['reservation_time']),
            (int) $data['guest_count'],
            $data['status'],
            $data['notes'] !== '' ? $data['notes'] : null,
        ];
        if ($id === null) {
            $stmt = $this->db->prepare('
                INSERT INTO reservations (customer_id, table_id, reservation_date, reservation_time, guest_count, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)');
        } else {
            $stmt = $this->db->prepare('
                UPDATE reservations
                SET customer_id = ?, table_id = ?, reservation_date = ?, reservation_time = ?, guest_count = ?, status = ?, notes = ?
                WHERE id = ?');
            $params[] = $id;
        }
        $stmt->execute($params);
        return [];
    }

    /** Membatalkan reservasi (status menjadi Dibatalkan, data tidak dihapus). */
    public function cancel(int $id): array
    {
        $reservation = $this->find($id);
        if ($reservation === null) {
            return ['Data reservasi tidak ditemukan.'];
        }
        if (!in_array($reservation['status'], ACTIVE_RESERVATION_STATUSES, true)) {
            return ['Hanya reservasi berstatus Menunggu atau Dikonfirmasi yang dapat dibatalkan.'];
        }

        $stmt = $this->db->prepare('UPDATE reservations SET status = ? WHERE id = ?');
        $stmt->execute(['Dibatalkan', $id]);
        return [];
    }
}
