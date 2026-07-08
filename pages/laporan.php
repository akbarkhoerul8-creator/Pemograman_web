<?php
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ' . BASE_URL . '/index.php?page=login');
    exit;
}

$pdo = getKoneksi();

/* ════════════════════════════════════════════
   Helper: query aman — return 0 jika tabel
   belum ada atau query gagal
═══════════════════════════════════════════ */
function safeVal(PDO $pdo, string $sql, array $params = []): int|string {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() ?? 0;
    } catch (Exception) { return 0; }
}
function safeRows(PDO $pdo, string $sql, array $params = []): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    } catch (Exception) { return []; }
}

/* ════════════════════════════════════════════
   1. ARMADA
═══════════════════════════════════════════ */
$totalKendaraan = safeVal($pdo, "SELECT COUNT(*) FROM kendaraan");
$totalTersedia  = safeVal($pdo, "SELECT COUNT(*) FROM kendaraan WHERE status='Tersedia'");
$totalDipakai   = safeVal($pdo, "SELECT COUNT(*) FROM kendaraan WHERE status='Dipakai'");
$totalServis    = safeVal($pdo, "SELECT COUNT(*) FROM kendaraan WHERE status='Servis'");

/* ════════════════════════════════════════════
   2. SERVIS
═══════════════════════════════════════════ */
$totalServisRec   = safeVal($pdo, "SELECT COUNT(*) FROM riwayat_servis");
$servisBulanIni   = safeVal($pdo, "SELECT COUNT(*) FROM riwayat_servis WHERE DATE_FORMAT(tanggal_servis,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m')");
$totalBiayaServis = safeVal($pdo, "SELECT COALESCE(SUM(biaya),0) FROM riwayat_servis");
$rataServis       = $totalServisRec > 0 ? (int)($totalBiayaServis / $totalServisRec) : 0;

/* ════════════════════════════════════════════
   3. JADWAL SERVIS
═══════════════════════════════════════════ */
$jadwalTerjadwal  = safeVal($pdo, "SELECT COUNT(*) FROM jadwal_servis WHERE status='Terjadwal'");
$jadwalSelesai    = safeVal($pdo, "SELECT COUNT(*) FROM jadwal_servis WHERE status='Selesai'");
$jadwalDibatalkan = safeVal($pdo, "SELECT COUNT(*) FROM jadwal_servis WHERE status='Dibatalkan'");
$jadwalTotal      = (int)$jadwalTerjadwal + (int)$jadwalSelesai + (int)$jadwalDibatalkan;

/* ════════════════════════════════════════════
   4. PENGGUNAAN
═══════════════════════════════════════════ */
$totalPenggunaan = safeVal($pdo, "SELECT COUNT(*) FROM riwayat_penggunaan");
$sedangBerjalan  = safeVal($pdo, "SELECT COUNT(*) FROM riwayat_penggunaan WHERE tanggal_selesai IS NULL");
$totalJarak      = safeVal($pdo, "SELECT COALESCE(SUM(km_akhir - km_awal),0) FROM riwayat_penggunaan WHERE km_akhir IS NOT NULL AND km_awal IS NOT NULL");

