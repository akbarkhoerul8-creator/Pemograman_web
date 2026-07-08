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
  <div class="topbar-title"><span>FleetHub</span> / Riwayat Penggunaan</div>
  <div class="topbar-actions">
    <button class="btn btn-primary" onclick="openModalTambah()">+ Tambah Penggunaan</button>
  </div>
</div>

<div class="content">

  <!-- ── Page Header ── -->
  <div class="page-header">
    <div>
      <h1>Riwayat Penggunaan</h1>
      <p>Catat dan pantau pemakaian kendaraan oleh pengemudi.</p>
    </div>
  </div>

  <!-- ── Stats ── -->
  <div class="stats-bar">
    <div class="stat-card">
      <div class="stat-label">Total Penggunaan</div>
      <div class="stat-value" id="statTotal">—</div>
      <div class="stat-sub">semua catatan</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Sedang Berjalan</div>
      <div class="stat-value" style="color:var(--accent)" id="statBerjalan">—</div>
      <div class="stat-sub">belum selesai</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Selesai</div>
      <div class="stat-value" style="color:var(--low)" id="statSelesai">—</div>
      <div class="stat-sub">sudah kembali</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Jarak Tempuh</div>
      <div class="stat-value" id="statJarak">—</div>
      <div class="stat-sub">kilometer</div>
    </div>
  </div>

  <!-- ── Filter ── -->
  <div class="filter-bar">
    <div class="filter-search">
      <span class="filter-search-icon">🔍</span>
      <input type="text" id="searchInput" placeholder="Cari plat, kendaraan, pengemudi, tujuan…"
             autocomplete="off" oninput="loadRiwayat()" />
    </div>
    <select id="filterStatus" onchange="loadRiwayat()">
      <option value="">Semua Status</option>
      <option value="berjalan">Sedang Berjalan</option>
      <option value="selesai">Selesai</option>
    </select>
    <button class="btn btn-ghost" onclick="
      document.getElementById('searchInput').value='';
      document.getElementById('filterStatus').value='';
      loadRiwayat()">Reset</button>
  </div>

  <!-- ── Toast ── -->
  <div id="toast" class="toast" aria-live="polite"></div>

  <!-- ── Tabel ── -->
  <div class="table-wrap">
    <div class="table-header">
      <div class="t-title">
        Daftar Riwayat Penggunaan
        <span class="count-badge" id="countBadge">0 items</span>
      </div>
    </div>
    <div style="overflow-x:auto">
      <table id="tabelRiwayat">
        <thead>
          <tr>
            <th>#</th>
            <th>Kendaraan</th>
            <th>Pengemudi</th>
            <th>Tujuan</th>
            <th>Tgl Mulai</th>
            <th>Tgl Selesai</th>
            <th>KM Awal</th>
            <th>KM Akhir</th>
            <th>Jarak</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="tableBody">
          <tr><td colspan="11" class="empty-row">Memuat data…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /content -->

<!-- ════ MODAL TAMBAH ════ -->
<div id="modalTambah" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box">
    <div class="modal-head">
      <h2>Tambah Penggunaan</h2>
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
        <div class="form-group" style="grid-column:span 2">
          <label>Nama Pengemudi <span class="req">*</span></label>
          <input type="text" id="tambahPengemudi" placeholder="Nama pengemudi" />
        </div>
        <div class="form-group" style="grid-column:span 2">
          <label>Tujuan</label>
          <input type="text" id="tambahTujuan" placeholder="Tujuan penggunaan…" />
        </div>
        <div class="form-group">
          <label>Tanggal Mulai <span class="req">*</span></label>
          <input type="date" id="tambahTanggalMulai" />
        </div>
        <div class="form-group">
          <label>Tanggal Selesai</label>
          <input type="date" id="tambahTanggalSelesai" />
        </div>
        <div class="form-group">
          <label>KM Awal</label>
          <input type="number" id="tambahKmAwal" placeholder="0" min="0" />
        </div>
        <div class="form-group">
          <label>KM Akhir</label>
          <input type="number" id="tambahKmAkhir" placeholder="0" min="0" />
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
      <h2>Edit Penggunaan</h2>
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
        <div class="form-group" style="grid-column:span 2">
          <label>Nama Pengemudi <span class="req">*</span></label>
          <input type="text" id="editPengemudi" />
        </div>
        <div class="form-group" style="grid-column:span 2">
          <label>Tujuan</label>
          <input type="text" id="editTujuan" />
        </div>
        <div class="form-group">
          <label>Tanggal Mulai <span class="req">*</span></label>
          <input type="date" id="editTanggalMulai" />
        </div>
        <div class="form-group">
          <label>Tanggal Selesai</label>
          <input type="date" id="editTanggalSelesai" />
        </div>
        <div class="form-group">
          <label>KM Awal</label>
          <input type="number" id="editKmAwal" min="0" />
        </div>
        <div class="form-group">
          <label>KM Akhir</label>
          <input type="number" id="editKmAkhir" min="0" />
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
        Tanggal selesai akan diisi otomatis dengan hari ini.
      </p>
      <input type="hidden" id="selesaiId" />
      <div class="form-group">
        <label>KM Akhir</label>
        <input type="number" id="selesaiKmAkhir" placeholder="Kosongkan jika belum tahu" min="0" />
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
const RIWAYAT_API = '<?= BASE_URL ?>/api/riwayat_penggunaan.php';

