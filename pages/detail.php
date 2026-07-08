<?php
require_once __DIR__ . '/../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_URL . '/index.php?page=home');
    exit;
}

try {
    $pdo = getKoneksi();

    $stmt = $pdo->prepare("SELECT * FROM kendaraan WHERE id = ?");
    $stmt->execute([$id]);
    $k = $stmt->fetch();
    if (!$k) {
        header('Location: ' . BASE_URL . '/index.php?page=home');
        exit;
    }

    $stmtServis = $pdo->prepare("SELECT * FROM riwayat_servis WHERE kendaraan_id = ? ORDER BY tanggal_servis DESC");
    $stmtServis->execute([$id]);
    $servis = $stmtServis->fetchAll();

    $stmtDriver = $pdo->prepare("SELECT * FROM pengemudi_kendaraan WHERE kendaraan_id = ? ORDER BY tanggal_mulai DESC LIMIT 1");
    $stmtDriver->execute([$id]);
    $pengemudi = $stmtDriver->fetch();

    $stmtDok = $pdo->prepare("SELECT * FROM dokumen_kendaraan WHERE kendaraan_id = ? ORDER BY tanggal_expire ASC");
    $stmtDok->execute([$id]);
    $dokumen = $stmtDok->fetchAll();

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

function statusCls(string $s): string {
    return match($s) { 'Tersedia' => 'low', 'Dipakai' => 'accent', 'Servis' => 'med', default => 'low' };
}
function tglIndo(?string $tgl): string {
    if (!$tgl) return '—';
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($tgl);
    return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
function inisial(string $nama): string {
    $parts = explode(' ', trim($nama));
    return implode('', array_map(fn($p) => strtoupper($p[0] ?? ''), array_slice($parts, 0, 2)));
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="topbar">
  <div class="topbar-title">
    <span>FleetHub</span> / Detail / <?= htmlspecialchars($k['nomor_plat']) ?>
  </div>
  <div class="topbar-actions">
    <button class="btn btn-ghost" id="btnEdit">✏️ Edit Data</button>
    <button class="btn btn-primary" id="btnUpdateStatus">🔄 Update Status</button>
  </div>
</div>

<div class="content">
  <a href="<?= BASE_URL ?>/index.php?page=home" class="back-link">Kembali ke Daftar Kendaraan</a>

  <!-- Hero -->
  <div class="detail-hero">
    <div class="detail-hero-left">
      <div class="priority priority-<?= statusCls($k['status']) ?>">
        <?= htmlspecialchars($k['nomor_plat']) ?>
      </div>
      <h1><?= htmlspecialchars($k['merek'] . ' ' . $k['model']) ?></h1>
      <div class="hero-meta">
        <span class="client-tag"><?= htmlspecialchars($k['jenis'] ?: 'Kendaraan') ?></span>
        <span class="client-tag" style="border-color:var(--<?= statusCls($k['status']) ?>);color:var(--<?= statusCls($k['status']) ?>);">
          <?= htmlspecialchars($k['status']) ?>
        </span>
        <?php if (!empty($k['lokasi'])): ?>
          <span class="client-tag"> <?= htmlspecialchars($k['lokasi']) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="detail-hero-right">
      <div class="deadline-label">Tahun Produksi</div>
      <div class="deadline-value"><?= (int)$k['tahun'] ?></div>
    </div>
  </div>

  <div class="detail-grid">

    <!-- ══ Kolom Kiri ══ -->
    <div class="detail-col">

      <!-- Foto -->
      <div class="card">
        <div class="card-head"><span class="card-icon"></span><h2>Foto Kendaraan</h2></div>
        <div class="card-body">
          <?php if (!empty($k['foto'])): ?>
            <img src="<?= htmlspecialchars($k['foto']) ?>" alt="Foto <?= htmlspecialchars($k['nomor_plat']) ?>" class="foto-kendaraan" id="fotoPreview" />
          <?php else: ?>
            <div class="foto-placeholder" id="fotoPlaceholder">
              <span>🚗</span><p>Belum ada foto kendaraan</p>
            </div>
            <img src="" alt="" class="foto-kendaraan" id="fotoPreview" style="display:none" />
          <?php endif; ?>
          <input type="file" id="inputFoto" accept="image/jpeg,image/png,image/webp" style="display:none" />
          <div style="margin-top:10px;display:flex;gap:8px">
            <button class="btn btn-ghost" style="flex:1" onclick="document.getElementById('inputFoto').click()">📎 Pilih Foto</button>
            <button class="btn btn-primary" style="flex:1;display:none" id="btnUploadFoto" onclick="uploadFoto()">⬆️ Upload</button>
          </div>
          <div id="uploadInfo" style="font-size:12px;color:var(--text-muted);margin-top:6px;font-family:var(--font-mono)"></div>
        </div>
      </div>

      <!-- Spesifikasi -->
      <div class="card">
        <div class="card-head"><span class="card-icon"></span><h2>Spesifikasi Kendaraan</h2></div>
        <div class="card-body spec-list">
          <div class="spec-row"><span class="spec-key">Plat Nomor</span><span class="spec-val"><?= htmlspecialchars($k['nomor_plat']) ?></span></div>
          <div class="spec-row"><span class="spec-key">Merk</span><span class="spec-val"><?= htmlspecialchars($k['merek']) ?></span></div>
          <div class="spec-row"><span class="spec-key">Model</span><span class="spec-val"><?= htmlspecialchars($k['model']) ?></span></div>
          <div class="spec-row"><span class="spec-key">Jenis</span><span class="spec-val"><?= htmlspecialchars($k['jenis'] ?: '—') ?></span></div>
          <div class="spec-row"><span class="spec-key">Tahun</span><span class="spec-val"><?= (int)$k['tahun'] ?></span></div>
          <div class="spec-row"><span class="spec-key">Warna</span><span class="spec-val"><?= htmlspecialchars($k['warna'] ?: '—') ?></span></div>
          <div class="spec-row"><span class="spec-key">Bahan Bakar</span><span class="spec-val"><?= htmlspecialchars($k['bahan_bakar'] ?: '—') ?></span></div>
          <div class="spec-row"><span class="spec-key">Kilometer</span><span class="spec-val"><?= number_format((int)$k['kilometer']) ?> km</span></div>
          <div class="spec-row"><span class="spec-key">Lokasi</span><span class="spec-val"><?= htmlspecialchars($k['lokasi'] ?: '—') ?></span></div>
          <div class="spec-row">
            <span class="spec-key">Status</span>
            <span class="spec-val"><span class="priority priority-<?= statusCls($k['status']) ?>"><?= htmlspecialchars($k['status']) ?></span></span>
          </div>
        </div>
      </div>

      <!-- Dokumen -->
      <div class="card">
        <div class="card-head"><span class="card-icon"></span><h2>Dokumen & Pajak</h2></div>
        <div class="card-body">
          <?php if (empty($dokumen)): ?>
            <p class="empty-info">Belum ada data dokumen.</p>
          <?php else: ?>
            <ul class="task-list">
              <?php foreach ($dokumen as $dok):
                $expired = !empty($dok['tanggal_expire']) && strtotime($dok['tanggal_expire']) < time();
              ?>
                <li class="task-item">
                  <div class="task-info">
                    <div class="task-name"><?= htmlspecialchars($dok['jenis_dokumen'] ?? '-') ?></div>
                    <div class="task-meta">No: <?= htmlspecialchars($dok['nomor_dokumen'] ?? '-') ?> · Berlaku hingga: <?= tglIndo($dok['tanggal_expire'] ?? null) ?></div>
                  </div>
                  <span class="task-status <?= $expired ? 'ts-pending' : 'ts-done' ?>"><?= $expired ? 'Kadaluarsa' : 'Valid' ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- /kolom kiri -->

    <!-- ══ Kolom Kanan ══ -->
    <div class="detail-col">

      <!-- Pengemudi -->
      <div class="card">
        <div class="card-head"><span class="card-icon">👤</span><h2>Pengemudi Terakhir</h2></div>
        <div class="card-body">
          <?php if (!$pengemudi): ?>
            <p class="empty-info">Belum ada data pengemudi.</p>
          <?php else: ?>
            <div class="team-item">
              <div class="avatar" style="background:#e0f2fe;color:#0369a1"><?= inisial($pengemudi['nama_pengemudi']) ?></div>
              <div class="member-info">
                <div class="member-name"><?= htmlspecialchars($pengemudi['nama_pengemudi']) ?></div>
                <div class="member-role"><?= htmlspecialchars($pengemudi['jabatan'] ?? '') ?> · Sejak <?= tglIndo($pengemudi['tanggal_mulai'] ?? null) ?></div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Riwayat Servis -->
      <div class="card">
        <div class="card-head">
          <span class="card-icon">🔧</span>
          <h2>Riwayat Servis</h2>
          <button class="btn btn-ghost" style="margin-left:auto;padding:4px 10px;font-size:12px" id="btnTambahServis">+ Tambah</button>
        </div>
        <div class="card-body" id="servisContainer">
          <?php if (empty($servis)): ?>
            <p class="empty-info" id="servisEmpty">Belum ada riwayat servis.</p>
          <?php else: ?>
            <div class="timeline" id="servisList">
              <?php foreach ($servis as $i => $s): ?>
                <div class="tl-item" data-servis-id="<?= $s['id'] ?>">
                  <div class="tl-dot <?= $i === 0 ? 'done' : '' ?>"></div>
                  <div class="tl-content">
                    <div class="tl-date"><?= tglIndo($s['tanggal_servis']) ?></div>
                    <div class="tl-event"><?= htmlspecialchars($s['jenis_servis'] ?? '-') ?></div>
                    <?php if (!empty($s['bengkel'])): ?>
                      <div class="tl-desc"> <?= htmlspecialchars($s['bengkel']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($s['catatan'])): ?>
                      <div class="tl-desc"><?= htmlspecialchars($s['catatan']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($s['biaya']) && (int)$s['biaya'] > 0): ?>
                      <div class="tl-desc"> Rp <?= number_format((int)$s['biaya'], 0, ',', '.') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($s['kilometer']) && (int)$s['kilometer'] > 0): ?>
                      <div class="tl-desc"> <?= number_format((int)$s['kilometer']) ?> km</div>
                    <?php endif; ?>
                    <button class="btn-hapus-servis" onclick="hapusServis(<?= $s['id'] ?>, this)" title="Hapus">✕</button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- /kolom kanan -->
  </div>

  <div id="toast" class="toast" aria-live="polite"></div>
</div>

<!-- ════ MODAL EDIT DATA ════ -->
<div id="modalEdit" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box">
    <div class="modal-head">
      <h2>Edit Kendaraan</h2>
      <button class="modal-close" onclick="tutupModalEdit()">&times;</button>
    </div>
    <form id="formEdit" novalidate>
      <div class="modal-body">
        <div class="form-grid-2">
          <div class="form-group"><label>Plat Nomor <span class="req">*</span></label><input type="text" id="eePlat" value="<?= htmlspecialchars($k['nomor_plat']) ?>" required /></div>
          <div class="form-group"><label>Jenis</label><select id="eeJenis"><?php foreach (['Mobil','Motor','Truk','Bus'] as $j): ?><option value="<?= $j ?>" <?= ($k['jenis'] ?? '') === $j ? 'selected' : '' ?>><?= $j ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>Merk <span class="req">*</span></label><input type="text" id="eeMerk" value="<?= htmlspecialchars($k['merek']) ?>" required /></div>
          <div class="form-group"><label>Model <span class="req">*</span></label><input type="text" id="eeModel" value="<?= htmlspecialchars($k['model']) ?>" required /></div>
          <div class="form-group"><label>Tahun <span class="req">*</span></label><input type="number" id="eeTahun" value="<?= (int)$k['tahun'] ?>" min="1990" max="2030" required /></div>
          <div class="form-group"><label>Warna</label><input type="text" id="eeWarna" value="<?= htmlspecialchars($k['warna'] ?? '') ?>" /></div>
          <div class="form-group"><label>Kilometer</label><input type="number" id="eeKm" value="<?= (int)$k['kilometer'] ?>" min="0" /></div>
          <div class="form-group"><label>Bahan Bakar</label><select id="eeBbm"><?php foreach (['Bensin','Solar','Listrik'] as $b): ?><option value="<?= $b ?>" <?= ($k['bahan_bakar'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>Lokasi</label><input type="text" id="eeLokasi" value="<?= htmlspecialchars($k['lokasi'] ?? '') ?>" /></div>
          <div class="form-group"><label>Status</label><select id="eeStatus"><?php foreach (['Tersedia','Dipakai','Servis'] as $st): ?><option value="<?= $st ?>" <?= $k['status'] === $st ? 'selected' : '' ?>><?= $st ?></option><?php endforeach; ?></select></div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="tutupModalEdit()">Batal</button>
        <button type="submit" class="btn btn-primary" id="btnSimpanEdit">Perbarui</button>
      </div>
    </form>
  </div>
</div>

<!-- ════ MODAL UPDATE STATUS ════ -->
<div id="modalStatus" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box modal-box-sm">
    <div class="modal-head">
      <h2>Update Status</h2>
      <button class="modal-close" onclick="tutupModalStatus()">&times;</button>
    </div>
    <div class="modal-body">
      <p class="status-current-label">Status saat ini: <span class="priority priority-<?= statusCls($k['status']) ?>"><?= htmlspecialchars($k['status']) ?></span></p>
      <div class="status-options">
        <?php foreach (['Tersedia','Dipakai','Servis'] as $st): ?>
          <button type="button" class="status-option-btn <?= $k['status'] === $st ? 'status-option-active' : '' ?>" data-status="<?= $st ?>">
            <span class="status-dot status-dot-<?= statusCls($st) ?>"></span><?= $st ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn btn-ghost" onclick="tutupModalStatus()">Batal</button>
      <button type="button" class="btn btn-primary" id="btnSimpanStatus">Simpan</button>
    </div>
  </div>
</div>

<!-- ════ MODAL TAMBAH SERVIS ════ -->
<div id="modalServis" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-box">
    <div class="modal-head">
      <h2>Tambah Riwayat Servis</h2>
      <button class="modal-close" onclick="tutupModalServis()">&times;</button>
    </div>
    <form id="formServis" novalidate>
      <div class="modal-body">
        <div class="form-grid-2">
          <div class="form-group">
            <label>Tanggal Servis <span class="req">*</span></label>
            <input type="date" id="ssTanggal" required />
          </div>
          <div class="form-group">
            <label>Jenis Servis <span class="req">*</span></label>
            <select id="ssJenis">
              <option value="Servis Rutin">Servis Rutin</option>
              <option value="Ganti Oli">Ganti Oli</option>
              <option value="Ganti Ban">Ganti Ban</option>
              <option value="Perbaikan Mesin">Perbaikan Mesin</option>
              <option value="Servis AC">Servis AC</option>
              <option value="Tune Up">Tune Up</option>
              <option value="Ganti Aki">Ganti Aki</option>
              <option value="Servis Rem">Servis Rem</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>
          <div class="form-group">
            <label>Nama Bengkel</label>
            <input type="text" id="ssBengkel" placeholder="Auto 2000 Fatmawati" />
          </div>
          <div class="form-group">
            <label>Kilometer Saat Servis</label>
            <input type="number" id="ssKilometer" placeholder="<?= (int)$k['kilometer'] ?>" min="0" />
          </div>
          <div class="form-group">
            <label>Biaya (Rp)</label>
            <input type="number" id="ssBiaya" placeholder="0" min="0" />
          </div>
          <div class="form-group" style="grid-column:span 2">
            <label>Catatan</label>
            <input type="text" id="ssCatatan" placeholder="Ganti oli mesin + filter udara" />
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" onclick="tutupModalServis()">Batal</button>
        <button type="submit" class="btn btn-primary" id="btnSimpanServis">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<style>
  .btn-hapus-servis {
    margin-top: 6px;
    background: none;
    border: none;
    color: var(--text-dim);
    cursor: pointer;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 4px;
    transition: all 0.15s;
  }
  .btn-hapus-servis:hover { background: var(--high-bg); color: var(--high); }
  .foto-kendaraan { width:100%; height:220px; object-fit:cover; border-radius:var(--radius); border:1px solid var(--border); display:block; }
  .foto-placeholder { width:100%; height:220px; background:var(--surface-2); border:2px dashed var(--border); border-radius:var(--radius); display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--text-dim); font-size:13px; font-family:var(--font-mono); gap:8px; }
  .foto-placeholder span { font-size:36px; }
  .spec-list { display:flex; flex-direction:column; gap:0; }
  .spec-row { display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid var(--border); font-size:14px; }
  .spec-row:last-child { border-bottom:none; }
  .spec-key { color:var(--text-muted); font-size:13px; }
  .spec-val { font-weight:500; color:var(--text); text-align:right; }
  .empty-info { color:var(--text-muted); font-size:13px; font-family:var(--font-mono); }
  .status-current-label { font-size:14px; color:var(--text-muted); margin-bottom:16px; display:flex; align-items:center; gap:8px; }
  .status-options { display:flex; flex-direction:column; gap:8px; }
  .status-option-btn { display:flex; align-items:center; gap:10px; padding:12px 16px; border:1px solid var(--border); border-radius:var(--radius-sm); background:var(--surface); cursor:pointer; font-size:14px; font-weight:500; color:var(--text); transition:all 0.15s; }
  .status-option-btn:hover { background:var(--surface-2); border-color:var(--border-light); }
  .status-option-active { border-color:var(--accent); background:var(--accent-glow); color:var(--accent); }
  .status-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
  .status-dot-low    { background:var(--low); }
  .status-dot-accent { background:var(--accent); }
  .status-dot-med    { background:var(--med); }
</style>

<script>
const API        = '<?= BASE_URL ?>/api/kendaraan.php';
const SERVIS_API = '<?= BASE_URL ?>/api/servis.php';
const UPLOAD_API = '<?= BASE_URL ?>/api/upload_foto.php';
const KID        = <?= (int)$k['id'] ?>;

function showToast(msg, type = 'success') {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className = 'toast toast-' + type + ' toast-show';
  setTimeout(() => el.classList.remove('toast-show'), 3500);
}

/* ── Upload Foto ── */
let fotoFile = null;
document.getElementById('inputFoto').addEventListener('change', function() {
  if (!this.files || !this.files[0]) return;
  fotoFile = this.files[0];
  if (fotoFile.size > 3 * 1024 * 1024) { showToast('Maksimal 3MB.', 'error'); fotoFile = null; return; }
  const reader = new FileReader();
  reader.onload = e => {
    const preview = document.getElementById('fotoPreview');
    const ph = document.getElementById('fotoPlaceholder');
    preview.src = e.target.result; preview.style.display = 'block';
    if (ph) ph.style.display = 'none';
  };
  reader.readAsDataURL(fotoFile);
  document.getElementById('uploadInfo').textContent = ` ${fotoFile.name} (${(fotoFile.size/1024).toFixed(0)} KB)`;
  document.getElementById('btnUploadFoto').style.display = 'flex';
});

async function uploadFoto() {
  if (!fotoFile) return;
  const btn = document.getElementById('btnUploadFoto');
  btn.disabled = true; btn.textContent = 'Mengupload…';
  const fd = new FormData();
  fd.append('kendaraan_id', KID); fd.append('foto', fotoFile);
  try {
    const res = await fetch(UPLOAD_API, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      showToast('Foto berhasil diupload!');
      document.getElementById('btnUploadFoto').style.display = 'none';
      document.getElementById('uploadInfo').textContent = '✅ Foto tersimpan';
      fotoFile = null;
    } else { showToast(json.message || 'Gagal upload.', 'error'); }
  } catch { showToast('Koneksi gagal.', 'error'); }
  finally { btn.disabled = false; btn.textContent = '⬆️ Upload'; }
}

/* ── Modal Edit ── */
document.getElementById('btnEdit').addEventListener('click', () => { document.getElementById('modalEdit').classList.add('modal-active'); });
function tutupModalEdit() { document.getElementById('modalEdit').classList.remove('modal-active'); }
document.getElementById('modalEdit').addEventListener('click', function(e) { if (e.target === this) tutupModalEdit(); });

document.getElementById('formEdit').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSimpanEdit');
  btn.disabled = true; btn.textContent = 'Menyimpan…';
  const payload = {
    action: 'edit', id: KID,
    nomor_plat: document.getElementById('eePlat').value.trim(),
    merek: document.getElementById('eeMerk').value.trim(),
    model: document.getElementById('eeModel').value.trim(),
    tahun: Number(document.getElementById('eeTahun').value),
    warna: document.getElementById('eeWarna').value.trim(),
    jenis: document.getElementById('eeJenis').value,
    kilometer: Number(document.getElementById('eeKm').value) || 0,
    bahan_bakar: document.getElementById('eeBbm').value,
    lokasi: document.getElementById('eeLokasi').value.trim(),
    status: document.getElementById('eeStatus').value,
  };
  if (!payload.nomor_plat || !payload.merek || !payload.model || !payload.tahun) {
    showToast('Field wajib tidak boleh kosong.', 'error'); btn.disabled = false; btn.textContent = 'Perbarui'; return;
  }
  try {
    const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const json = await res.json();
    if (json.success) { showToast(json.message); tutupModalEdit(); setTimeout(() => location.reload(), 900); }
    else showToast(json.message || 'Gagal.', 'error');
  } catch { showToast('Koneksi gagal.', 'error'); }
  finally { btn.disabled = false; btn.textContent = 'Perbarui'; }
});

/* ── Modal Update Status ── */
let _statusTerpilih = '<?= $k['status'] ?>';
document.getElementById('btnUpdateStatus').addEventListener('click', () => { document.getElementById('modalStatus').classList.add('modal-active'); });
function tutupModalStatus() { document.getElementById('modalStatus').classList.remove('modal-active'); }
document.getElementById('modalStatus').addEventListener('click', function(e) { if (e.target === this) tutupModalStatus(); });
document.querySelectorAll('.status-option-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.status-option-btn').forEach(b => b.classList.remove('status-option-active'));
    this.classList.add('status-option-active');
    _statusTerpilih = this.dataset.status;
  });
});
document.getElementById('btnSimpanStatus').addEventListener('click', async () => {
  const btn = document.getElementById('btnSimpanStatus');
  btn.disabled = true; btn.textContent = 'Menyimpan…';
  const payload = {
    action: 'edit', id: KID,
    nomor_plat: <?= json_encode($k['nomor_plat']) ?>, merek: <?= json_encode($k['merek']) ?>,
    model: <?= json_encode($k['model']) ?>, tahun: <?= (int)$k['tahun'] ?>,
    warna: <?= json_encode($k['warna'] ?? '') ?>, jenis: <?= json_encode($k['jenis'] ?? '') ?>,
    kilometer: <?= (int)$k['kilometer'] ?>, bahan_bakar: <?= json_encode($k['bahan_bakar'] ?? '') ?>,
    lokasi: <?= json_encode($k['lokasi'] ?? '') ?>, status: _statusTerpilih,
  };
  try {
    const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const json = await res.json();
    if (json.success) { showToast('Status diperbarui!'); tutupModalStatus(); setTimeout(() => location.reload(), 900); }
    else showToast(json.message || 'Gagal.', 'error');
  } catch { showToast('Koneksi gagal.', 'error'); }
  finally { btn.disabled = false; btn.textContent = 'Simpan'; }
});

