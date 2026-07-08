<?php
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ' . BASE_URL . '/index.php?page=login');
    exit;
}

$pdo        = getKoneksi();
$kendaraans = $pdo->query("SELECT id, nomor_plat, merek, model FROM kendaraan ORDER BY nomor_plat ASC")->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- ── Topbar ── -->
<div class="topbar">
  <div class="topbar-title"><span>FleetHub</span> / Task Maintenance</div>
  <div class="topbar-actions">
    <button class="btn btn-primary" onclick="openModalTambah()">+ Tambah Jadwal</button>
  </div>
</div>

<div class="content">

  <!-- ── Page Header ── -->
  <div class="page-header">
    <div>
      <h1>Task Maintenance</h1>
      <p>Kelola jadwal servis dan perawatan seluruh armada kendaraan.</p>
    </div>
  </div>

  <!-- ── Stats ── -->
  <div class="stats-bar">
    <div class="stat-card">
      <div class="stat-label">Total Jadwal</div>
      <div class="stat-value" id="statTotal">—</div>
      <div class="stat-sub">semua jadwal</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Terjadwal</div>
      <div class="stat-value" style="color:var(--accent)" id="statTerjadwal">—</div>
      <div class="stat-sub">menunggu servis</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Selesai</div>
      <div class="stat-value" style="color:var(--low)" id="statSelesai">—</div>
      <div class="stat-sub">selesai dikerjakan</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Dibatalkan</div>
      <div class="stat-value" style="color:var(--high)" id="statDibatalkan">—</div>
      <div class="stat-sub">jadwal batal</div>
    </div>
  </div>

  <!-- ── Filter ── -->
  <div class="filter-bar">
    <div class="filter-search">
      <span class="filter-search-icon">🔍</span>
      <input type="text" id="searchInput" placeholder="Cari plat, merek, jenis servis…"
             autocomplete="off" oninput="loadJadwal()" />
    </div>
    <select id="filterStatus" onchange="loadJadwal()">
      <option value="">Semua Status</option>
      <option value="Terjadwal">Terjadwal</option>
      <option value="Selesai">Selesai</option>
      <option value="Dibatalkan">Dibatalkan</option>
    </select>
    <button class="btn btn-ghost" onclick="
      document.getElementById('searchInput').value='';
      document.getElementById('filterStatus').value='';
      loadJadwal()">Reset</button>
  </div>

  <!-- ── Toast ── -->
  <div id="toast" class="toast" aria-live="polite"></div>

  <!-- ── Tabel ── -->
  <div class="table-wrap">
    <div class="table-header">
      <div class="t-title">
        Daftar Jadwal Servis
        <span class="count-badge" id="countBadge">0 items</span>
      </div>
    </div>
    <div style="overflow-x:auto">
      <table id="tabelMaintenance">
        <thead>
          <tr>
            <th>#</th>
            <th>Kendaraan</th>
            <th>Tanggal</th>
            <th>Jenis Servis</th>
            <th>Bengkel</th>
            <th>Est. Biaya</th>
            <th>Status</th>
            <th>Catatan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="tableBody">
          <tr><td colspan="9" class="empty-row">Memuat data…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /content -->

