<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getKoneksi();

    // Ambil semua riwayat servis, urutkan by tanggal terbaru
    $stmt = $pdo->prepare("
        SELECT rs.*, k.nomor_plat, k.merek, k.model, k.status
        FROM riwayat_servis rs
        JOIN kendaraan k ON rs.kendaraan_id = k.id
        ORDER BY rs.tanggal_servis DESC
    ");
    $stmt->execute();
    $servis = $stmt->fetchAll();

    // Hitung statistik
    $statTotal = count($servis);
    $statBulanIni = 0;
    $statBiayaTotal = 0;

    $bulanIni = date('Y-m');
    foreach ($servis as $s) {
        $biaya = (int)($s['biaya'] ?? 0);
        if ($biaya > 0) $statBiayaTotal += $biaya;
        if (strpos($s['tanggal_servis'], $bulanIni) === 0) $statBulanIni++;
    }

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

function tglIndo(?string $tgl): string {
    if (!$tgl) return '—';
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($tgl);
    return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function statusCls(string $s): string {
    return match($s) { 'Tersedia' => 'low', 'Dipakai' => 'accent', 'Servis' => 'med', default => 'low' };
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="topbar">
  <div class="topbar-title">
    <span>FleetHub</span> / Jadwal Servis
  </div>
  <div class="topbar-actions">
    <button class="btn btn-primary" id="btnFilter">🔍 Filter</button>
  </div>
</div>

<div class="content">

  <!-- Stats -->
  <div class="stats-bar">
    <div class="stat-card">
      <div class="stat-label">Total Servis</div>
      <div class="stat-value"><?= $statTotal ?></div>
      <div class="stat-sub">semua waktu</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Servis Bulan Ini</div>
      <div class="stat-value" style="color:var(--accent)"><?= $statBulanIni ?></div>
      <div class="stat-sub">kali</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Biaya</div>
      <div class="stat-value" style="color:var(--med)">Rp <?= number_format($statBiayaTotal, 0, ',', '.') ?></div>
      <div class="stat-sub">pengeluaran</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Rata-rata Biaya</div>
      <div class="stat-value" style="color:var(--low)">Rp <?= $statTotal > 0 ? number_format((int)($statBiayaTotal / $statTotal), 0, ',', '.') : '0' ?></div>
      <div class="stat-sub">per servis</div>
    </div>
  </div>

  <!-- Filter & Search -->
  <div class="toolbar">
    <form method="GET" class="search-form" id="formFilter">
      <input type="text" name="search" placeholder="Cari plat, merk, jenis servis..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="search-input" />
      <select name="bulan" class="filter-select">
        <option value="">Semua Bulan</option>
        <?php
          $bulanList = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
          $bulanNow = (int)date('m');
          for ($i = 1; $i <= 12; $i++) {
            $selected = isset($_GET['bulan']) && $_GET['bulan'] == $i ? 'selected' : '';
            echo "<option value=\"$i\" $selected>" . $bulanList[$i-1] . "</option>";
          }
        ?>
      </select>
      <button type="submit" class="btn btn-secondary">Cari</button>
      <?php if (!empty($_GET['search']) || !empty($_GET['bulan'])): ?>
        <a href="<?= BASE_URL ?>/index.php?page=jadwal_servis" class="btn btn-ghost">Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Tabel Servis -->
  <div class="table-wrap">
    <div class="table-header">
      <div class="t-title">Daftar Jadwal Servis <span class="count-badge"><?= count($servis) ?> items</span></div>
    </div>
    <?php if (empty($servis)): ?>
      <div style="padding:40px;text-align:center;color:var(--text-muted)">
        <p>Belum ada data servis.</p>
      </div>
    <?php else: ?>
      <table class="servis-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Plat Nomor</th>
            <th>Kendaraan</th>
            <th>Jenis Servis</th>
            <th>Bengkel</th>
            <th>Kilometer</th>
            <th>Biaya</th>
            <th>Catatan</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($servis as $s):
            $search = $_GET['search'] ?? '';
            $bulan = $_GET['bulan'] ?? '';

            // Filter search
            if ($search && !stripos($s['nomor_plat'] . ' ' . $s['merek'] . ' ' . $s['model'] . ' ' . $s['jenis_servis'], $search)) continue;

            // Filter bulan
            if ($bulan && (int)date('m', strtotime($s['tanggal_servis'])) != (int)$bulan) continue;
          ?>
            <tr>
              <td class="tgl-servis"><?= tglIndo($s['tanggal_servis']) ?></td>
              <td><strong><?= htmlspecialchars($s['nomor_plat']) ?></strong></td>
              <td><?= htmlspecialchars($s['merek'] . ' ' . $s['model']) ?></td>
              <td class="jenis-servis"><span class="badge-servis"><?= htmlspecialchars($s['jenis_servis']) ?></span></td>
              <td><?= htmlspecialchars($s['bengkel'] ?? '—') ?></td>
              <td class="km-servis"><?= !empty($s['kilometer']) && (int)$s['kilometer'] > 0 ? number_format((int)$s['kilometer']) . ' km' : '—' ?></td>
              <td class="biaya-servis">
                <?php if (!empty($s['biaya']) && (int)$s['biaya'] > 0): ?>
                  <strong>Rp <?= number_format((int)$s['biaya'], 0, ',', '.') ?></strong>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td class="catatan-servis" title="<?= htmlspecialchars($s['catatan'] ?? '') ?>"><?= !empty($s['catatan']) ? substr(htmlspecialchars($s['catatan']), 0, 30) . '...' : '—' ?></td>
              <td>
                <span class="priority priority-<?= statusCls($s['status']) ?>">
                  <?= htmlspecialchars($s['status']) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<style>
  .servis-table { width: 100%; border-collapse: collapse; }
  .servis-table thead tr { border-bottom: 1px solid var(--border); background: var(--surface-2); }
  .servis-table thead th {
    padding: 12px 14px;
    text-align: left;
    font-size: 11px;
    font-family: var(--font-mono);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--text-dim);
    font-weight: 600;
    white-space: nowrap;
  }
  .servis-table tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
  .servis-table tbody tr:last-child { border-bottom: none; }
  .servis-table tbody tr:hover { background: var(--surface-2); }
  .servis-table tbody td {
    padding: 12px 14px;
    font-size: 13px;
    color: var(--text);
    vertical-align: middle;
  }
  .tgl-servis { font-family: var(--font-mono); font-size: 12px; color: var(--text-muted); }
  .jenis-servis { min-width: 120px; }
  .badge-servis {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    background: var(--accent-glow);
    color: var(--accent);
    border: 1px solid rgba(37, 99, 235, 0.25);
  }
  .km-servis { font-family: var(--font-mono); font-size: 12px; color: var(--text-muted); }
  .biaya-servis { font-family: var(--font-mono); font-size: 12px; }
  .catatan-servis { font-size: 12px; color: var(--text-muted); max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
