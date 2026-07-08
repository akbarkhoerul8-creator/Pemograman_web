<?php
/**
 * Hapus data duplikat di tabel riwayat_penggunaan.
 * Jalankan SEKALI: http://localhost/pemograman_web/config/cleanup_duplikat.php
 * Hapus file ini setelah selesai.
 */
require_once __DIR__ . '/database.php';
$pdo = getKoneksi();

// Hapus duplikat — simpan hanya baris dengan id terkecil per kombinasi unik
$sql = "DELETE rp1 FROM riwayat_penggunaan rp1
        INNER JOIN riwayat_penggunaan rp2
        WHERE rp1.id > rp2.id
          AND rp1.kendaraan_id     = rp2.kendaraan_id
          AND rp1.nama_pengemudi   = rp2.nama_pengemudi
          AND rp1.tanggal_mulai    = rp2.tanggal_mulai
          AND rp1.km_awal          = rp2.km_awal";

$affected = $pdo->exec($sql);

echo "<pre style='font-family:monospace;padding:20px'>";
echo "✅ Duplikat dihapus: {$affected} baris\n\n";

$total = $pdo->query("SELECT COUNT(*) FROM riwayat_penggunaan")->fetchColumn();
echo "Data tersisa: {$total} baris\n\n";
echo "Selesai. <a href='../index.php?page=riwayat_penggunaan'>← Buka Riwayat Penggunaan</a>\n";
echo "⚠️ Hapus file ini setelah selesai!\n";
echo "</pre>";
