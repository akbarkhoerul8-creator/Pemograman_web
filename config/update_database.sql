-- ══════════════════════════════════════════════════
-- 1. Tambah kolom nomor_rangka & nomor_mesin
-- ══════════════════════════════════════════════════
ALTER TABLE kendaraan 
  ADD COLUMN nomor_rangka VARCHAR(50) DEFAULT NULL AFTER warna,
  ADD COLUMN nomor_mesin  VARCHAR(50) DEFAULT NULL AFTER nomor_rangka;

-- ══════════════════════════════════════════════════
-- 2. Buat tabel riwayat_penggunaan
-- ══════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS riwayat_penggunaan (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  kendaraan_id    INT NOT NULL,
  nama_pengemudi  VARCHAR(100) NOT NULL,
  tujuan          VARCHAR(200) DEFAULT NULL,
  tanggal_mulai   DATE NOT NULL,
  tanggal_selesai DATE DEFAULT NULL,
  km_awal         INT DEFAULT 0,
  km_akhir        INT DEFAULT 0,
  keterangan      TEXT DEFAULT NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kendaraan_id) REFERENCES kendaraan(id) ON DELETE CASCADE
);

-- ══════════════════════════════════════════════════
-- 3. Sample data riwayat_penggunaan (opsional)
-- ══════════════════════════════════════════════════
INSERT INTO riwayat_penggunaan (kendaraan_id, nama_pengemudi, tujuan, tanggal_mulai, tanggal_selesai, km_awal, km_akhir, keterangan) VALUES
(1, 'Rizki Aditya',   'Kunjungan Klien Bandung',  '2026-04-20', '2026-04-21', 38500, 39200, 'Perjalanan dinas'),
(1, 'Siti Rahayu',    'Meeting Jakarta Pusat',     '2026-03-15', '2026-03-15', 37800, 38100, NULL),
(1, 'Budi Santoso',   'Antar Dokumen Depok',       '2026-02-10', '2026-02-10', 37200, 37500, 'Pengiriman kontrak');