function showToast(msg, type = 'success') {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className   = 'toast toast-' + type + ' toast-show';
  setTimeout(() => el.classList.remove('toast-show'), 3500);
}

function openModal(id)  { document.getElementById(id).classList.add('modal-active'); }
function closeModal(id) { document.getElementById(id).classList.remove('modal-active'); }

['modalTambah','modalEdit','modalSelesai'].forEach(id => {
  document.getElementById(id).addEventListener('click', function(e) {
    if (e.target === this) closeModal(id);
  });
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') ['modalTambah','modalEdit','modalSelesai'].forEach(closeModal);
});

function badgeStatus(selesai) {
  return selesai
    ? '<span class="priority priority-low">Selesai</span>'
    : '<span class="priority priority-accent">Berjalan</span>';
}

function formatTgl(t) {
  if (!t) return '—';
  return new Date(t).toLocaleDateString('id-ID', {day:'2-digit', month:'short', year:'numeric'});
}

function aksiTombol(r) {
  let html = '<div class="action-group">';
  html += `<button class="btn btn-detail" onclick="openModalEdit(${r.id})">Edit</button>`;
  if (!r.tanggal_selesai) {
    html += `<button class="btn-icon btn-edit" title="Tandai Selesai" onclick="openModalSelesai(${r.id})">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
    </button>`;
  }
  html += `<button class="btn-icon btn-delete" title="Hapus" onclick="hapusRiwayat(${r.id})">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="3 6 5 6 21 6"/>
      <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
      <path d="M10 11v6M14 11v6"/>
      <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
    </svg>
  </button>`;
  html += '</div>';
  return html;
}

async function loadRiwayat() {
  const search = encodeURIComponent(document.getElementById('searchInput').value);
  const status = encodeURIComponent(document.getElementById('filterStatus').value);

  try {
    const res  = await fetch(`${RIWAYAT_API}?search=${search}&status=${status}`);
    const json = await res.json();
    if (!json.success) { showToast('Gagal memuat data', 'error'); return; }

    const s = json.stats ?? {};
    document.getElementById('statTotal').textContent    = s.total    ?? 0;
    document.getElementById('statBerjalan').textContent = s.berjalan ?? 0;
    document.getElementById('statSelesai').textContent  = s.selesai  ?? 0;
    document.getElementById('statJarak').textContent    = (s.jarak ?? 0).toLocaleString('id-ID') + ' km';
    document.getElementById('countBadge').textContent   = (json.data?.length ?? 0) + ' items';

    const tbody = document.getElementById('tableBody');
    if (!json.data.length) {
      tbody.innerHTML = `<tr><td colspan="11" class="empty-row">Belum ada riwayat penggunaan.</td></tr>`;
      return;
    }

    tbody.innerHTML = json.data.map((r, i) => {
      const jarak = (r.km_akhir && r.km_awal) ? (r.km_akhir - r.km_awal) : null;
      return `<tr>
        <td>${i + 1}</td>
        <td>
          <span class="project-name">
            ${r.nomor_plat}
            <small>${r.merek} ${r.model}</small>
          </span>
        </td>
        <td>${r.nama_pengemudi}</td>
        <td class="client-name" style="max-width:160px;white-space:normal">${r.tujuan || '—'}</td>
        <td class="deadline">${formatTgl(r.tanggal_mulai)}</td>
        <td class="deadline">${formatTgl(r.tanggal_selesai)}</td>
        <td>${r.km_awal ?? '—'}</td>
        <td>${r.km_akhir ?? '—'}</td>
        <td>${jarak !== null ? jarak.toLocaleString('id-ID') + ' km' : '—'}</td>
        <td>${badgeStatus(r.tanggal_selesai)}</td>
        <td>${aksiTombol(r)}</td>
      </tr>`;
    }).join('');
  } catch { showToast('Gagal memuat data.', 'error'); }
}