/* ════════════════════════════════════════════
   5. TABEL PER KENDARAAN
═══════════════════════════════════════════ */
$perKendaraan = safeRows($pdo, "
    SELECT
        k.id,
        k.nomor_plat,
        k.merek,
        k.model,
        k.status,
        k.kilometer,
        COALESCE(s.jml_servis, 0)    AS jml_servis,
        COALESCE(s.total_biaya, 0)   AS total_biaya,
        COALESCE(p.jml_pakai, 0)     AS jml_pakai,
        COALESCE(p.total_jarak, 0)   AS total_jarak
    FROM kendaraan k
    LEFT JOIN (
        SELECT kendaraan_id,
               COUNT(*)    AS jml_servis,
               SUM(biaya)  AS total_biaya
        FROM riwayat_servis
        GROUP BY kendaraan_id
    ) s ON s.kendaraan_id = k.id
    LEFT JOIN (
        SELECT kendaraan_id,
               COUNT(*) AS jml_pakai,
               COALESCE(SUM(km_akhir - km_awal), 0) AS total_jarak
        FROM riwayat_penggunaan
        WHERE km_akhir IS NOT NULL AND km_awal IS NOT NULL
        GROUP BY kendaraan_id
    ) p ON p.kendaraan_id = k.id
    ORDER BY k.nomor_plat ASC
");

function statusCls(string $s): string {
    return match($s) { 'Tersedia'=>'low', 'Dipakai'=>'accent', 'Servis'=>'med', default=>'low' };
}
$tanggalCetak = date('d F Y, H:i');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="topbar no-print">
  <div class="topbar-title"><span>FleetHub</span> / Laporan</div>
  <div class="topbar-actions">
    <button class="btn btn-ghost" onclick="window.print()">🖨️ Print / Simpan PDF</button>
  </div>
</div>

<div class="content">

<!-- ── Print Header (hanya muncul saat print) ── -->
  <div class="print-header">
    <div class="print-header-top">
      <h2 class="print-title">LAPORAN PENGELOLAAN ARMADA KENDARAAN</h2>
      <div class="print-meta">
        <div><span>Perusahaan</span><span>: FleetHub</span></div>
        <div><span>Tanggal Cetak</span><span>: <?= $tanggalCetak ?></span></div>
        <div><span>Total Unit</span><span>: <?= (int)$totalKendaraan ?> kendaraan</span></div>
      </div>
    </div>
  </div>

  <!-- ── Page Header ── -->
  <div class="page-header no-print">
    <div>
      <h1>Laporan</h1>
      <p>Rekap lengkap seluruh data armada, servis, jadwal, dan penggunaan kendaraan.</p>
    </div>
  </div>

  <!-- ═══════════════════════════════════
       SECTION 1 — RINGKASAN ARMADA
  ════════════════════════════════════ -->
  <div class="laporan-section">
    <div class="laporan-section-head">
      <span>🚗</span>
      <h2>Ringkasan Armada</h2>
      <span class="laporan-date">per <?= date('d F Y') ?></span>
    </div>
    <div class="stats-bar">
      <div class="stat-card">
        <div class="stat-label">Total Kendaraan</div>
        <div class="stat-value"><?= (int)$totalKendaraan ?></div>
        <div class="stat-sub">unit terdaftar</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Tersedia</div>
        <div class="stat-value" style="color:var(--low)"><?= (int)$totalTersedia ?></div>
        <div class="stat-sub">siap digunakan</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Sedang Dipakai</div>
        <div class="stat-value" style="color:var(--accent)"><?= (int)$totalDipakai ?></div>
        <div class="stat-sub">unit aktif</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Dalam Servis</div>
        <div class="stat-value" style="color:var(--med)"><?= (int)$totalServis ?></div>
        <div class="stat-sub">sedang diperbaiki</div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════
       SECTION 2 — RINGKASAN SERVIS
  ════════════════════════════════════ -->
  <div class="laporan-section">
    <div class="laporan-section-head">
      <span>🔧</span>
      <h2>Ringkasan Servis</h2>
    </div>
    <div class="stats-bar" style="grid-template-columns:repeat(4,1fr)">
      <div class="stat-card">
        <div class="stat-label">Total Riwayat</div>
        <div class="stat-value"><?= (int)$totalServisRec ?></div>
        <div class="stat-sub">semua waktu</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Bulan Ini</div>
        <div class="stat-value" style="color:var(--accent)"><?= (int)$servisBulanIni ?></div>
        <div class="stat-sub"><?= date('F Y') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Biaya</div>
        <div class="stat-value" style="font-size:18px;color:var(--med)">
          Rp <?= number_format((int)$totalBiayaServis, 0, ',', '.') ?>
        </div>
        <div class="stat-sub">pengeluaran</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Rata-rata / Servis</div>
        <div class="stat-value" style="font-size:18px;color:var(--low)">
          Rp <?= number_format($rataServis, 0, ',', '.') ?>
        </div>
        <div class="stat-sub">per kunjungan</div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════
       SECTION 3 — RINGKASAN JADWAL
  ════════════════════════════════════ -->
  <div class="laporan-section">
    <div class="laporan-section-head">
      <span>📅</span>
      <h2>Ringkasan Jadwal Servis</h2>
    </div>
    <div class="stats-bar" style="grid-template-columns:repeat(4,1fr)">
      <div class="stat-card">
        <div class="stat-label">Total Jadwal</div>
        <div class="stat-value"><?= $jadwalTotal ?></div>
        <div class="stat-sub">semua jadwal</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Terjadwal</div>
        <div class="stat-value" style="color:var(--accent)"><?= (int)$jadwalTerjadwal ?></div>
        <div class="stat-sub">menunggu servis</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Selesai</div>
        <div class="stat-value" style="color:var(--low)"><?= (int)$jadwalSelesai ?></div>
        <div class="stat-sub">sudah dikerjakan</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Dibatalkan</div>
        <div class="stat-value" style="color:var(--high)"><?= (int)$jadwalDibatalkan ?></div>
        <div class="stat-sub">jadwal batal</div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════
       SECTION 4 — RINGKASAN PENGGUNAAN
  ════════════════════════════════════ -->
  <div class="laporan-section">
    <div class="laporan-section-head">
      <span>🗺️</span>
      <h2>Ringkasan Penggunaan</h2>
    </div>
    <div class="stats-bar" style="grid-template-columns:repeat(3,1fr)">
      <div class="stat-card">
        <div class="stat-label">Total Catatan</div>
        <div class="stat-value"><?= (int)$totalPenggunaan ?></div>
        <div class="stat-sub">semua penggunaan</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Sedang Berjalan</div>
        <div class="stat-value" style="color:var(--accent)"><?= (int)$sedangBerjalan ?></div>
        <div class="stat-sub">belum kembali</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Jarak Tempuh</div>
        <div class="stat-value" style="color:var(--low);font-size:20px">
          <?= number_format((int)$totalJarak, 0, ',', '.') ?> km
        </div>
        <div class="stat-sub">kumulatif</div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════
       SECTION 5 — TABEL PER KENDARAAN
  ════════════════════════════════════ -->
  <div class="laporan-section">
    <div class="laporan-section-head">
      <span>📋</span>
      <h2>Detail Per Kendaraan</h2>
    </div>
    <div class="table-wrap">
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Plat</th>
              <th>Kendaraan</th>
              <th>Status</th>
              <th>Odometer</th>
              <th>Jml Servis</th>
              <th>Biaya Servis</th>
              <th>Jml Pemakaian</th>
              <th>Total Jarak</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($perKendaraan)): ?>
              <tr><td colspan="9" class="empty-row">Belum ada data kendaraan.</td></tr>
            <?php else: foreach ($perKendaraan as $i => $row): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($row['nomor_plat']) ?></strong></td>
                <td class="client-name"><?= htmlspecialchars($row['merek'] . ' ' . $row['model']) ?></td>
                <td>
                  <span class="priority priority-<?= statusCls($row['status']) ?>">
                    <?= htmlspecialchars($row['status']) ?>
                  </span>
                </td>
                <td class="deadline"><?= number_format((int)$row['kilometer'], 0, ',', '.') ?> km</td>
                <td style="text-align:center"><?= (int)$row['jml_servis'] ?> ×</td>
                <td class="deadline">
                  <?= $row['total_biaya'] > 0
                    ? 'Rp ' . number_format((int)$row['total_biaya'], 0, ',', '.')
                    : '—' ?>
                </td>
                <td style="text-align:center"><?= (int)$row['jml_pakai'] ?> ×</td>
                <td class="deadline">
                  <?= $row['total_jarak'] > 0
                    ? number_format((int)$row['total_jarak'], 0, ',', '.') . ' km'
                    : '—' ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
          <?php if (!empty($perKendaraan)): ?>
          <tfoot>
            <tr class="laporan-tfoot">
              <td colspan="5"><strong>Total</strong></td>
              <td style="text-align:center"><strong><?= array_sum(array_column($perKendaraan,'jml_servis')) ?> ×</strong></td>
              <td><strong>Rp <?= number_format(array_sum(array_column($perKendaraan,'total_biaya')), 0, ',', '.') ?></strong></td>
              <td style="text-align:center"><strong><?= array_sum(array_column($perKendaraan,'jml_pakai')) ?> ×</strong></td>
              <td><strong><?= number_format(array_sum(array_column($perKendaraan,'total_jarak')), 0, ',', '.') ?> km</strong></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════
       SECTION 6 — CATATAN & TANDA TANGAN
  ════════════════════════════════════ -->
  <div class="laporan-section laporan-ttd">
    <div class="laporan-section-head">
      <span>✍️</span>
      <h2>Catatan &amp; Tanda Tangan</h2>
    </div>

    <div class="ttd-catatan">
      <label>Catatan:</label>
      <div class="ttd-catatan-box"></div>
    </div>

    <div class="ttd-grid">
      <div class="ttd-col">
        <p class="ttd-label">Dibuat oleh,</p>
        <div class="ttd-space"></div>
        <div class="ttd-line"></div>
        <p class="ttd-name">( _________________________ )</p>
        <p class="ttd-role">Administrator Fleet</p>
      </div>
      <div class="ttd-col">
        <p class="ttd-label">Diketahui oleh,</p>
        <div class="ttd-space"></div>
        <div class="ttd-line"></div>
        <p class="ttd-name">( _________________________ )</p>
        <p class="ttd-role">Kepala Divisi / Manager</p>
      </div>
      <div class="ttd-col">
        <p class="ttd-label">Disetujui oleh,</p>
        <div class="ttd-space"></div>
        <div class="ttd-line"></div>
        <p class="ttd-name">( _________________________ )</p>
        <p class="ttd-role">Direktur / Pimpinan</p>
      </div>
    </div>

    <p class="ttd-kota"><?= date('d F Y') ?></p>
  </div>

  <!-- ═══════════════════════════════════
       CATATAN & TANDA TANGAN (khusus print)
  ════════════════════════════════════ -->
  <div class="print-only laporan-notes-box">
    <div class="notes-title">CATATAN</div>
    <div class="notes-line"></div>
    <div class="notes-line"></div>
    <div class="notes-line"></div>
  </div>

  <div class="print-only sig-row">
    <div class="sig-box">
      <div class="sig-label">Dibuat oleh</div>
      <div class="sig-space"></div>
      <div class="sig-line"></div>
      <div class="sig-name">Admin FleetHub</div>
    </div>
    <div class="sig-box">
      <div class="sig-label">Diperiksa oleh</div>
      <div class="sig-space"></div>
      <div class="sig-line"></div>
      <div class="sig-name">&nbsp;</div>
    </div>
    <div class="sig-box">
      <div class="sig-label">Disetujui oleh</div>
      <div class="sig-space"></div>
      <div class="sig-line"></div>
      <div class="sig-name">&nbsp;</div>
    </div>
  </div>

  <p class="laporan-footer-note no-print">
    Laporan dibuat otomatis berdasarkan data real-time database.
    Klik <strong>🖨️ Print / Simpan PDF</strong> di pojok kanan atas untuk mencetak.
  </p>