/* ── Modal Tambah Servis ── */
document.getElementById('btnTambahServis').addEventListener('click', () => {
  document.getElementById('ssTanggal').value = new Date().toISOString().split('T')[0];
  document.getElementById('modalServis').classList.add('modal-active');
});
function tutupModalServis() { document.getElementById('modalServis').classList.remove('modal-active'); document.getElementById('formServis').reset(); }
document.getElementById('modalServis').addEventListener('click', function(e) { if (e.target === this) tutupModalServis(); });

document.getElementById('formServis').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSimpanServis');
  btn.disabled = true; btn.textContent = 'Menyimpan…';

  const payload = {
    action:        'tambah',
    kendaraan_id:  KID,
    tanggal_servis: document.getElementById('ssTanggal').value,
    jenis_servis:  document.getElementById('ssJenis').value,
    bengkel:       document.getElementById('ssBengkel').value.trim(),
    kilometer:     Number(document.getElementById('ssKilometer').value) || 0,
    biaya:         Number(document.getElementById('ssBiaya').value) || 0,
    catatan:       document.getElementById('ssCatatan').value.trim(),
  };

  if (!payload.tanggal_servis || !payload.jenis_servis) {
    showToast('Tanggal dan jenis servis wajib diisi.', 'error'); btn.disabled = false; btn.textContent = 'Simpan'; return;
  }

  try {
    const res  = await fetch(SERVIS_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const json = await res.json();
    if (json.success) {
      showToast('Riwayat servis ditambahkan!');
      tutupModalServis();
      setTimeout(() => location.reload(), 900);
    } else { showToast(json.message || 'Gagal.', 'error'); }
  } catch { showToast('Koneksi gagal.', 'error'); }
  finally { btn.disabled = false; btn.textContent = 'Simpan'; }
});

/* ── Hapus Servis ── */
async function hapusServis(id, btnEl) {
  if (!confirm('Hapus data servis ini?')) return;
  try {
    const res  = await fetch(SERVIS_API, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'hapus', id }) });
    const json = await res.json();
    if (json.success) {
      showToast('Data servis dihapus.');
      btnEl.closest('.tl-item').remove();
      if (!document.querySelector('.tl-item')) {
        document.getElementById('servisContainer').innerHTML = '<p class="empty-info">Belum ada riwayat servis.</p>';
      }
    } else { showToast(json.message || 'Gagal.', 'error'); }
  } catch { showToast('Koneksi gagal.', 'error'); }
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { tutupModalEdit(); tutupModalStatus(); tutupModalServis(); }
});
</script>