/* ── Tambah ── */
function openModalTambah() {
  ['tambahKendaraan','tambahPengemudi','tambahTujuan','tambahTanggalMulai',
   'tambahTanggalSelesai','tambahKmAwal','tambahKmAkhir']
    .forEach(id => document.getElementById(id).value = '');
  document.getElementById('tambahTanggalMulai').value = new Date().toISOString().split('T')[0];
  openModal('modalTambah');
}

async function submitTambah() {
  const body = {
    action:           'tambah',
    kendaraan_id:      document.getElementById('tambahKendaraan').value,
    nama_pengemudi:    document.getElementById('tambahPengemudi').value,
    tujuan:            document.getElementById('tambahTujuan').value,
    tanggal_mulai:     document.getElementById('tambahTanggalMulai').value,
    tanggal_selesai:   document.getElementById('tambahTanggalSelesai').value,
    km_awal:           document.getElementById('tambahKmAwal').value,
    km_akhir:          document.getElementById('tambahKmAkhir').value,
  };
  if (!body.kendaraan_id || !body.nama_pengemudi || !body.tanggal_mulai) {
    showToast('Kendaraan, pengemudi, dan tanggal mulai wajib diisi.', 'error'); return;
  }
  const res  = await fetch(RIWAYAT_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
  const json = await res.json();
  showToast(json.message, json.success ? 'success' : 'error');
  if (json.success) { closeModal('modalTambah'); loadRiwayat(); }
}

/* ── Edit ── */
async function openModalEdit(id) {
  const res  = await fetch(`${RIWAYAT_API}?action=get&id=${id}`);
  const json = await res.json();
  if (!json.success) { showToast('Gagal memuat data.', 'error'); return; }
  const d = json.data;
  document.getElementById('editId').value              = d.id;
  document.getElementById('editKendaraan').value       = d.kendaraan_id;
  document.getElementById('editPengemudi').value       = d.nama_pengemudi;
  document.getElementById('editTujuan').value          = d.tujuan || '';
  document.getElementById('editTanggalMulai').value    = d.tanggal_mulai;
  document.getElementById('editTanggalSelesai').value  = d.tanggal_selesai || '';
  document.getElementById('editKmAwal').value          = d.km_awal ?? '';
  document.getElementById('editKmAkhir').value         = d.km_akhir ?? '';
  openModal('modalEdit');
}

async function submitEdit() {
  const body = {
    action:           'edit',
    id:                document.getElementById('editId').value,
    kendaraan_id:      document.getElementById('editKendaraan').value,
    nama_pengemudi:    document.getElementById('editPengemudi').value,
    tujuan:            document.getElementById('editTujuan').value,
    tanggal_mulai:     document.getElementById('editTanggalMulai').value,
    tanggal_selesai:   document.getElementById('editTanggalSelesai').value,
    km_awal:           document.getElementById('editKmAwal').value,
    km_akhir:          document.getElementById('editKmAkhir').value,
  };
  const res  = await fetch(RIWAYAT_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
  const json = await res.json();
  showToast(json.message, json.success ? 'success' : 'error');
  if (json.success) { closeModal('modalEdit'); loadRiwayat(); }
}

/* ── Selesai ── */
function openModalSelesai(id) {
  document.getElementById('selesaiId').value = id;
  document.getElementById('selesaiKmAkhir').value = '';
  openModal('modalSelesai');
}

async function submitSelesai() {
  const body = {
    action:   'selesai',
    id:       document.getElementById('selesaiId').value,
    km_akhir: document.getElementById('selesaiKmAkhir').value,
  };
  const res  = await fetch(RIWAYAT_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
  const json = await res.json();
  showToast(json.message, json.success ? 'success' : 'error');
  if (json.success) { closeModal('modalSelesai'); loadRiwayat(); }
}

/* ── Hapus ── */
async function hapusRiwayat(id) {
  if (!confirm('Hapus riwayat penggunaan ini secara permanen?')) return;
  const res  = await fetch(RIWAYAT_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'hapus', id }) });
  const json = await res.json();
  showToast(json.message, json.success ? 'success' : 'error');
  if (json.success) loadRiwayat();
}

/* ── Init ── */
loadRiwayat();
</script>
