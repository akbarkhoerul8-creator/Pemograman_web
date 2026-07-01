<?php
// Load config: session, BASE_URL, dan koneksi database
require_once __DIR__ . '/config/database.php';

// Router dinamis
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Validasi login
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$is_logged_in && $page !== 'login') {
    header("Location: index.php?page=login");
    exit();
}

if ($is_logged_in && $page === 'login') {
    header("Location: index.php?page=home");
    exit();
}

// Halaman yang diizinkan (Path Traversal prevention)
$allowed_pages = ['home', 'detail', 'login', 'logout', 'maintenance', 'jadwal_servis', 'riwayat_penggunaan', 'laporan'];

if (in_array($page, $allowed_pages)) {
    $file_path = __DIR__ . '/pages/' . $page . '.php';
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        http_response_code(404);
        echo "404 - Halaman tidak ditemukan.";
    }
} else {
    http_response_code(404);
    echo "404 - Halaman tidak dikenali.";
}
