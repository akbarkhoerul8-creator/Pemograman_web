-- ============================================================
--  FleetHub Database Schema
--  File   : fleethub.sql
--  Engine : MySQL 5.7+ / MariaDB 10.3+
--  Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS fleethub_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fleethub_db;

-- ── 1. Tabel Utama: kendaraan ────────────────────────────────
CREATE TABLE IF NOT EXISTS kendaraan (
    id           INT          NOT NULL AUTO_INCREMENT,
    nomor_plat   VARCHAR(20)  NOT NULL,
    merek        VARCHAR(50)  NOT NULL,
    model        VARCHAR(50)  NOT NULL,
    tahun        SMALLINT     NOT NULL,
    warna        VARCHAR(30)  NOT NULL DEFAULT '',
    jenis        VARCHAR(30)  NOT NULL DEFAULT 'Mobil',
    status       ENUM('Tersedia','Dipakai','Servis') NOT NULL DEFAULT 'Tersedia',
    kilometer    INT          NOT NULL DEFAULT 0,
    bahan_bakar  ENUM('Bensin','Solar','Listrik')    NOT NULL DEFAULT 'Bensin',
    lokasi       VARCHAR(100) NOT NULL DEFAULT '',
    foto         VARCHAR(255) NOT NULL DEFAULT '',
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nomor_plat (nomor_plat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Dokumen Kendaraan (STNK, Pajak, Asuransi, dll) ───────
CREATE TABLE IF NOT EXISTS dokumen_kendaraan (
    id               INT         NOT NULL AUTO_INCREMENT,
    kendaraan_id     INT         NOT NULL,
    jenis_dokumen    VARCHAR(50) NOT NULL DEFAULT '',   -- contoh: STNK, Pajak Tahunan, Asuransi
    nomor_dokumen    VARCHAR(60) NOT NULL DEFAULT '',
    tanggal_terbit   DATE        NULL,
    tanggal_expire   DATE        NULL,                  -- NULL = tidak ada batas berlaku
    keterangan       VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    CONSTRAINT fk_dokumen_kendaraan
        FOREIGN KEY (kendaraan_id) REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Riwayat Servis ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS riwayat_servis (
    id              INT          NOT NULL AUTO_INCREMENT,
    kendaraan_id    INT          NOT NULL,
    tanggal_servis  DATE         NOT NULL,
    jenis_servis    VARCHAR(100) NOT NULL DEFAULT '',   -- contoh: Ganti Oli, Tune Up
    bengkel         VARCHAR(100) NOT NULL DEFAULT '',   -- nama bengkel
    kilometer       INT          NOT NULL DEFAULT 0,    -- km saat servis
    biaya           INT          NOT NULL DEFAULT 0,    -- dalam Rupiah
    catatan         TEXT         NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_servis_kendaraan
        FOREIGN KEY (kendaraan_id) REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Jadwal Servis ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS jadwal_servis (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. Pengemudi Kendaraan ───────────────────────────────────
CREATE TABLE IF NOT EXISTS pengemudi_kendaraan (
    id               INT          NOT NULL AUTO_INCREMENT,
    kendaraan_id     INT          NOT NULL,
    nama_pengemudi   VARCHAR(100) NOT NULL,
    jabatan          VARCHAR(100) NOT NULL DEFAULT '',
    tanggal_mulai    DATE         NOT NULL,
    tanggal_selesai  DATE         NULL,                 -- NULL = masih aktif dipakai
    keterangan       VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    CONSTRAINT fk_pengemudi_kendaraan
        FOREIGN KEY (kendaraan_id) REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  DATA CONTOH
-- ============================================================

-- Kendaraan
INSERT INTO kendaraan (nomor_plat, merek, model, tahun, warna, jenis, status, kilometer, bahan_bakar, lokasi) VALUES
('B 1234 ABC', 'Toyota',     'Fortuner',      2022, 'Putih',  'Mobil', 'Tersedia', 45230,  'Solar',  'Jakarta Selatan'),
('B 5678 DEF', 'Honda',      'Vario 160',     2023, 'Hitam',  'Motor', 'Dipakai',  12300,  'Bensin', 'Depok'),
('B 9101 GHI', 'Mitsubishi', 'Pajero Sport',  2021, 'Silver', 'Mobil', 'Servis',   87650,  'Solar',  'Bekasi'),
('B 4455 KLM', 'Suzuki',     'Ertiga Hybrid', 2024, 'Merah',  'Mobil', 'Tersedia', 8920,   'Bensin', 'Bogor'),
('B 7788 NOP', 'Yamaha',     'NMAX 155',      2023, 'Biru',   'Motor', 'Dipakai',  18650,  'Bensin', 'Tangerang'),
('B 3344 QRS', 'Isuzu',      'Elf Minibus',   2020, 'Kuning', 'Mobil', 'Servis',   156200, 'Solar',  'Jakarta Timur');

-- Riwayat Servis (untuk kendaraan id 1 = B 1234 ABC)
INSERT INTO riwayat_servis (kendaraan_id, tanggal_servis, jenis_servis, bengkel, kilometer, biaya, catatan) VALUES
(1, '2026-04-18', 'Servis Rutin 45.000 km',    'Auto2000 Gatot Subroto',  45000, 850000,  'Ganti oli mesin, filter udara, cek rem'),
(1, '2026-02-12', 'Ganti Ban Depan',            'Bridgestone Shop Jakarta',44000, 1200000, '2 ban depan diganti Bridgestone Turanza'),
(1, '2025-10-05', 'Servis Rutin 40.000 km',    'Auto2000 Gatot Subroto',  40000, 800000,  'Ganti oli & filter, tune up ringan'),
(3, '2026-05-01', 'Perbaikan Suspensi',         'Bengkel Spesialis Pajero',87000, 2500000, 'Ganti per dan shock absorber belakang'),
(3, '2026-03-15', 'Ganti Aki',                  'Toko Aki Sumber Jaya',   86000, 650000,  'Aki GS Astra 65Ah');

-- Dokumen Kendaraan (untuk kendaraan id 1 = B 1234 ABC)
INSERT INTO dokumen_kendaraan (kendaraan_id, jenis_dokumen, nomor_dokumen, tanggal_terbit, tanggal_expire, keterangan) VALUES
(1, 'STNK',          '1234567890',  '2025-03-15', '2026-03-15', 'Perpanjang setiap tahun'),
(1, 'Pajak Tahunan', 'PAJ-2025-001','2025-03-15', '2027-05-12', ''),
(1, 'Asuransi',      'ASR-TLO-001', '2025-01-20', '2027-01-20', 'All Risk — Asuransi Jasindo'),
(2, 'STNK',          '9876543210',  '2024-06-01', '2025-06-01', ''),
(3, 'STNK',          '5551234567',  '2024-09-10', '2025-09-10', '');

-- Jadwal Servis contoh
INSERT INTO jadwal_servis (kendaraan_id, tanggal_jadwal, jenis_servis, bengkel, estimasi_biaya, status, catatan) VALUES
(2, '2026-07-10', 'Ganti Oli & Filter',     'Bengkel Resmi Honda Depok',  350000,  'Terjadwal',  'Oli Yamalube 10W-40'),
(4, '2026-07-15', 'Servis Rutin 10.000 km', 'Auto2000 Bogor',             800000,  'Terjadwal',  NULL),
(1, '2026-06-01', 'Tune Up Mesin',          'Auto2000 Gatot Subroto',     1200000, 'Selesai',    'Busi & throttle body dibersihkan'),
(3, '2026-05-20', 'Perbaikan AC',           'Bengkel AC Khusus',          2000000, 'Dibatalkan', 'Ditunda ke bulan depan');


INSERT INTO pengemudi_kendaraan (kendaraan_id, nama_pengemudi, jabatan, tanggal_mulai, tanggal_selesai) VALUES
(2, 'Rizki Aditya',    'Marketing Manager',  '2026-04-20', NULL),
(5, 'Siti Rahmawati',  'Sales Executive',    '2026-05-01', NULL),
(1, 'Budi Santoso',    'Driver Operasional', '2025-12-01', '2026-01-31');

-- ============================================================
--  SCRIPT ALTER — jalankan jika tabel SUDAH ADA dari versi lama
--  (skip jika import fresh / database baru)
-- ============================================================
-- ALTER TABLE kendaraan
--   ADD COLUMN IF NOT EXISTS lokasi    VARCHAR(100) NOT NULL DEFAULT '' AFTER bahan_bakar,
--   ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
--   MODIFY COLUMN status      ENUM('Tersedia','Dipakai','Servis') NOT NULL DEFAULT 'Tersedia',
--   MODIFY COLUMN bahan_bakar ENUM('Bensin','Solar','Listrik')    NOT NULL DEFAULT 'Bensin';
--
-- ALTER TABLE pengemudi_kendaraan
--   ADD COLUMN IF NOT EXISTS jabatan    VARCHAR(100) NOT NULL DEFAULT '' AFTER nama_pengemudi,
--   ADD COLUMN IF NOT EXISTS keterangan VARCHAR(255) NOT NULL DEFAULT '' AFTER tanggal_selesai;
--
-- ALTER TABLE riwayat_servis
--   MODIFY COLUMN biaya INT NOT NULL DEFAULT 0;
--
-- UPDATE kendaraan SET status='Tersedia' WHERE status NOT IN ('Tersedia','Dipakai','Servis');
