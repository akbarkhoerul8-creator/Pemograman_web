<?php
/**
 * api/jadwal_servis.php
 * CRUD jadwal servis — mengembalikan JSON.
 */
ob_start();
require_once __DIR__ . '/../config/database.php';
ob_clean();

header('Content-Type: application/json; charset=utf-8');

function jsonOut(bool $ok, string $msg, array $extra = []): void {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    jsonOut(false, 'Unauthorized');
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getKoneksi();

    /* ── Auto-create tabel jika belum ada ── */
    $pdo->exec("CREATE TABLE IF NOT EXISTS jadwal_servis (
        id               INT          NOT NULL AUTO_INCREMENT,
        kendaraan_id     INT          NOT NULL,
        tanggal_jadwal   DATE         NOT NULL,
        jenis_servis     VARCHAR(100) NOT NULL DEFAULT '',
        bengkel          VARCHAR(100) NOT NULL DEFAULT '',
        estimasi_biaya   INT          NOT NULL DEFAULT 0,
        status           ENUM('Terjadwal','Selesai','Dibatalkan') NOT NULL DEFAULT 'Terjadwal',
        catatan          TEXT         NULL,
        created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        CONSTRAINT fk_jadwal_kend
            FOREIGN KEY (kendaraan_id) REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* ── GET ── */
    if ($method === 'GET') {

        if ($action === 'get' && isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT j.*, k.nomor_plat, k.merek, k.model
                FROM jadwal_servis j JOIN kendaraan k ON k.id = j.kendaraan_id
                WHERE j.id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $row = $stmt->fetch();
            $row ? jsonOut(true, 'ok', ['data' => $row])
                 : jsonOut(false, 'Tidak ditemukan');
        }

        $status = trim($_GET['status'] ?? '');
        $search = trim($_GET['search'] ?? '');
        $sql    = "SELECT j.*, k.nomor_plat, k.merek, k.model
                   FROM jadwal_servis j
                   JOIN kendaraan k ON k.id = j.kendaraan_id
                   WHERE 1=1";
        $params = [];

        if ($status !== '') { $sql .= " AND j.status = ?";  $params[] = $status; }
        if ($search !== '') {
            $sql .= " AND (k.nomor_plat LIKE ? OR k.merek LIKE ? OR j.jenis_servis LIKE ? OR j.bengkel LIKE ?)";
            $like  = "%{$search}%";
            array_push($params, $like, $like, $like, $like);
        }
        $sql .= " ORDER BY j.tanggal_jadwal ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $stat = $pdo->query("SELECT
            COUNT(*) AS total,
            SUM(status='Terjadwal')  AS terjadwal,
            SUM(status='Selesai')    AS selesai,
            SUM(status='Dibatalkan') AS dibatalkan
        FROM jadwal_servis")->fetch();

        jsonOut(true, 'ok', ['data' => $rows, 'stats' => $stat]);
    }

    /* ── POST ── */
    if ($method === 'POST') {
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $body['action'] ?? $action;
        $req    = fn($k) => trim($body[$k] ?? '');

        if ($action === 'tambah') {
            $kid    = (int)($body['kendaraan_id'] ?? 0);
            $tgl    = $req('tanggal_jadwal');
            $jenis  = $req('jenis_servis');
            if (!$kid || !$tgl || !$jenis) {
                http_response_code(422);
                jsonOut(false, 'Kendaraan, tanggal, dan jenis servis wajib diisi');
            }
            $pdo->prepare("INSERT INTO jadwal_servis
                (kendaraan_id, tanggal_jadwal, jenis_servis, bengkel, estimasi_biaya, status, catatan)
                VALUES (?, ?, ?, ?, ?, 'Terjadwal', ?)")
                ->execute([$kid, $tgl, $jenis, $req('bengkel'), (int)($body['estimasi_biaya'] ?? 0), $req('catatan')]);
            jsonOut(true, 'Jadwal berhasil ditambahkan', ['id' => $pdo->lastInsertId()]);
        }

        if ($action === 'edit') {
            $id  = (int)($body['id'] ?? 0);
            $kid = (int)($body['kendaraan_id'] ?? 0);
            $tgl = $req('tanggal_jadwal');
            $jenis = $req('jenis_servis');
            if (!$id || !$kid || !$tgl || !$jenis) {
                http_response_code(422); jsonOut(false, 'Data tidak lengkap');
            }
            $pdo->prepare("UPDATE jadwal_servis SET
                kendaraan_id=?, tanggal_jadwal=?, jenis_servis=?,
                bengkel=?, estimasi_biaya=?, catatan=? WHERE id=?")
                ->execute([$kid, $tgl, $jenis, $req('bengkel'), (int)($body['estimasi_biaya'] ?? 0), $req('catatan'), $id]);
            jsonOut(true, 'Jadwal berhasil diperbarui');
        }

        if ($action === 'selesai') {
            $id = (int)($body['id'] ?? 0);
            if (!$id) { http_response_code(422); jsonOut(false, 'ID tidak valid'); }

            $jadwal = $pdo->prepare("SELECT * FROM jadwal_servis WHERE id = ?");
            $jadwal->execute([$id]);
            $j = $jadwal->fetch();
            if (!$j) jsonOut(false, 'Jadwal tidak ditemukan');

            $pdo->prepare("UPDATE jadwal_servis SET status='Selesai' WHERE id=?")->execute([$id]);

            // Catat otomatis ke riwayat_servis
            $biaya = (int)($body['biaya_aktual'] ?? 0) ?: $j['estimasi_biaya'];
            $cat   = trim($body['catatan'] ?? '') ?: $j['catatan'];

            // Pastikan kolom bengkel & kilometer ada
            $cols = array_column($pdo->query("SHOW COLUMNS FROM riwayat_servis")->fetchAll(), 'Field');
            if (!in_array('bengkel',   $cols)) $pdo->exec("ALTER TABLE riwayat_servis ADD COLUMN bengkel   VARCHAR(100) NOT NULL DEFAULT '' AFTER jenis_servis");
            if (!in_array('kilometer', $cols)) $pdo->exec("ALTER TABLE riwayat_servis ADD COLUMN kilometer INT NOT NULL DEFAULT 0 AFTER bengkel");

            $pdo->prepare("INSERT INTO riwayat_servis (kendaraan_id, tanggal_servis, jenis_servis, bengkel, biaya, catatan)
                VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$j['kendaraan_id'], $j['tanggal_jadwal'], $j['jenis_servis'], $j['bengkel'], $biaya, $cat]);

            jsonOut(true, 'Jadwal selesai & riwayat servis diperbarui');
        }

        if ($action === 'batal') {
            $id = (int)($body['id'] ?? 0);
            if (!$id) { http_response_code(422); jsonOut(false, 'ID tidak valid'); }
            $pdo->prepare("UPDATE jadwal_servis SET status='Dibatalkan' WHERE id=?")->execute([$id]);
            jsonOut(true, 'Jadwal dibatalkan');
        }

        if ($action === 'hapus') {
            $id = (int)($body['id'] ?? 0);
            if (!$id) { http_response_code(422); jsonOut(false, 'ID tidak valid'); }
            $pdo->prepare("DELETE FROM jadwal_servis WHERE id=?")->execute([$id]);
            jsonOut(true, 'Jadwal berhasil dihapus');
        }

        http_response_code(400);
        jsonOut(false, 'Action tidak dikenal');
    }

    http_response_code(405);
    jsonOut(false, 'Method not allowed');

} catch (Exception $e) {
    http_response_code(500);
    jsonOut(false, 'Server error: ' . $e->getMessage());
}
