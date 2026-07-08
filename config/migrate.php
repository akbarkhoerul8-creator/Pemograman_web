<?php
/**
 * config/migrate.php
 * ─────────────────────────────────────────────────────────────
 * Jalankan SEKALI jika database sudah ada dari versi lama.
 * Akses: http://localhost/pemograman_web/config/migrate.php
 * Hapus atau batasi akses file ini setelah dijalankan.
 * ─────────────────────────────────────────────────────────────
 */
require_once __DIR__ . '/database.php';

$pdo = getKoneksi();
$log = [];

function run(PDO $pdo, string $sql, string $label): void {
    global $log;
    try {
        $pdo->exec($sql);
        $log[] = "✅ $label";
    } catch (PDOException $e) {
        $log[] = "⚠️  $label — " . $e->getMessage();
    }
}

// ── Tabel kendaraan ──────────────────────────────────────────
run($pdo, "ALTER TABLE kendaraan
    ADD COLUMN foto       VARCHAR(255) NOT NULL DEFAULT '' AFTER lokasi",
    "kendaraan: tambah kolom 'foto'");

run($pdo, "ALTER TABLE kendaraan
    ADD COLUMN lokasi      VARCHAR(100) NOT NULL DEFAULT '' AFTER bahan_bakar",
    "kendaraan: tambah kolom 'lokasi'");

run($pdo, "ALTER TABLE kendaraan
    ADD COLUMN updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
    "kendaraan: tambah kolom 'updated_at'");

run($pdo, "ALTER TABLE kendaraan
    MODIFY COLUMN status      ENUM('Tersedia','Dipakai','Servis') NOT NULL DEFAULT 'Tersedia'",
    "kendaraan: update ENUM status");

run($pdo, "ALTER TABLE kendaraan
    MODIFY COLUMN bahan_bakar ENUM('Bensin','Solar','Listrik') NOT NULL DEFAULT 'Bensin'",
    "kendaraan: update ENUM bahan_bakar");

// Migrate nilai status lama → baru
run($pdo, "UPDATE kendaraan SET status='Tersedia' WHERE status='aktif'",      "Migrate: aktif → Tersedia");
run($pdo, "UPDATE kendaraan SET status='Servis'   WHERE status='perawatan'",  "Migrate: perawatan → Servis");
run($pdo, "UPDATE kendaraan SET status='Tersedia' WHERE status='tidak_aktif'","Migrate: tidak_aktif → Tersedia");

// ── Tabel jadwal_servis ──────────────────────────────────────
run($pdo, "CREATE TABLE IF NOT EXISTS jadwal_servis (
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
    CONSTRAINT fk_jadwal_kendaraan
        FOREIGN KEY (kendaraan_id) REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "Buat tabel 'jadwal_servis' (jika belum ada)");

// ── Tabel pengemudi_kendaraan ────────────────────────────────
run($pdo, "CREATE TABLE IF NOT EXISTS pengemudi_kendaraan (
    id               INT          NOT NULL AUTO_INCREMENT,
    kendaraan_id     INT          NOT NULL,
    nama_pengemudi   VARCHAR(100) NOT NULL,
    jabatan          VARCHAR(100) NOT NULL DEFAULT '',
    tanggal_mulai    DATE         NOT NULL,
    tanggal_selesai  DATE         NULL,
    keterangan       VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    CONSTRAINT fk_pengemudi_kendaraan
        FOREIGN KEY (kendaraan_id) REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "Buat tabel 'pengemudi_kendaraan' (jika belum ada)");

run($pdo, "ALTER TABLE pengemudi_kendaraan
    ADD COLUMN jabatan    VARCHAR(100) NOT NULL DEFAULT '' AFTER nama_pengemudi",
    "pengemudi_kendaraan: tambah kolom 'jabatan'");

run($pdo, "ALTER TABLE pengemudi_kendaraan
    ADD COLUMN keterangan VARCHAR(255) NOT NULL DEFAULT '' AFTER tanggal_selesai",
    "pengemudi_kendaraan: tambah kolom 'keterangan'");

// ── Tabel dokumen_kendaraan ──────────────────────────────────
run($pdo, "ALTER TABLE dokumen_kendaraan
    ADD COLUMN keterangan VARCHAR(255) NOT NULL DEFAULT '' AFTER tanggal_expire",
    "dokumen_kendaraan: tambah kolom 'keterangan'");

// ── Tabel riwayat_servis ─────────────────────────────────────
run($pdo, "ALTER TABLE riwayat_servis
    MODIFY COLUMN biaya INT NOT NULL DEFAULT 0",
    "riwayat_servis: set biaya NOT NULL DEFAULT 0");

run($pdo, "ALTER TABLE riwayat_servis
    ADD COLUMN bengkel    VARCHAR(100) NOT NULL DEFAULT '' AFTER jenis_servis",
    "riwayat_servis: tambah kolom 'bengkel'");

run($pdo, "ALTER TABLE riwayat_servis
    ADD COLUMN kilometer  INT NOT NULL DEFAULT 0 AFTER bengkel",
    "riwayat_servis: tambah kolom 'kilometer'");

echo "<!DOCTYPE html><html><head><meta charset='utf-8'>
<style>body{font-family:monospace;padding:32px;background:#f8f9fc;color:#1a1d2e}
pre{background:#fff;border:1px solid #e1e4ed;border-radius:8px;padding:20px;line-height:2}
a{color:#2563eb}</style></head><body>";
echo "<h2>🔧 Hasil Migrasi FleetHub</h2><pre>";
foreach ($log as $l) echo $l . "\n";
echo "</pre>";
echo "<p><strong>Selesai.</strong> Anda bisa menghapus atau menonaktifkan file ini.</p>";
echo "<p><a href='../index.php'>← Kembali ke Aplikasi</a></p>";
echo "</body></html>";
