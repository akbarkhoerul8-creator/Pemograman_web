<?php
/**
 * api/upload_foto.php
 * Upload foto kendaraan — simpan ke /uploads/kendaraan/, update kolom foto di DB.
 * Dipanggil via fetch() FormData dari pages/detail.php.
 */

// Buffer output agar tidak ada whitespace yang merusak header JSON
ob_start();

require_once __DIR__ . '/../config/database.php';

// Buang apapun yang mungkin sudah tercetak oleh database.php
ob_clean();

header('Content-Type: application/json; charset=utf-8');

/* ── Helper: kirim JSON dan exit ── */
function jsonOut(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

/* ── Auth ── */
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    jsonOut(false, 'Unauthorized');
}

/* ── Validasi ID ── */
$id = (int)($_POST['kendaraan_id'] ?? 0);
if ($id <= 0) {
    jsonOut(false, 'ID kendaraan tidak valid');
}

/* ── Validasi file ── */
if (empty($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    $errMsg = [
        UPLOAD_ERR_INI_SIZE   => 'File melebihi batas upload_max_filesize server',
        UPLOAD_ERR_FORM_SIZE  => 'File melebihi MAX_FILE_SIZE form',
        UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian',
        UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary server tidak ditemukan',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
    ];
    $code = $_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE;
    jsonOut(false, $errMsg[$code] ?? 'Gagal upload file (error ' . $code . ')');
}

$file    = $_FILES['foto'];
$maxSize = 3 * 1024 * 1024; // 3 MB

if ($file['size'] > $maxSize) {
    jsonOut(false, 'Ukuran file maksimal 3MB');
}

/* ── Deteksi MIME type (dua cara, fallback ke ekstensi) ── */
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
$extMap  = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$extOut  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

// Coba finfo dulu
$mimeType = false;
if (function_exists('finfo_open')) {
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
}

// Fallback ke ekstensi jika finfo tidak tersedia
if (!$mimeType || $mimeType === 'application/octet-stream') {
    $origExt  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mimeType = $extMap[$origExt] ?? 'unknown';
}

if (!in_array($mimeType, $allowed)) {
    jsonOut(false, 'Format file harus JPG, PNG, atau WEBP (terdeteksi: ' . $mimeType . ')');
}

/* ── Siapkan folder upload ── */
// Simpan di /uploads/ (huruf kecil, lebih umum di server Linux)
$uploadDir = __DIR__ . '/../uploads/kendaraan/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        jsonOut(false, 'Gagal membuat folder upload. Periksa permission folder.');
    }
}

/* ── Pindahkan file ── */
$ext      = $extOut[$mimeType];
$fileName = 'kendaraan_' . $id . '_' . time() . '.' . $ext;
$filePath = $uploadDir . $fileName;
$fileUrl  = BASE_URL . '/uploads/kendaraan/' . $fileName;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    jsonOut(false, 'Gagal menyimpan file. Periksa permission folder uploads/');
}

/* ── Update database ── */
try {
    $pdo = getKoneksi();

    // Hapus foto lama dari disk jika ada
    $old = $pdo->prepare("SELECT foto FROM kendaraan WHERE id = ?");
    $old->execute([$id]);
    $row = $old->fetch();
    if (!empty($row['foto'])) {
        // Konversi URL ke path fisik
        $oldRelative = ltrim(str_replace(BASE_URL, '', $row['foto']), '/');
        $oldPath     = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldRelative);
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    // Cek apakah kolom foto sudah ada (graceful jika belum migration)
    $columns = $pdo->query("SHOW COLUMNS FROM kendaraan LIKE 'foto'")->fetchAll();
    if (empty($columns)) {
        // Kolom belum ada — tambahkan otomatis
        $pdo->exec("ALTER TABLE kendaraan ADD COLUMN foto VARCHAR(255) NOT NULL DEFAULT '' AFTER lokasi");
    }

    $stmt = $pdo->prepare("UPDATE kendaraan SET foto = ? WHERE id = ?");
    $stmt->execute([$fileUrl, $id]);

    jsonOut(true, 'Foto berhasil diupload', ['url' => $fileUrl]);

} catch (Exception $e) {
    // File sudah terlanjur dipindah — hapus agar tidak orphan
    if (is_file($filePath)) @unlink($filePath);
    jsonOut(false, 'DB error: ' . $e->getMessage());
}
