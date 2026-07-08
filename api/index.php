<?php
// Load config: session, BASE_URL, dan koneksi database
require_once __DIR__ . '/config/database.php';

// Router Dinamis
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Validasi login: Jika belum login dan ingin buka halaman terproteksi, lempar ke login
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$is_logged_in && $page !== 'login') {
    header("Location: index.php?page=login");
    exit();
}

// Jika sudah login tapi ingin buka login page, arahkan ke home
if ($is_logged_in && $page === 'login') {
    header("Location: index.php?page=home");
    exit();
}

// Validasi halaman yang diizinkan untuk mencegah Path Traversal
$allowed_pages = ['home', 'detail', 'login', 'logout', 'maintenance'];

if (in_array($page, $allowed_pages)) {
    $file_path = __DIR__ . '/pages/' . $page . '.php';
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        echo "404 - File halaman tidak ditemukan.";
    }
} else {
    // Jika halaman tidak dikenali, arahkan ke 404 atau home
    echo "404 - Halaman tidak ditemukan.";
}
?>
