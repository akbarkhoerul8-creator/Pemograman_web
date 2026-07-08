<?php
require_once __DIR__ . '/../config/database.php';

// Ambil data awal dari DB untuk render server-side (SEO + no-JS fallback)
try {
    $pdo   = getKoneksi();
    $stat  = $pdo->query("SELECT
        COUNT(*)               AS total,
        SUM(status='Tersedia') AS tersedia,
        SUM(status='Dipakai')  AS dipakai,
        SUM(status='Servis')   AS servis
    FROM kendaraan")->fetch();

    $kendaraan_list = $pdo->query("SELECT * FROM kendaraan ORDER BY id DESC")->fetchAll();
} catch (Exception $e) {
    $stat           = ['total' => 0, 'tersedia' => 0, 'dipakai' => 0, 'servis' => 0];
    $kendaraan_list = [];
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- ── Topbar ── -->
<div class="topbar">
  <div class="topbar-title"><span>FleetHub</span> / Semua Kendaraan</div>
  <div class="topbar-actions">
    <button class="btn btn-primary" id="btnTambah">+ Tambah Kendaraan</button>
  </div>
</div>

<div class="content">

  <!-- ── Page Header ── -->
  <div class="page-header">
    <div>
      <h1>Pengelolaan Aset Kendaraan</h1>
      <p>Monitor seluruh armada kendaraan perusahaan Anda secara real-time.</p>
    </div>
  </div>

  <!-- ── Stats ── -->
  <div class="stats-bar" id="statsBar">
    <div class="stat-card">
      <div class="stat-label">Total Kendaraan</div>
      <div class="stat-value" id="statTotal"><?= (int)$stat['total'] ?></div>
      <div class="stat-sub">unit terdaftar</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Tersedia</div>
      <div class="stat-value" style="color:var(--low)" id="statTersedia"><?= (int)$stat['tersedia'] ?></div>
      <div class="stat-sub">siap digunakan</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Sedang Dipakai</div>
      <div class="stat-value" style="color:var(--accent)" id="statDipakai"><?= (int)$stat['dipakai'] ?></div>
      <div class="stat-sub">unit aktif</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Dalam Servis</div>
      <div class="stat-value" style="color:var(--med)" id="statServis"><?= (int)$stat['servis'] ?></div>
      <div class="stat-sub">unit</div>
    </div>
  </div>

  <!-- ── Filter & Search ── -->
  <div class="filter-bar">
    <div class="filter-search">
      <span class="filter-search-icon">🔍</span>
      <input type="text" id="searchInput" placeholder="Cari merk, plat, atau lokasi..." autocomplete="off" />
    </div>
    <select id="statusFilter">
      <option value="">Semua Status</option>
      <option value="Tersedia">Tersedia</option>
      <option value="Dipakai">Dipakai</option>
      <option value="Servis">Servis</option>
    </select>
    <select id="jenisFilter">
      <option value="">Semua Jenis</option>
      <option value="Mobil">Mobil</option>
      <option value="Motor">Motor</option>
    </select>
    <button class="btn btn-ghost" id="btnReset">Reset</button>
  </div>

  <!-- ── Toast notifikasi ── -->
  <div id="toast" class="toast" aria-live="polite"></div>

  <!-- ── Tabel ── -->
  <div class="table-wrap">
    <div class="table-header">
      <div class="t-title">
        Daftar Kendaraan
        <span class="count-badge" id="countBadge"><?= count($kendaraan_list) ?> items</span>
      </div>
    </div>
    <table id="tabelKendaraan">
      <thead>
        <tr>
          <th>#</th>
          <th>Plat Nomor</th>
          <th>Merk / Model</th>
          <th>Jenis</th>
          <th>Tahun</th>
          <th>Kilometer</th>
          <th>Lokasi</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody id="tbodyKendaraan">
        <?php if (empty($kendaraan_list)): ?>
          <tr><td colspan="9" class="empty-row">Belum ada data kendaraan.</td></tr>
        <?php else: foreach ($kendaraan_list as $i => $k): ?>
          <tr data-id="<?= $k['id'] ?>">
            <td><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></td>
            <td><strong><?= htmlspecialchars($k['nomor_plat']) ?></strong></td>
            <td>
              <span class="project-name">
                <?= htmlspecialchars($k['merek']) ?> <?= htmlspecialchars($k['model']) ?>
                <small><?= htmlspecialchars($k['warna'] ?: '—') ?> • <?= htmlspecialchars($k['bahan_bakar']) ?></small>
              </span>
            </td>
            <td><?= htmlspecialchars($k['jenis']) ?></td>
            <td><?= (int)$k['tahun'] ?></td>
            <td class="deadline"><?= number_format((int)$k['kilometer']) ?> km</td>
            <td class="client-name"><?= htmlspecialchars($k['lokasi'] ?: '—') ?></td>
            <td>
              <?php
                $statusMap = ['Tersedia' => 'low', 'Dipakai' => 'accent', 'Servis' => 'med'];
                $cls = $statusMap[$k['status']] ?? 'low';
              ?>
              <span class="priority priority-<?= $cls ?>"><?= htmlspecialchars($k['status']) ?></span>
            </td>
            <td>
              <div class="action-group">
                <a href="<?= BASE_URL ?>/index.php?page=detail&id=<?= $k['id'] ?>" class="btn btn-detail">Detail</a>
                <button class="btn-icon btn-edit" title="Edit" onclick="bukaModalEdit(<?= $k['id'] ?>)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button class="btn-icon btn-delete" title="Hapus" onclick="hapusKendaraan(<?= $k['id'] ?>, '<?= htmlspecialchars($k['nomor_plat'], ENT_QUOTES) ?>')">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div><!-- /table-wrap -->

</div><!-- /content -->

<!-- ════════════════════════════════════════════════════
     MODAL TAMBAH / EDIT KENDARAAN
═══════════════════════════════════════════════════════ -->
<div id="modalKendaraan" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal-box">
    <div class="modal-head">
      <h2 id="modalTitle">Tambah Kendaraan</h2>
      <button class="modal-close" onclick="tutupModal()" aria-label="Tutup">&times;</button>
    </div>
    <form id="formKendaraan" novalidate>
      <input type="hidden" id="editId" value="">
      <div class="modal-body">
        <div class="form-grid-2">
          <div class="form-group">
            <label for="inputPlat">Plat Nomor <span class="req">*</span></label>
            <input type="text" id="inputPlat" placeholder="B 1234 ABC" required />
          </div>
          <div class="form-group">
            <label for="inputJenis">Jenis</label>
            <select id="inputJenis">
              <option value="Mobil">Mobil</option>
              <option value="Motor">Motor</option>
              <option value="Truk">Truk</option>
              <option value="Bus">Bus</option>
            </select>
          </div>
          <div class="form-group">
            <label for="inputMerk">Merk <span class="req">*</span></label>
            <input type="text" id="inputMerk" placeholder="Toyota" required />
          </div>
          <div class="form-group">
            <label for="inputModel">Model <span class="req">*</span></label>
            <input type="text" id="inputModel" placeholder="Fortuner VRZ" required />
          </div>
          <div class="form-group">
            <label for="inputTahun">Tahun <span class="req">*</span></label>
            <input type="number" id="inputTahun" placeholder="2022" min="1990" max="2030" required />
          </div>
          <div class="form-group">
            <label for="inputWarna">Warna</label>
            <input type="text" id="inputWarna" placeholder="Putih" />
          </div>
          <div class="form-group">
            <label for="inputKm">Kilometer</label>
            <input type="number" id="inputKm" placeholder="0" min="0" />
          </div>
          <div class="form-group">
            <label for="inputBbm">Bahan Bakar</label>
            <select id="inputBbm">
              <option value="Bensin">Bensin</option>
              <option value="Solar">Solar</option>
              <option value="Listrik">Listrik</option>
            </select>
          </div>
          <div class="form-group">
            <label for="inputLokasi">Lokasi</label>
            <input type="text" id="inputLokasi" placeholder="Jakarta Selatan" />
          </div>
          <div class="form-group">
            <label for="inputStatus">Status</label>
            <select id="inputStatus">
              <option value="Tersedia">Tersedia</option>
              <option value="Dipakai">Dipakai</option>
              <option value="Servis">Servis</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="tutupModal()">Batal</button>
        <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- ════════════════════════════════════════════════════
     MODAL KONFIRMASI HAPUS
═══════════════════════════════════════════════════════ -->
<div id="modalHapus" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box modal-box-sm">
    <div class="modal-head">
      <h2>Hapus Kendaraan</h2>
      <button class="modal-close" onclick="tutupModalHapus()" aria-label="Tutup">&times;</button>
    </div>
    <div class="modal-body">
      <p class="hapus-desc">Kendaraan <strong id="hapusPlat"></strong> akan dihapus permanen.<br>Tindakan ini tidak dapat dibatalkan.</p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn btn-ghost" onclick="tutupModalHapus()">Batal</button>
      <button type="button" class="btn btn-danger" id="btnKonfirmasiHapus">Ya, Hapus</button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- ════════════════════════════════════════════════════
     SCRIPT CRUD
═══════════════════════════════════════════════════════ -->
<script>
const API = '<?= BASE_URL ?>/api/kendaraan.php';

/* ── Debounce helper ── */
function debounce(fn, ms) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

/* ── Toast ── */
function showToast(msg, type = 'success') {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className   = 'toast toast-' + type + ' toast-show';
  setTimeout(() => el.classList.remove('toast-show'), 3500);
}

/* ══════════════════════════════
   RENDER TABEL
══════════════════════════════ */
function statusClass(s) {
  return { Tersedia: 'low', Dipakai: 'accent', Servis: 'med' }[s] || 'low';
}

function renderRows(list) {
  const tbody = document.getElementById('tbodyKendaraan');
  document.getElementById('countBadge').textContent = list.length + ' items';

  if (!list.length) {
    tbody.innerHTML = `<tr><td colspan="9" class="empty-row">Tidak ada kendaraan ditemukan.</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map((k, i) => `
    <tr data-id="${k.id}">
      <td>${String(i + 1).padStart(2, '0')}</td>
      <td><strong>${esc(k.nomor_plat)}</strong></td>
      <td>
        <span class="project-name">
          ${esc(k.merek)} ${esc(k.model)}
          <small>${esc(k.warna || '—')} • ${esc(k.bahan_bakar)}</small>
        </span>
      </td>
      <td>${esc(k.jenis)}</td>
      <td>${k.tahun}</td>
      <td class="deadline">${Number(k.kilometer).toLocaleString('id-ID')} km</td>
      <td class="client-name">${esc(k.lokasi || '—')}</td>
      <td><span class="priority priority-${statusClass(k.status)}">${esc(k.status)}</span></td>
      <td>
        <div class="action-group">
          <a href="<?= BASE_URL ?>/index.php?page=detail&id=${k.id}" class="btn btn-detail">Detail</a>
          <button class="btn-icon btn-edit" title="Edit" onclick="bukaModalEdit(${k.id})">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="btn-icon btn-delete" title="Hapus" onclick="hapusKendaraan(${k.id}, '${esc(k.nomor_plat)}')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          </button>
        </div>
      </td>
    </tr>`).join('');
}

function updateStats(stats) {
  if (!stats) return;
  document.getElementById('statTotal').textContent    = stats.total    ?? 0;
  document.getElementById('statTersedia').textContent = stats.tersedia ?? 0;
  document.getElementById('statDipakai').textContent  = stats.dipakai  ?? 0;
  document.getElementById('statServis').textContent   = stats.servis   ?? 0;
}

/* ── XSS escape ── */
function esc(str) {
  return String(str ?? '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ══════════════════════════════
   FETCH DATA (search + filter)
══════════════════════════════ */
async function loadData() {
  const search = document.getElementById('searchInput').value.trim();
  const status = document.getElementById('statusFilter').value;
  const jenis  = document.getElementById('jenisFilter').value;

  const params = new URLSearchParams({ search, status, jenis });
  try {
    const res  = await fetch(`${API}?${params}`);
    const json = await res.json();
    if (json.success) {
      renderRows(json.data);
      updateStats(json.stats);
    }
  } catch (e) {
    showToast('Gagal memuat data.', 'error');
  }
}

/* Search realtime dengan debounce */
document.getElementById('searchInput').addEventListener('input',  debounce(loadData, 300));
document.getElementById('statusFilter').addEventListener('change', loadData);
document.getElementById('jenisFilter').addEventListener('change',  loadData);
document.getElementById('btnReset').addEventListener('click', () => {
  document.getElementById('searchInput').value  = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('jenisFilter').value  = '';
  loadData();
});

/* ══════════════════════════════
   MODAL TAMBAH / EDIT
══════════════════════════════ */
function bukaModal(mode = 'tambah') {
  const modal = document.getElementById('modalKendaraan');
  document.getElementById('modalTitle').textContent =
    mode === 'tambah' ? 'Tambah Kendaraan' : 'Edit Kendaraan';
  document.getElementById('btnSimpan').textContent = mode === 'tambah' ? 'Simpan' : 'Perbarui';
  modal.classList.add('modal-active');
  document.getElementById('inputPlat').focus();
}

function tutupModal() {
  document.getElementById('modalKendaraan').classList.remove('modal-active');
  document.getElementById('formKendaraan').reset();
  document.getElementById('editId').value = '';
}

function bukaModalEdit(id) {
  fetch(`${API}?action=get&id=${id}`)
    .then(r => r.json())
    .then(json => {
      if (!json.success) { showToast('Data tidak ditemukan.', 'error'); return; }
      const k = json.data;
      document.getElementById('editId').value     = k.id;
      document.getElementById('inputPlat').value  = k.nomor_plat;
      document.getElementById('inputMerk').value  = k.merek;
      document.getElementById('inputModel').value = k.model;
      document.getElementById('inputTahun').value = k.tahun;
      document.getElementById('inputWarna').value = k.warna  ?? '';
      document.getElementById('inputKm').value    = k.kilometer;
      document.getElementById('inputLokasi').value= k.lokasi ?? '';
      setSelectVal('inputJenis',  k.jenis);
      setSelectVal('inputBbm',    k.bahan_bakar);
      setSelectVal('inputStatus', k.status);
      bukaModal('edit');
    })
    .catch(() => showToast('Gagal mengambil data.', 'error'));
}

function setSelectVal(id, val) {
  const el = document.getElementById(id);
  if (!el) return;
  [...el.options].forEach(o => o.selected = (o.value === val));
}

/* Tombol Tambah Kendaraan */
document.getElementById('btnTambah').addEventListener('click', () => {
  document.getElementById('formKendaraan').reset();
  document.getElementById('editId').value = '';
  bukaModal('tambah');
});

/* Klik di luar modal = tutup */
document.getElementById('modalKendaraan').addEventListener('click', function(e) {
  if (e.target === this) tutupModal();
});

/* ══════════════════════════════
   SUBMIT FORM (tambah / edit)
══════════════════════════════ */
document.getElementById('formKendaraan').addEventListener('submit', async function(e) {
  e.preventDefault();
  const id = document.getElementById('editId').value;

  const payload = {
    action:      id ? 'edit' : 'tambah',
    id:          id ? Number(id) : undefined,
    nomor_plat:  document.getElementById('inputPlat').value.trim(),
    merek:       document.getElementById('inputMerk').value.trim(),
    model:       document.getElementById('inputModel').value.trim(),
    tahun:       Number(document.getElementById('inputTahun').value),
    warna:       document.getElementById('inputWarna').value.trim(),
    jenis:       document.getElementById('inputJenis').value,
    kilometer:   Number(document.getElementById('inputKm').value) || 0,
    bahan_bakar: document.getElementById('inputBbm').value,
    lokasi:      document.getElementById('inputLokasi').value.trim(),
    status:      document.getElementById('inputStatus').value,
  };

  // Validasi sederhana
  if (!payload.nomor_plat || !payload.merek || !payload.model || !payload.tahun) {
    showToast('Field wajib (*) tidak boleh kosong.', 'error');
    return;
  }

  const btn = document.getElementById('btnSimpan');
  btn.disabled    = true;
  btn.textContent = 'Menyimpan…';

  try {
    const res  = await fetch(API, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload),
    });
    const json = await res.json();

    if (json.success) {
      showToast(json.message);
      tutupModal();
      loadData();
    } else {
      showToast(json.message || 'Gagal menyimpan.', 'error');
    }
  } catch (err) {
    showToast('Koneksi gagal.', 'error');
  } finally {
    btn.disabled    = false;
    btn.textContent = id ? 'Perbarui' : 'Simpan';
  }
});

/* ══════════════════════════════
   HAPUS
══════════════════════════════ */
let _hapusId = null;

function hapusKendaraan(id, plat) {
  _hapusId = id;
  document.getElementById('hapusPlat').textContent = plat;
  document.getElementById('modalHapus').classList.add('modal-active');
}

function tutupModalHapus() {
  _hapusId = null;
  document.getElementById('modalHapus').classList.remove('modal-active');
}

document.getElementById('modalHapus').addEventListener('click', function(e) {
  if (e.target === this) tutupModalHapus();
});

document.getElementById('btnKonfirmasiHapus').addEventListener('click', async () => {
  if (!_hapusId) return;

  const btn = document.getElementById('btnKonfirmasiHapus');
  btn.disabled    = true;
  btn.textContent = 'Menghapus…';

  try {
    const res  = await fetch(API, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'hapus', id: _hapusId }),
    });
    const json = await res.json();

    if (json.success) {
      showToast(json.message);
      tutupModalHapus();
      loadData();
    } else {
      showToast(json.message || 'Gagal menghapus.', 'error');
    }
  } catch (err) {
    showToast('Koneksi gagal.', 'error');
  } finally {
    btn.disabled    = false;
    btn.textContent = 'Ya, Hapus';
  }
});

/* Tutup modal dengan Escape */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { tutupModal(); tutupModalHapus(); }
});
</script>
