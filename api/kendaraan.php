<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getKoneksi();

    if ($method === 'GET') {
        if ($action === 'get' && isset($_GET['id'])) {
            $id   = (int) $_GET['id'];
            $stmt = $pdo->prepare("SELECT * FROM kendaraan WHERE id = ?");
            $stmt->execute([$id]);
            $row  = $stmt->fetch();
            echo $row
                ? json_encode(['success' => true, 'data' => $row])
                : json_encode(['success' => false, 'message' => 'Tidak ditemukan']);
            exit;
        }

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $jenis  = trim($_GET['jenis']  ?? '');

        $sql    = "SELECT * FROM kendaraan WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql     .= " AND (nomor_plat LIKE ? OR merek LIKE ? OR model LIKE ? OR lokasi LIKE ?)";
            $like     = "%{$search}%";
            $params   = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($status !== '') { $sql .= " AND status = ?"; $params[] = $status; }
        if ($jenis  !== '') { $sql .= " AND jenis = ?";  $params[] = $jenis; }
        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $stat = $pdo->query("SELECT
            COUNT(*) AS total,
            SUM(status='Tersedia') AS tersedia,
            SUM(status='Dipakai')  AS dipakai,
            SUM(status='Servis')   AS servis
        FROM kendaraan")->fetch();

        echo json_encode(['success' => true, 'data' => $rows, 'stats' => $stat]);
        exit;
    }

    if ($method === 'POST') {
        $body   = json_decode(file_get_contents('php://input'), true);
        $action = $body['action'] ?? $action;
        $req    = fn(string $k) => trim($body[$k] ?? '');

        if ($action === 'tambah' || $action === 'edit') {
            $nomor_plat   = $req('nomor_plat');
            $merek        = $req('merek');
            $model        = $req('model');
            $tahun        = (int)($body['tahun'] ?? 0);
            $warna        = $req('warna');
            $jenis        = $req('jenis');
            $status       = $req('status');
            $kilometer    = (int)($body['kilometer'] ?? 0);
            $bahan_bakar  = $req('bahan_bakar');
            $lokasi       = $req('lokasi');
            $nomor_rangka = $req('nomor_rangka');
            $nomor_mesin  = $req('nomor_mesin');

            if (!$nomor_plat || !$merek || !$model || !$tahun) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Field wajib tidak boleh kosong']);
                exit;
            }

            if ($action === 'tambah') {
                $stmt = $pdo->prepare("INSERT INTO kendaraan
                    (nomor_plat, merek, model, tahun, warna, jenis, status, kilometer, bahan_bakar, lokasi, nomor_rangka, nomor_mesin)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nomor_plat, $merek, $model, $tahun, $warna, $jenis, $status, $kilometer, $bahan_bakar, $lokasi, $nomor_rangka, $nomor_mesin]);
                echo json_encode(['success' => true, 'message' => 'Kendaraan berhasil ditambahkan', 'id' => $pdo->lastInsertId()]);
            } else {
                $id = (int)($body['id'] ?? 0);
                if (!$id) { http_response_code(422); echo json_encode(['success' => false, 'message' => 'ID tidak valid']); exit; }
                $stmt = $pdo->prepare("UPDATE kendaraan SET
                    nomor_plat=?, merek=?, model=?, tahun=?, warna=?, jenis=?, status=?,
                    kilometer=?, bahan_bakar=?, lokasi=?, nomor_rangka=?, nomor_mesin=?
                    WHERE id=?");
                $stmt->execute([$nomor_plat, $merek, $model, $tahun, $warna, $jenis, $status, $kilometer, $bahan_bakar, $lokasi, $nomor_rangka, $nomor_mesin, $id]);
                echo json_encode(['success' => true, 'message' => 'Kendaraan berhasil diperbarui']);
            }
            exit;
        }

        if ($action === 'hapus') {
            $id = (int)($body['id'] ?? 0);
            if (!$id) { http_response_code(422); echo json_encode(['success' => false, 'message' => 'ID tidak valid']); exit; }
            $pdo->prepare("DELETE FROM kendaraan WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Kendaraan berhasil dihapus']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action tidak dikenal']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}