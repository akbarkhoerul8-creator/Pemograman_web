<?php
/**
 * api/riwayat_penggunaan.php
 * CRUD riwayat penggunaan kendaraan — mengembalikan JSON.
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
    jsonOut(false, 'Sesi habis, silakan login ulang.');
}

$pdo = getKoneksi();

$method = $_SERVER['REQUEST_METHOD'];

/* ── Auto-create tabel jika belum ada ── */
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS riwayat_penggunaan (
        id               INT          NOT NULL AUTO_INCREMENT,
        kendaraan_id     INT          NOT NULL,
        nama_pengemudi   VARCHAR(100) NOT NULL,
        tujuan           VARCHAR(255) NOT NULL DEFAULT '',
        tanggal_mulai    DATE         NOT NULL,
        tanggal_selesai  DATE         NULL,
        km_awal          INT          NULL,
        km_akhir         INT          NULL,
        keterangan       TEXT         NULL,
        created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        CONSTRAINT fk_rp_kend FOREIGN KEY (kendaraan_id)
            REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // Tabel mungkin sudah ada — lanjut
}

/* ══════════ GET — list / get single ══════════ */
if ($method === 'GET') {

    // ── Ambil 1 data (untuk form edit) ──
    if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM riwayat_penggunaan WHERE id = ?");
        $stmt->execute([(int)$_GET['id']]);
        $data = $stmt->fetch();

        if (!$data) jsonOut(false, 'Data tidak ditemukan.');
        jsonOut(true, 'OK', ['data' => $data]);
    }

    // ── List dengan search & filter ──
    $search = trim($_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? ''); // 'berjalan' | 'selesai'

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(k.nomor_plat LIKE ? OR k.merek LIKE ? OR k.model LIKE ? OR rp.nama_pengemudi LIKE ? OR rp.tujuan LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like, $like, $like);
    }

    if ($status === 'berjalan') {
        $where[] = "rp.tanggal_selesai IS NULL";
    } elseif ($status === 'selesai') {
        $where[] = "rp.tanggal_selesai IS NOT NULL";
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "SELECT rp.*, k.nomor_plat, k.merek, k.model
            FROM riwayat_penggunaan rp
            JOIN kendaraan k ON k.id = rp.kendaraan_id
            $whereSql
            ORDER BY rp.tanggal_mulai DESC, rp.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    // ── Stats ──
    $statTotal    = (int)$pdo->query("SELECT COUNT(*) FROM riwayat_penggunaan")->fetchColumn();
    $statBerjalan = (int)$pdo->query("SELECT COUNT(*) FROM riwayat_penggunaan WHERE tanggal_selesai IS NULL")->fetchColumn();
    $statSelesai  = (int)$pdo->query("SELECT COUNT(*) FROM riwayat_penggunaan WHERE tanggal_selesai IS NOT NULL")->fetchColumn();
    $statJarak    = (int)$pdo->query("SELECT COALESCE(SUM(km_akhir - km_awal),0) FROM riwayat_penggunaan WHERE km_akhir IS NOT NULL AND km_awal IS NOT NULL")->fetchColumn();

    jsonOut(true, 'OK', [
        'data'  => $data,
        'stats' => [
            'total'    => $statTotal,
            'berjalan' => $statBerjalan,
            'selesai'  => $statSelesai,
            'jarak'    => $statJarak,
        ],
    ]);
}

/* ══════════ POST — tambah / edit / selesai / hapus ══════════ */
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';

    if ($action === 'tambah') {
        $kendaraan_id    = (int)($body['kendaraan_id'] ?? 0);
        $nama_pengemudi  = trim($body['nama_pengemudi'] ?? '');
        $tujuan          = trim($body['tujuan'] ?? '');
        $tanggal_mulai   = trim($body['tanggal_mulai'] ?? '');
        $tanggal_selesai = trim($body['tanggal_selesai'] ?? '');
        $km_awal         = ($body['km_awal'] ?? '') !== '' ? (int)$body['km_awal'] : null;
        $km_akhir        = ($body['km_akhir'] ?? '') !== '' ? (int)$body['km_akhir'] : null;

        if (!$kendaraan_id || !$nama_pengemudi || !$tanggal_mulai) {
            jsonOut(false, 'Kendaraan, nama pengemudi, dan tanggal mulai wajib diisi.');
        }

        $stmt = $pdo->prepare("INSERT INTO riwayat_penggunaan
            (kendaraan_id, nama_pengemudi, tujuan, tanggal_mulai, tanggal_selesai, km_awal, km_akhir)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $kendaraan_id, $nama_pengemudi, $tujuan, $tanggal_mulai,
            $tanggal_selesai ?: null, $km_awal, $km_akhir,
        ]);

        jsonOut(true, 'Riwayat penggunaan berhasil ditambahkan.');
    }

    if ($action === 'edit') {
        $id              = (int)($body['id'] ?? 0);
        $kendaraan_id    = (int)($body['kendaraan_id'] ?? 0);
        $nama_pengemudi  = trim($body['nama_pengemudi'] ?? '');
        $tujuan          = trim($body['tujuan'] ?? '');
        $tanggal_mulai   = trim($body['tanggal_mulai'] ?? '');
        $tanggal_selesai = trim($body['tanggal_selesai'] ?? '');
        $km_awal         = $body['km_awal'] !== '' ? (int)$body['km_awal'] : null;
        $km_akhir        = ($body['km_akhir'] ?? '') !== '' ? (int)$body['km_akhir'] : null;

        if (!$id || !$kendaraan_id || !$nama_pengemudi || !$tanggal_mulai) {
            jsonOut(false, 'Kendaraan, nama pengemudi, dan tanggal mulai wajib diisi.');
        }

        $stmt = $pdo->prepare("UPDATE riwayat_penggunaan SET
            kendaraan_id = ?, nama_pengemudi = ?, tujuan = ?, tanggal_mulai = ?,
            tanggal_selesai = ?, km_awal = ?, km_akhir = ?
            WHERE id = ?");
        $stmt->execute([
            $kendaraan_id, $nama_pengemudi, $tujuan, $tanggal_mulai,
            $tanggal_selesai ?: null, $km_awal, $km_akhir, $id,
        ]);

        jsonOut(true, 'Riwayat penggunaan berhasil diperbarui.');
    }

    // Tandai selesai — isi tanggal_selesai & km_akhir hari ini
    if ($action === 'selesai') {
        $id       = (int)($body['id'] ?? 0);
        $km_akhir = ($body['km_akhir'] ?? '') !== '' ? (int)$body['km_akhir'] : null;

        if (!$id) jsonOut(false, 'ID tidak valid.');

        $stmt = $pdo->prepare("UPDATE riwayat_penggunaan
            SET tanggal_selesai = CURDATE(), km_akhir = COALESCE(?, km_akhir)
            WHERE id = ?");
        $stmt->execute([$km_akhir, $id]);

        jsonOut(true, 'Penggunaan ditandai selesai.');
    }

    if ($action === 'hapus') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) jsonOut(false, 'ID tidak valid.');

        $stmt = $pdo->prepare("DELETE FROM riwayat_penggunaan WHERE id = ?");
        $stmt->execute([$id]);

        jsonOut(true, 'Riwayat penggunaan berhasil dihapus.');
    }

    jsonOut(false, 'Aksi tidak dikenali.');
}

jsonOut(false, 'Method tidak diizinkan.');