<!-- ════ MODAL TAMBAH ════ -->
<div id="modalTambah" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box">
    <div class="modal-head">
      <h2>Tambah Jadwal Servis</h2>
      <button class="modal-close" onclick="closeModal('modalTambah')" aria-label="Tutup">&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-grid-2">
        <div class="form-group" style="grid-column:span 2">
          <label>Kendaraan <span class="req">*</span></label>
          <select id="tambahKendaraan">
            <option value="">— Pilih Kendaraan —</option>
            <?php foreach ($kendaraans as $k): ?>
            <option value="<?= $k['id'] ?>">
              <?= htmlspecialchars($k['nomor_plat'] . ' — ' . $k['merek'] . ' ' . $k['model']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Tanggal Jadwal <span class="req">*</span></label>
          <input type="date" id="tambahTanggal" />
        </div>
        <div class="form-group">
          <label>Jenis Servis <span class="req">*</span></label>
          <input type="text" id="tambahJenis" placeholder="Ganti oli, tune-up, dll." />
        </div>
        <div class="form-group">
          <label>Bengkel</label>
          <input type="text" id="tambahBengkel" placeholder="Nama bengkel" />
        </div>
        <div class="form-group">
          <label>Estimasi Biaya (Rp)</label>
          <input type="number" id="tambahBiaya" placeholder="0" min="0" />
        </div>
        <div class="form-group" style="grid-column:span 2">
          <label>Catatan</label>
          <input type="text" id="tambahCatatan" placeholder="Catatan tambahan…" />
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('modalTambah')">Batal</button>
      <button class="btn btn-primary" onclick="submitTambah()">Simpan</button>
    </div>
  </div>
</div>

<!-- ════ MODAL EDIT ════ -->
<div id="modalEdit" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box">
    <div class="modal-head">
      <h2>Edit Jadwal Servis</h2>
      <button class="modal-close" onclick="closeModal('modalEdit')" aria-label="Tutup">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editId" />
      <div class="form-grid-2">
        <div class="form-group" style="grid-column:span 2">
          <label>Kendaraan <span class="req">*</span></label>
          <select id="editKendaraan">
            <option value="">— Pilih Kendaraan —</option>
            <?php foreach ($kendaraans as $k): ?>
            <option value="<?= $k['id'] ?>">
              <?= htmlspecialchars($k['nomor_plat'] . ' — ' . $k['merek'] . ' ' . $k['model']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Tanggal Jadwal <span class="req">*</span></label>
          <input type="date" id="editTanggal" />
        </div>
        <div class="form-group">
          <label>Jenis Servis <span class="req">*</span></label>
          <input type="text" id="editJenis" />
        </div>
        <div class="form-group">
          <label>Bengkel</label>
          <input type="text" id="editBengkel" />
        </div>
        <div class="form-group">
          <label>Estimasi Biaya (Rp)</label>
          <input type="number" id="editBiaya" min="0" />
        </div>
        <div class="form-group" style="grid-column:span 2">
          <label>Catatan</label>
          <input type="text" id="editCatatan" />
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('modalEdit')">Batal</button>
      <button class="btn btn-primary" onclick="submitEdit()">Simpan Perubahan</button>
    </div>
  </div>
</div>

<!-- ════ MODAL TANDAI SELESAI ════ -->
<div id="modalSelesai" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box modal-box-sm">
    <div class="modal-head">
      <h2>Tandai Selesai</h2>
      <button class="modal-close" onclick="closeModal('modalSelesai')" aria-label="Tutup">&times;</button>
    </div>
    <div class="modal-body">
      <p class="hapus-desc" style="margin-bottom:18px">
        Servis akan dicatat otomatis ke <strong>Riwayat Servis</strong> kendaraan.
      </p>
      <input type="hidden" id="selesaiId" />
      <div class="form-group">
        <label>Biaya Aktual (Rp)</label>
        <input type="number" id="selesaiBiaya" placeholder="Kosongkan = pakai estimasi" min="0" />
      </div>
      <div class="form-group" style="margin-top:12px">
        <label>Catatan Tambahan</label>
        <input type="text" id="selesaiCatatan" placeholder="Opsional…" />
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" onclick="closeModal('modalSelesai')">Batal</button>
      <button class="btn btn-primary" onclick="submitSelesai()">✔ Tandai Selesai</button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
const JADWAL_API = '<?= BASE_URL ?>/api/jadwal_servis.php';

function showToast(msg, type = 'success') {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className   = 'toast toast-' + type + ' toast-show';
  setTimeout(() => el.classList.remove('toast-show'), 3500);
}

function openModal(id)  {
  document.getElementById(id).classList.add('modal-active');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('modal-active');
  // tutup juga dengan klik overlay
}

// Klik di luar modal = tutup
['modalTambah','modalEdit','modalSelesai'].forEach(id => {
  document.getElementById(id).addEventListener('click', function(e) {
    if (e.target === this) closeModal(id);
  });
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') ['modalTambah','modalEdit','modalSelesai'].forEach(closeModal);
});

/* ── Badge status ── */
function badgeStatus(s) {
  const map = {
    Terjadwal: 'priority-accent',
    Selesai:   'priority-low',
    Dibatalkan:'priority-high',
  };
  return `<span class="priority ${map[s] || ''}">${s}</span>`;
}

function rupiahFormat(n) {
  return 'Rp\u00a0' + Number(n).toLocaleString('id-ID');
}

/* ── Aksi tombol tabel ── */
function aksiTerjadwal(id) {
  return `
    <div class="action-group">
      <button class="btn btn-detail" onclick="openModalEdit(${id})">Edit</button>
      <button class="btn-icon btn-edit" title="Tandai Selesai" onclick="openModalSelesai(${id})">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </button>
      <button class="btn-icon" title="Batalkan" style="color:var(--med)"
        onclick="batalJadwal(${id})">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
        </svg>
      </button>
      <button class="btn-icon btn-delete" title="Hapus" onclick="hapusJadwal(${id})">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="3 6 5 6 21 6"/>
          <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
          <path d="M10 11v6M14 11v6"/>
          <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
        </svg>
      </button>
    </div>`;
}

function aksiLainnya(id) {
  return `
    <div class="action-group">
      <button class="btn-icon btn-delete" title="Hapus" onclick="hapusJadwal(${id})">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="3 6 5 6 21 6"/>
          <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
          <path d="M10 11v6M14 11v6"/>
          <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
        </svg>
      </button>
    </div>`;
}

/* ── Load data ── */
async function loadJadwal() {
  const search = encodeURIComponent(document.getElementById('searchInput').value);
  const status = encodeURIComponent(document.getElementById('filterStatus').value);

  try {
    const res  = await fetch(`${JADWAL_API}?search=${search}&status=${status}`);
    const json = await res.json();
    if (!json.success) { showToast('Gagal memuat data', 'error'); return; }

    const s = json.stats ?? {};
    document.getElementById('statTotal').textContent      = s.total      ?? 0;
    document.getElementById('statTerjadwal').textContent  = s.terjadwal  ?? 0;
    document.getElementById('statSelesai').textContent    = s.selesai    ?? 0;
    document.getElementById('statDibatalkan').textContent = s.dibatalkan ?? 0;
    document.getElementById('countBadge').textContent     = (json.data?.length ?? 0) + ' items';

    const tbody = document.getElementById('tableBody');
    if (!json.data.length) {
      tbody.innerHTML = `<tr><td colspan="9" class="empty-row">Belum ada jadwal servis.</td></tr>`;
      return;
    }

    tbody.innerHTML = json.data.map((r, i) => {
      const tgl = new Date(r.tanggal_jadwal).toLocaleDateString('id-ID',
        {day:'2-digit', month:'short', year:'numeric'});
      return `<tr>
        <td>${i + 1}</td>
        <td>
          <span class="project-name">
            ${r.nomor_plat}
            <small>${r.merek} ${r.model}</small>
          </span>
        </td>
        <td class="deadline">${tgl}</td>
        <td>${r.jenis_servis}</td>
        <td class="client-name">${r.bengkel || '—'}</td>
        <td class="deadline">${r.estimasi_biaya > 0 ? rupiahFormat(r.estimasi_biaya) : '—'}</td>
        <td>${badgeStatus(r.status)}</td>
        <td class="client-name" style="max-width:160px;white-space:normal">${r.catatan || '—'}</td>
        <td>${r.status === 'Terjadwal' ? aksiTerjadwal(r.id) : aksiLainnya(r.id)}</td>
      </tr>`;
    }).join('');
  } catch { showToast('Gagal memuat data.', 'error'); }
}

/* ── Tambah ── */
function openModalTambah() {
  ['tambahKendaraan','tambahTanggal','tambahJenis','tambahBengkel','tambahBiaya','tambahCatatan']
    .forEach(id => document.getElementById(id).value = '');
  document.getElementById('tambahTanggal').value = new Date().toISOString().split('T')[0];
  openModal('modalTambah');
}

async function submitTambah() {
  const body = {
    action:         'tambah',
    kendaraan_id:   document.getElementById('tambahKendaraan').value,
    tanggal_jadwal: document.getElementById('tambahTanggal').value,
    jenis_servis:   document.getElementById('tambahJenis').value,
    bengkel:        document.getElementById('tambahBengkel').value,
    estimasi_biaya: Number(document.getElementById('tambahBiaya').value) || 0,
    catatan:        document.getElementById('tambahCatatan').value,
  };
  if (!body.kendaraan_id || !body.tanggal_jadwal || !body.jenis_servis) {
    showToast('Kendaraan, tanggal, dan jenis servis wajib diisi.', 'error'); return;
  }
  const res  = await fetch(JADWAL_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
  const json = await res.json();
  showToast(json.message, json.success ? 'success' : 'error');
  if (json.success) { closeModal('modalTambah'); loadJadwal(); }
}

/* ── Edit ── */
async function openModalEdit(id) {
  const res  = await fetch(`${JADWAL_API}?action=get&id=${id}`);
  const json = await res.json();
  if (!json.success) { showToast('Gagal memuat data.', 'error'); return; }
  const d = json.data;
  document.getElementById('editId').value        = d.id;
  document.getElementById('editKendaraan').value = d.kendaraan_id;
  document.getElementById('editTanggal').value   = d.tanggal_jadwal;
  document.getElementById('editJenis').value     = d.jenis_servis;
  document.getElementById('editBengkel').value   = d.bengkel || '';
  document.getElementById('editBiaya').value     = d.estimasi_biaya || '';
  document.getElementById('editCatatan').value   = d.catatan || '';
  openModal('modalEdit');
}

async function submitEdit() {
  const body = {
    action:         'edit',
    id:             document.getElementById('editId').value,
    kendaraan_id:   document.getElementById('editKendaraan').value,
    tanggal_jadwal: document.getElementById('editTanggal').value,
    jenis_servis:   document.getElementById('editJenis').value,
    bengkel:        document.getElementById('editBengkel').value,
    estimasi_biaya: Number(document.getElementById('editBiaya').value) || 0,
    catatan:        document.getElementById('editCatatan').value,
  };
  const res  = await fetch(JADWAL_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
  const json = await res.json();
  showToast(json.message, json.success ? 'success' : 'error');
  if (json.success) { closeModal('modalEdit'); loadJadwal(); }
}

/* ── Selesai ── */
function openModalSelesai(id) {
  document.getElementById('selesaiId').value = id;
  document.getElementById('selesaiBiaya').value = '';
  document.getElementById('selesaiCatatan').value = '';
  openModal('modalSelesai');
}

async function submitSelesai() {
  const body = {
    action:       'selesai',
    id:           document.getElementById('selesaiId').value,
    biaya_aktual: Number(document.getElementById('selesaiBiaya').value) || 0,
    catatan:      document.getElementById('selesaiCatatan').value,
  };
  const res  = await fetch(JADWAL_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
  const json = await res.json();
  showToast(json.message, json.success ? 'success' : 'error');
  if (json.success) { closeModal('modalSelesai'); loadJadwal(); }
}

/* ── Batal ── */
async function batalJadwal(id) {
  if (!confirm('Batalkan jadwal servis ini?')) return;
  const res  = await fetch(JADWAL_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'batal', id }) });
  const json = await res.json();
  showToast(json.message, json.success ? 'success' : 'error');
  if (json.success) loadJadwal();
}

/* ── Hapus ── */
async function hapusJadwal(id) {
  if (!confirm('Hapus jadwal ini secara permanen?')) return;
  const res  = await fetch(JADWAL_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'hapus', id }) });
  const json = await res.json();
  showToast(json.message, json.success ? 'success' : 'error');
  if (json.success) loadJadwal();
}

/* ── Init ── */
loadJadwal();
</script>
