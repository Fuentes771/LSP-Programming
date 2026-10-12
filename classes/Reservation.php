<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Mengelola reservasi meja beserta aturannya:
 * jam operasional, tidak boleh di waktu yang sudah lewat, kapasitas meja,
 * jadwal bentrok (slot 2 jam), dan alur status Menunggu → Dikonfirmasi → Selesai.
 */
class Reservation extends BaseModel
{
    protected string $table = 'reservations';

    /** Waktu "sekarang" dapat disuntikkan agar perilaku berbasis waktu mudah diuji. */
    private DateTimeImmutable $now;

    private const SELECT_WITH_RELATIONS = '
        SELECT r.*, c.name AS customer_name, c.phone AS customer_phone, t.table_number, t.capacity,
               (SELECT COUNT(*) FROM restaurant_orders o
                WHERE o.reservation_id = r.id AND o.status <> \'Dibatalkan\') AS order_count,
               (SELECT COUNT(*) FROM restaurant_orders o
                WHERE o.reservation_id = r.id AND o.status = \'Diproses\') AS open_order_count
        FROM reservations r
        JOIN customers c ON c.id = r.customer_id
        JOIN restaurant_tables t ON t.id = r.table_id';

    public function __construct(PDO $db, ?DateTimeInterface $now = null)
    {
        parent::__construct($db);
        $this->now = $now !== null ? DateTimeImmutable::createFromInterface($now) : new DateTimeImmutable();
    }

    private function today(): string
    {
        return $this->now->format('Y-m-d');
    }

    /**
     * Daftar reservasi.
     * $scope: 'today' (hari ini), 'upcoming' (hari ini dan seterusnya), 'all' (semua).
     */
    public function all(string $scope = 'upcoming', string $status = ''): array
    {
        $where = [];
        $params = [];
        if ($scope === 'today') {
            $where[] = 'r.reservation_date = ?';
            $params[] = $this->today();
        } elseif ($scope === 'upcoming') {
            $where[] = 'r.reservation_date >= ?';
            $params[] = $this->today();
        }
        if (in_array($status, RESERVATION_STATUSES, true)) {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }

        $sql = self::SELECT_WITH_RELATIONS . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
        $sql .= $scope === 'all'
            ? ' ORDER BY r.reservation_date DESC, r.reservation_time DESC'
            : ' ORDER BY r.reservation_date, r.reservation_time';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findWithRelations(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT_WITH_RELATIONS . ' WHERE r.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Jumlah reservasi (selain Dibatalkan) pada tanggal tertentu. */
    public function countOn(string $date): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM reservations WHERE reservation_date = ? AND status <> ?');
        $stmt->execute([$date, 'Dibatalkan']);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Reservasi yang boleh dibuatkan pesanan: sudah Dikonfirmasi (tamu datang)
     * dan tanggalnya dalam 30 hari terakhir sampai hari ini.
     */
    public function forOrder(): array
    {
        $stmt = $this->db->prepare(self::SELECT_WITH_RELATIONS . '
            WHERE r.status = ? AND r.reservation_date BETWEEN ? AND ?
            ORDER BY r.reservation_date DESC, r.reservation_time DESC');
        $stmt->execute([
            'Dikonfirmasi',
            $this->now->modify('-30 days')->format('Y-m-d'),
            $this->today(),
        ]);
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

    /** Jumlah pesanan (selain Dibatalkan) yang tertaut ke reservasi ini. */
    private function countActiveOrders(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM restaurant_orders WHERE reservation_id = ? AND status <> ?');
        $stmt->execute([$id, 'Dibatalkan']);
        return (int) $stmt->fetchColumn();
    }

    /** Jumlah pesanan yang masih Diproses untuk reservasi ini. */
    private function countOpenOrders(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM restaurant_orders WHERE reservation_id = ? AND status = ?');
        $stmt->execute([$id, 'Diproses']);
        return (int) $stmt->fetchColumn();
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

    /** Membuat reservasi baru. Status awal selalu Menunggu. */
    public function create(array $data): array
    {
        return $this->persist($data, null);
    }

    /** Mengubah data reservasi. Hanya reservasi aktif (Menunggu/Dikonfirmasi) yang dapat diubah. */
    public function update(int $id, array $data): array
    {
        $existing = $this->find($id);
        if ($existing === null) {
            return ['Data reservasi tidak ditemukan.'];
        }
        if (!in_array($existing['status'], ACTIVE_RESERVATION_STATUSES, true)) {
            return ['Reservasi yang sudah ' . $existing['status'] . ' tidak dapat diubah.'];
        }
        return $this->persist($data, $existing);
    }

    private function persist(array $data, ?array $existing): array
    {
        $id = $existing !== null ? (int) $existing['id'] : null;

        // Cek "sudah lewat" hanya untuk reservasi baru atau jika tanggal/jamnya diubah
        $checkPast = $existing === null
            || $existing['reservation_date'] !== $data['reservation_date']
            || normalizeTime($existing['reservation_time']) !== normalizeTime($data['reservation_time']);

        $errors = validateReservationData($data, $this->now, $checkPast);
        if ($errors) {
            return $errors;
        }

        $customerStmt = $this->db->prepare('SELECT id FROM customers WHERE id = ?');
        $customerStmt->execute([(int) $data['customer_id']]);
        if ($customerStmt->fetch() === false) {
            return ['Pelanggan yang dipilih tidak ditemukan.'];
        }
        if ($existing !== null && $this->countActiveOrders($id) > 0) {
            // Pesanan mengikuti pelanggan dan tanggal reservasi, jadi keduanya dikunci setelah ada pesanan
            if ((int) $existing['customer_id'] !== (int) $data['customer_id']) {
                return ['Pelanggan tidak dapat diganti karena reservasi ini sudah memiliki pesanan.'];
            }
            if ($existing['reservation_date'] !== $data['reservation_date']) {
                return ['Tanggal tidak dapat diubah karena reservasi ini sudah memiliki pesanan.'];
            }
        }

        $tableId = (int) $data['table_id'];
        $tableStmt = $this->db->prepare('SELECT * FROM restaurant_tables WHERE id = ?');
        $tableStmt->execute([$tableId]);
        $table = $tableStmt->fetch();
        if ($table === false) {
            return ['Meja yang dipilih tidak ditemukan.'];
        }

        if ($table['status'] !== 'Aktif') {
            $errors[] = 'Meja ' . $table['table_number'] . ' sedang Nonaktif dan tidak dapat direservasi.';
        }
        if (!validateGuestCount($data['guest_count'], $table['capacity'])) {
            $errors[] = 'Jumlah tamu (' . (int) $data['guest_count'] . ') melebihi kapasitas meja '
                . $table['table_number'] . ' (' . $table['capacity'] . ' orang).';
        }
        if (hasReservationConflict(
            $this->onTableAndDate($tableId, $data['reservation_date']),
            $tableId,
            $data['reservation_date'],
            $data['reservation_time'],
            $id
        )) {
            $errors[] = 'Meja ' . $table['table_number'] . ' sudah dipesan pada rentang waktu tersebut. '
                . 'Setiap reservasi memakai meja selama ' . (RESERVATION_DURATION_MINUTES / 60) . ' jam; pilih jam atau meja lain.';
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
            $data['notes'] !== '' ? $data['notes'] : null,
        ];
        if ($id === null) {
            $stmt = $this->db->prepare('
                INSERT INTO reservations (customer_id, table_id, reservation_date, reservation_time, guest_count, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)');
            $params[] = 'Menunggu';
        } else {
            $stmt = $this->db->prepare('
                UPDATE reservations
                SET customer_id = ?, table_id = ?, reservation_date = ?, reservation_time = ?, guest_count = ?, notes = ?
                WHERE id = ?');
            $params[] = $id;
        }
        $stmt->execute($params);
        return [];
    }

    /** Mengubah status sesuai alur: Menunggu → Dikonfirmasi → Selesai, atau dibatalkan selama masih aktif. */
    public function changeStatus(int $id, string $status): array
    {
        $reservation = $this->find($id);
        if ($reservation === null) {
            return ['Data reservasi tidak ditemukan.'];
        }
        if (!canChangeStatus($reservation['status'], $status, RESERVATION_TRANSITIONS)) {
            return ['Status reservasi tidak dapat diubah dari ' . $reservation['status'] . ' menjadi ' . $status . '.'];
        }
        if ($status === 'Dikonfirmasi' && $reservation['reservation_date'] < $this->today()) {
            return ['Reservasi yang tanggalnya sudah lewat tidak dapat dikonfirmasi. Batalkan reservasi tersebut.'];
        }
        if ($status === 'Selesai' && $reservation['reservation_date'] > $this->today()) {
            return ['Reservasi belum dapat diselesaikan sebelum tanggal reservasinya.'];
        }
        if ($status === 'Selesai' && $this->countOpenOrders($id) > 0) {
            return ['Reservasi belum dapat diselesaikan karena masih ada pesanan yang Diproses. Selesaikan pesanannya; reservasi akan ikut selesai otomatis.'];
        }
        if ($status === 'Dibatalkan' && $this->countActiveOrders($id) > 0) {
            return ['Reservasi tidak dapat dibatalkan karena sudah memiliki pesanan. Batalkan pesanannya terlebih dahulu.'];
        }

        $stmt = $this->db->prepare('UPDATE reservations SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        return [];
    }
}
