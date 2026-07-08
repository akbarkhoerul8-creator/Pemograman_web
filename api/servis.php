<?php
/**
 * api/servis.php
 * CRUD riwayat_servis — mengembalikan JSON.
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

try {
    $pdo = getKoneksi();

    /* ── Pastikan kolom bengkel & kilometer ada (auto-migrate) ── */
    $cols = array_column($pdo->query("SHOW COLUMNS FROM riwayat_servis")->fetchAll(), 'Field');
    if (!in_array('bengkel', $cols)) {
        $pdo->exec("ALTER TABLE riwayat_servis ADD COLUMN bengkel VARCHAR(100) NOT NULL DEFAULT '' AFTER jenis_servis");
    }
    if (!in_array('kilometer', $cols)) {
        $pdo->exec("ALTER TABLE riwayat_servis ADD COLUMN kilometer INT NOT NULL DEFAULT 0 AFTER bengkel");
    }

    /* ── GET: ambil list servis kendaraan tertentu ── */
    if ($method === 'GET') {
        $kid  = (int)($_GET['kendaraan_id'] ?? 0);
        $sql  = $kid
            ? "SELECT * FROM riwayat_servis WHERE kendaraan_id = ? ORDER BY tanggal_servis DESC"
            : "SELECT * FROM riwayat_servis ORDER BY tanggal_servis DESC LIMIT 50";
        $stmt = $kid ? $pdo->prepare($sql) : $pdo->query($sql);
        if ($kid) $stmt->execute([$kid]);
        jsonOut(true, 'ok', ['data' => $stmt->fetchAll()]);
    }

    /* ── POST: tambah / hapus ── */
    if ($method === 'POST') {
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $body['action'] ?? '';
        $req    = fn($k) => trim($body[$k] ?? '');

        /* Tambah */
        if ($action === 'tambah') {
            $kendaraan_id = (int)($body['kendaraan_id'] ?? 0);
            $tanggal      = $req('tanggal_servis');
            $jenis        = $req('jenis_servis');
            $bengkel      = $req('bengkel');
            $kilometer    = (int)($body['kilometer'] ?? 0);
            $biaya        = (int)($body['biaya']     ?? 0);
            $catatan      = $req('catatan');

            if (!$kendaraan_id || !$tanggal || !$jenis) {
                http_response_code(422);
                jsonOut(false, 'Tanggal dan jenis servis wajib diisi');
            }

            $pdo->prepare("INSERT INTO riwayat_servis
                (kendaraan_id, tanggal_servis, jenis_servis, bengkel, kilometer, biaya, catatan)
                VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$kendaraan_id, $tanggal, $jenis, $bengkel, $kilometer, $biaya, $catatan]);

            // Update odometer kendaraan kalau km servis lebih besar dari km saat ini
            if ($kilometer > 0) {
                $pdo->prepare("UPDATE kendaraan SET kilometer = ? WHERE id = ? AND kilometer < ?")
                    ->execute([$kilometer, $kendaraan_id, $kilometer]);
            }

            jsonOut(true, 'Riwayat servis berhasil ditambahkan', ['id' => $pdo->lastInsertId()]);
        }

        /* Hapus */
        if ($action === 'hapus') {
            $id = (int)($body['id'] ?? 0);
            if (!$id) { http_response_code(422); jsonOut(false, 'ID tidak valid'); }
            $pdo->prepare("DELETE FROM riwayat_servis WHERE id = ?")->execute([$id]);
            jsonOut(true, 'Data servis dihapus');
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
