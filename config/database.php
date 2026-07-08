<?php

/**
 * config/database.php
 * -------------------------------------------------
 * File koneksi database FleetHub menggunakan PDO.
 * Sesuaikan DB_HOST, DB_NAME, DB_USER, DB_PASS dengan
 * konfigurasi MySQL di komputer/server kamu.
 * -------------------------------------------------
 */

// ===== SESSION =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== BASE URL =====
// Pakai DOCUMENT_ROOT untuk mapping path fisik ke URL
$_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_docRoot  = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
$_projRoot = rtrim(str_replace('\\', '/', realpath(dirname(__DIR__))), '/');
$_basePath = str_replace($_docRoot, '', $_projRoot);
if (!defined('BASE_URL')) {
    define('BASE_URL', $_protocol . '://' . $_host . $_basePath);
}

// ===== KONFIGURASI DATABASE =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'fleethub_db');
define('DB_USER', 'root');     // ganti sesuai user MySQL kamu
define('DB_PASS', '');         // ganti sesuai password MySQL kamu (XAMPP/Laragon default: kosong)
define('DB_CHARSET', 'utf8mb4');

/**
 * Mengembalikan koneksi PDO ke database.
 * Dipanggil di setiap file yang butuh akses database.
 *
 * @return PDO
 */
function getKoneksi(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lempar exception kalau ada error SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // hasil query berupa array asosiatif
            PDO::ATTR_EMULATE_PREPARES   => false,                   // gunakan prepared statement asli (lebih aman)
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Jangan tampilkan detail error database ke publik di production.
            // Untuk development, ini membantu debugging.
            die('Koneksi database gagal: ' . $e->getMessage());
        }
    }

    return $pdo;
}