</div><!-- /content -->

<?php include __DIR__ . '/../includes/footer.php'; ?>

<style>
/* ── Laporan section (tampilan layar / dashboard) ── */
.laporan-section {
  margin-bottom: 36px;
}
.laporan-section-head {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 2px solid var(--border);
}
.laporan-section-head h2 {
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
  letter-spacing: -0.01em;
}
.laporan-section-head span:first-child {
  font-size: 18px;
}
.laporan-date {
  margin-left: auto;
  font-size: 12px;
  color: var(--text-dim);
  font-family: var(--font-mono);
}
.laporan-tfoot td {
  padding: 12px 20px;
  background: var(--surface-2);
  border-top: 2px solid var(--border);
  font-size: 13px;
}
.laporan-footer-note {
  text-align: center;
  font-size: 13px;
  color: var(--text-dim);
  margin-top: 24px;
  font-family: var(--font-mono);
}

/* Print header — hidden on screen, elemen print-only juga hidden di layar */
.print-header, .print-only {
  display: none;
}

/* ════════════════════════════════════════════
   PRINT STYLES — gaya "Car Maintenance Log"
   Bergaris tegas, kotak besar, hitam-putih
═══════════════════════════════════════════ */
@media print {
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .no-print,
  .sidebar,
  .topbar {
    display: none !important;
  }
  .main { margin-left: 0 !important; }
  .content { padding: 12px !important; }

  body {
    font-size: 12px !important;
    color: #000 !important;
    font-family: Arial, Helvetica, sans-serif !important;
  }

  /* ── Header dokumen ── */
  .print-header {
    display: block !important;
    margin-bottom: 18px;
  }
  .print-header-top {
    border-bottom: 3px solid #000;
    padding-bottom: 10px;
    margin-bottom: 4px;
  }
  .print-title {
    font-size: 24px;
    font-weight: 900;
    letter-spacing: 0.5px;
    color: #000;
    margin-bottom: 8px;
  }
  .print-meta {
    display: flex;
    justify-content: flex-start;
    gap: 32px;
    font-size: 11px;
    font-weight: 600;
    color: #000;
  }
  .print-meta > div {
    display: flex;
    gap: 4px;
  }
  .print-meta span:first-child {
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  /* ── Section jadi kotak besar bergaris tebal ── */
  .laporan-section {
    border: 2.5px solid #000 !important;
    border-radius: 0 !important;
    padding: 10px 16px 16px !important;
    margin-bottom: 16px !important;
    page-break-inside: avoid;
  }
  .laporan-section-head {
    border-bottom: 2px solid #000 !important;
    padding-bottom: 6px !important;
    margin-bottom: 10px !important;
  }
  .laporan-section-head span:first-child {
    display: none !important;
  }
  .laporan-section-head h2 {
    font-size: 18px !important;
    font-weight: 900 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.3px !important;
    color: #000 !important;
  }
  .laporan-date {
    color: #000 !important;
    font-weight: 600 !important;
  }

  /* ── Stat card jadi kotak bergaris tegas, semua warna → hitam ── */
  .stats-bar {
    gap: 0 !important;
    border: 1.5px solid #000 !important;
  }
  .stat-card {
    border: 1.5px solid #000 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    background: #fff !important;
    padding: 8px 12px !important;
  }
  .stat-label {
    color: #000 !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    font-size: 9px !important;
    letter-spacing: 0.4px !important;
  }
  .stat-value {
    color: #000 !important;
    font-weight: 900 !important;
  }
  .stat-sub {
    color: #333 !important;
    font-size: 9.5px !important;
  }

  /* ── Tabel detail per kendaraan ── */
  .table-wrap {
    box-shadow: none !important;
    border: none !important;
  }
  table {
    border-collapse: collapse !important;
    width: 100% !important;
  }
  table th,
  table td {
    border: 1.3px solid #000 !important;
    color: #000 !important;
    padding: 6px 10px !important;
  }
  table thead th {
    background: #e8e8e8 !important;
    text-transform: uppercase;
    font-size: 10px;
    font-weight: 800;
  }
  .priority {
    border: 1.2px solid #000 !important;
    background: #fff !important;
    color: #000 !important;
    border-radius: 0 !important;
    font-weight: 700 !important;
  }
  .laporan-tfoot td {
    background: #f0f0f0 !important;
    border-top: 2px solid #000 !important;
  }

  /* ── Catatan box ── */
  .print-only { display: block !important; }
  .laporan-notes-box {
    border: 2.5px solid #000;
    padding: 10px 16px 16px;
    margin-bottom: 16px;
    page-break-inside: avoid;
  }
  .notes-title {
    font-size: 16px;
    font-weight: 900;
    text-transform: uppercase;
    border-bottom: 2px solid #000;
    padding-bottom: 6px;
    margin-bottom: 14px;
  }
  .notes-line {
    border-bottom: 1px solid #000;
    height: 22px;
  }

  /* ── Tanda tangan ── */
  .sig-row {
    display: flex !important;
    justify-content: space-between;
    gap: 24px;
    margin-top: 24px;
    page-break-inside: avoid;
  }
  .sig-box {
    flex: 1;
    text-align: center;
  }
  .sig-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 4px;
  }
  .sig-space { height: 50px; }
  .sig-line {
    border-bottom: 1.5px solid #000;
    margin: 0 8px;
  }
  .sig-name {
    font-size: 11px;
    font-weight: 600;
    margin-top: 6px;
  }
}
</style>
