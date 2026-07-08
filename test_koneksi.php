<?php

/**
 * test_koneksi.php
 * -------------------------------------------------
 * Buka file ini di browser untuk memastikan koneksi
 * ke database fleethub_db berhasil, sebelum lanjut ke
 * tahap berikutnya (CRUD).
 *
 * Akses via: http://localhost/fleethub/test_koneksi.php
 * -------------------------------------------------
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getKoneksi();

    // Cek koneksi dengan menghitung jumlah kendaraan
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM kendaraan");
    $row  = $stmt->fetch();

    echo "<h2>✅ Koneksi database BERHASIL</h2>";
    echo "<p>Terhubung ke database: <strong>" . DB_NAME . "</strong></p>";
    echo "<p>Jumlah data kendaraan saat ini: <strong>{$row['total']}</strong></p>";

    // Tampilkan contoh data agar lebih yakin datanya benar terbaca
    $stmt = $pdo->query("SELECT plat_nomor, merk, model, status FROM kendaraan");
    $data = $stmt->fetchAll();

    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>Plat Nomor</th><th>Merk</th><th>Model</th><th>Status</th></tr>";
    foreach ($data as $k) {
        echo "<tr>";
        echo "<td>{$k['plat_nomor']}</td>";
        echo "<td>{$k['merk']}</td>";
        echo "<td>{$k['model']}</td>";
        echo "<td>{$k['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<h2>❌ Terjadi kesalahan</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
