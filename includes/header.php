<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FleetHub — Pengelolaan Aset Kendaraan</title>
  <!-- Path aset disesuaikan dari router (index.php) -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
  <script src="<?= BASE_URL ?>/assets/js/script.js" defer></script>
</head>

<body>
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-mark">// FLEET</div>
      <div class="logo-name">FleetHub</div>
    </div>
    <ul class="sidebar-nav">
      <li><a href="<?= BASE_URL ?>/index.php?page=home">🚗 Daftar Kendaraan</a></li>
      <li><a href="<?= BASE_URL ?>/index.php?page=maintenance">🔧 Task Maintenance</a></li>
      <li><a href="<?= BASE_URL ?>/index.php?page=jadwal_servis">📅 Jadwal Servis</a></li>
      <li><a href="<?= BASE_URL ?>/index.php?page=riwayat_penggunaan">📋 Riwayat Penggunaan</a></li>
      <li><a href="<?= BASE_URL ?>/index.php?page=laporan">📊 Laporan</a></li>
      <li style="margin-top:auto;border-top:1px solid var(--border);padding-top:10px">
        <a href="<?= BASE_URL ?>/index.php?page=logout" style="color:var(--high)">🚪 Logout</a>
      </li>
    </ul>
    <div class="sidebar-footer">v2.5.0 — April 2026</div>
  </aside>

  <main class="main">
