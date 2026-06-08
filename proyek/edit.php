<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$title = 'Edit Proyek';
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM proyek WHERE id = ? AND entity_id = ?");
$stmt->execute([$id, $_SESSION['entity_id']]);
$proyek = $stmt->fetch();
if (!$proyek) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM entity WHERE id = ?");
$stmt->execute([$_SESSION['entity_id']]);
$entity = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM klien WHERE entity_id = ? ORDER BY nama_perusahaan ASC");
$stmt->execute([$_SESSION['entity_id']]);
$klien_list = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM barang WHERE entity_id = ? ORDER BY nama ASC");
$stmt->execute([$_SESSION['entity_id']]);
$barang_list = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM proyek_item WHERE proyek_id = ? ORDER BY no_urut");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$has_barang = count(array_filter($items, fn($i) => $i['kategori'] === 'barang')) > 0;
$has_jasa = count(array_filter($items, fn($i) => $i['kategori'] === 'pekerjaan')) > 0;

$stmt = $pdo->prepare("SELECT * FROM proyek_tahap_pembayaran WHERE proyek_id = ? ORDER BY urutan");
$stmt->execute([$id]);
$tahap_pembayaran = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $klien_id = $_POST['klien_id'];
    $tanggal = $_POST['tanggal'] ?: date('Y-m-d');
    $berlaku_sampai = $_POST['berlaku_sampai'] ?: date('Y-m-d', strtotime('+1 month'));
    $diskon_persen = (float)($_POST['diskon_persen'] ?? 0);
    $ppn_persen = (float)($_POST['ppn_persen'] ?? ($entity['kena_ppn'] ? 11 : 0));
    $waktu_pelaksanaan_hari = (int)($_POST['waktu_pelaksanaan_hari'] ?? 7);
    $new_items = $_POST['items'] ?? [];

    $sub_total = 0;
    foreach ($new_items as $item) {
        $sub_total += (float)($item['harga'] ?? 0) * (float)($item['qty'] ?? 0);
    }
    $diskon_nominal = $sub_total * ($diskon_persen / 100);
    $dpp = $sub_total - $diskon_nominal;
    $ppn_nominal = $dpp * ($ppn_persen / 100);
    $grand_total = $dpp + $ppn_nominal;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            UPDATE proyek SET klien_id=?, tanggal=?, berlaku_sampai=?, diskon_persen=?, ppn_persen=?,
                sub_total=?, dpp=?, grand_total=?, waktu_pelaksanaan_hari=?
            WHERE id=? AND entity_id=?
        ");
        $stmt->execute([$klien_id, $tanggal, $berlaku_sampai, $diskon_persen, $ppn_persen,
            $sub_total, $dpp, $grand_total, $waktu_pelaksanaan_hari, $id, $_SESSION['entity_id']]);

        $pdo->prepare("DELETE FROM proyek_item WHERE proyek_id = ?")->execute([$id]);

        foreach ($new_items as $i => $item) {
            $barang_id = $item['barang_id'] ?: null;
            $barang_custom = trim($item['barang_custom'] ?? '');
            if (!$barang_id && $barang_custom) {
                $stmt = $pdo->prepare("INSERT INTO barang (entity_id, nama) VALUES (?, ?)");
                $stmt->execute([$_SESSION['entity_id'], $barang_custom]);
                $barang_id = $pdo->lastInsertId();
            }
            $stmt = $pdo->prepare("
                INSERT INTO proyek_item (proyek_id, no_urut, barang_id, kategori, keterangan, harga, qty)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id, $i+1, $barang_id, $item['kategori'] ?? 'barang',
                $item['keterangan'] ?? '', (float)($item['harga'] ?? 0), (float)($item['qty'] ?? 1)]);
        }

        // Save payment stages
        $tahap_json = $_POST['tahap'] ?? '[]';
        $tahap = json_decode($tahap_json, true);
        if (is_array($tahap)) {
            $pdo->prepare("DELETE FROM proyek_tahap_pembayaran WHERE proyek_id = ?")->execute([$id]);
            $ins = $pdo->prepare("INSERT INTO proyek_tahap_pembayaran (proyek_id, urutan, persentase, deskripsi) VALUES (?, ?, ?, ?)");
            $urutan = 1;
            foreach ($tahap as $t) {
                $p = str_replace(',', '.', $t['persentase'] ?? '0');
                $p = (float)$p;
                $d = trim($t['deskripsi'] ?? '');
                if ($p <= 0 || !$d) continue;
                $ins->execute([$id, $urutan++, $p, $d]);
            }
        }

        $pdo->commit();
        header("Location: detail.php?id=$id&msg=updated");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Gagal update: " . $e->getMessage();
    }
}

require '../template/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0">Edit Proyek #<?= htmlspecialchars($proyek['no_referensi']) ?></h5>
  <a href="detail.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<?php if (isset($error)): ?><div class="alert alert-danger py-2 small"><?= $error ?></div><?php endif; ?>

<form method="post" id="proyekForm" novalidate>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent py-2">
          <h6 class="fw-bold mb-0">Data Klien</h6>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label small">Klien</label>
              <select name="klien_id" class="form-select form-select-sm" required>
                <option value="">-- Pilih --</option>
                <?php foreach ($klien_list as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $k['id'] == $proyek['klien_id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_perusahaan']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small">Tanggal</label>
              <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= $proyek['tanggal'] ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label small">Berlaku Sampai</label>
              <input type="date" name="berlaku_sampai" class="form-control form-control-sm" value="<?= $proyek['berlaku_sampai'] ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent py-2 d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0">Item Barang / Jasa</h6>
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="tambahItem()">
            <i class="bi bi-plus-lg"></i> Tambah Item
          </button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm mb-0" id="itemsTable">
              <thead>
                <tr>
                  <th>#</th><th>Nama Barang</th><th>Kategori</th><th>Keterangan</th>
                  <th style="width:130px">Harga</th><th style="width:70px">Qty</th>
                  <th style="width:130px">Jumlah</th>
                  <th style="width:40px"></th>
                </tr>
              </thead>
              <tbody id="itemsBody"></tbody>
              <tfoot class="table-light">
                <tr>
                  <td colspan="5"></td>
                  <td class="fw-medium">Sub Total</td>
                  <td class="fw-bold" id="subTotalDisplay">Rp 0</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- Tahap Pembayaran -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent py-2">
          <h6 class="fw-bold mb-0">Tahap Pembayaran</h6>
        </div>
        <div class="card-body">
          <p class="small text-muted">Persentase dari Grand Total. Sisanya akan terlihat di bawah.</p>
          <div id="tahapContainer">
            <table class="table table-sm" id="tahapTable">
              <thead><tr><th style="width:30px">#</th><th style="width:60px">%</th><th>Deskripsi</th><th style="width:100px">Nominal</th><th style="width:30px"></th></tr></thead>
              <tbody id="tahapBody"></tbody>
            </table>
            <div id="sisaContainer" class="small mb-2"></div>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="tambahBarisTahap()" id="btnTambahTahap">
              <i class="bi bi-plus-lg"></i> Tambah Tahap
            </button>
            <style>
            #btnTambahTahap:disabled { opacity: 0.35; border-color: #6c757d; color: #6c757d; background: #e9ecef; pointer-events: none; }
            </style>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent py-2">
          <h6 class="fw-bold mb-0">Diskon & PPN</h6>
        </div>
        <div class="card-body">
          <div class="mb-2">
            <label class="form-label small">Diskon (%)</label>
            <input type="number" name="diskon_persen" id="diskonPersen" class="form-control form-control-sm" value="<?= $proyek['diskon_persen'] ?>" min="0" max="100" step="0.01" oninput="hitungTotal()">
          </div>
          <div class="mb-2">
            <label class="form-label small">PPN (%)</label>
            <input type="number" name="ppn_persen" id="ppnPersen" class="form-control form-control-sm" value="<?= $proyek['ppn_persen'] ?>" min="0" max="100" step="0.01" oninput="hitungTotal()">
          </div>
        </div>
      </div>

      <!-- Waktu Pelaksanaan -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent py-2">
          <h6 class="fw-bold mb-0">Waktu Pelaksanaan</h6>
        </div>
        <div class="card-body">
          <div class="mb-2">
            <label class="form-label small">Lama Pelaksanaan (hari)</label>
            <input type="number" name="waktu_pelaksanaan_hari" class="form-control form-control-sm" value="<?= (int)($proyek['waktu_pelaksanaan_hari'] ?: 7) ?>" min="1" max="365">
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent py-2">
          <h6 class="fw-bold mb-0">Ringkasan</h6>
        </div>
        <div class="card-body">
          <table class="table table-sm mb-0">
            <tr><td>Sub Total</td><td class="fw-bold text-end" id="subTotalSummary">Rp 0</td></tr>
            <tr><td>Diskon <span id="diskonLabel">(0%)</span></td><td class="text-end text-danger" id="diskonSummary">- Rp 0</td></tr>
            <tr><td>DPP</td><td class="fw-bold text-end" id="dppSummary">Rp 0</td></tr>
            <tr><td>PPN <span id="ppnLabel">(0%)</span></td><td class="text-end" id="ppnSummary">Rp 0</td></tr>
            <tr class="table-primary">
              <td class="fw-bold">Grand Total</td>
              <td class="fw-bold text-end fs-5" id="grandTotalSummary">Rp 0</td>
            </tr>
          </table>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2 fw-medium">
        <i class="bi bi-save me-1"></i>Update Proyek
      </button>
    </div>
  </div>
</form>

<script>
const barangList = <?= json_encode($barang_list) ?>;
let itemCount = 0;

function tambahItem(data) {
  itemCount++;
  const no = itemCount;
  const tbody = document.getElementById('itemsBody');
  const barangOpts = barangList.map(b => `<option value="${b.id}" ${data && data.barang_id == b.id ? 'selected' : ''}>${b.nama}</option>`).join('');

  const row = document.createElement('tr');
  row.id = `item-${no}`;
  row.innerHTML = `
    <td>${no}</td>
    <td>
      <select name="items[${no}][barang_id]" class="form-select form-select-sm" onchange="itemBarangChange(this, ${no})">
        <option value="">-- Custom --</option>
        ${barangOpts}
      </select>
      <input type="text" name="items[${no}][barang_custom]" id="barangCustom-${no}" class="form-control form-control-sm mt-1" placeholder="Nama barang custom" style="display:${data?.barang_id ? 'none' : 'block'}">
    </td>
    <td><select name="items[${no}][kategori]" class="form-select form-select-sm">
      <option value="barang" ${data?.kategori === 'barang' || !data ? 'selected' : ''}>Barang</option>
      <option value="pekerjaan" ${data?.kategori === 'pekerjaan' ? 'selected' : ''}>Jasa</option>
    </select></td>
    <td><input type="text" name="items[${no}][keterangan]" class="form-control form-control-sm" value="${data?.keterangan || ''}"></td>
    <td><input type="number" name="items[${no}][harga]" class="form-control form-control-sm" step="0.01" min="0" oninput="hitungItem(${no})" value="${data?.harga || 0}"></td>
    <td><input type="number" name="items[${no}][qty]" class="form-control form-control-sm" min="0" step="0.01" oninput="hitungItem(${no})" value="${data?.qty || 1}"></td>
    <td id="jumlah-${no}">Rp 0</td>
    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusItem(${no})"><i class="bi bi-x"></i></button></td>
  `;
  tbody.appendChild(row);
  hitungItem(no);
}

function itemBarangChange(sel, no) {
  const ci = document.getElementById(`barangCustom-${no}`);
  ci.style.display = sel.value ? 'none' : 'block';
  if (!sel.value) ci.focus();
}

function hapusItem(no) {
  const row = document.getElementById(`item-${no}`);
  if (row) row.remove();
  hitungTotal();
}

function hitungItem(no) {
  const row = document.getElementById(`item-${no}`);
  if (!row) return;
  const harga = parseFloat(row.querySelector(`[name="items[${no}][harga]"]`).value) || 0;
  const qty = parseFloat(row.querySelector(`[name="items[${no}][qty]"]`).value) || 0;
  document.getElementById(`jumlah-${no}`).textContent = formatRp(harga * qty);
  hitungTotal();
}

function hitungTotal() {
  let subTotal = 0;
  document.querySelectorAll('[id^="item-"]').forEach(row => {
    const id = row.id.replace('item-', '');
    const harga = parseFloat(row.querySelector(`[name="items[${id}][harga]"]`).value) || 0;
    const qty = parseFloat(row.querySelector(`[name="items[${id}][qty]"]`).value) || 0;
    subTotal += harga * qty;
  });
  const dp = parseFloat(document.getElementById('diskonPersen').value) || 0;
  const pp = parseFloat(document.getElementById('ppnPersen').value) || 0;
  const dn = subTotal * (dp / 100);
  const dpp = subTotal - dn;
  const pn = dpp * (pp / 100);
  const gt = dpp + pn;
  document.getElementById('subTotalDisplay').textContent = formatRp(subTotal);
  document.getElementById('subTotalSummary').textContent = formatRp(subTotal);
  document.getElementById('diskonLabel').textContent = `(${dp}%)`;
  document.getElementById('diskonSummary').textContent = `- ${formatRp(dn)}`;
  document.getElementById('dppSummary').textContent = formatRp(dpp);
  document.getElementById('ppnLabel').textContent = `(${pp}%)`;
  document.getElementById('ppnSummary').textContent = formatRp(pn);
  document.getElementById('grandTotalSummary').textContent = formatRp(gt);
  grandTotal = gt;
  refreshTahapNominal();
}

function formatRp(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

// Tahap Pembayaran
let tahapData = <?= json_encode($tahap_pembayaran) ?>;
let hasBarang = <?= $has_barang ? 'true' : 'false' ?>;
let hasJasa = <?= $has_jasa ? 'true' : 'false' ?>;
let grandTotal = <?= $proyek['grand_total'] ?>;

function initTahap() {
  // For the form submit: serialize tahapData into hidden input
  const form = document.getElementById('proyekForm');
  form.addEventListener('submit', function() {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'tahap';
    input.value = JSON.stringify(tahapData);
    form.appendChild(input);
  });
  renderTahap();
}

function renderTahap() {
  const tbody = document.getElementById('tahapBody');
  if (!tbody) return;
  tbody.innerHTML = '';
  if (!tahapData.length) {
    tahapData = [];
    if (hasBarang) {
      tahapData.push({ persentase: 50, deskripsi: 'DP Barang — Dibayar sebelum produksi' });
      tahapData.push({ persentase: 50, deskripsi: 'Pelunasan Barang — Setelah produksi & opname' });
    }
    if (hasJasa) {
      tahapData.push({ persentase: 100, deskripsi: 'Pelunasan Jasa — Setelah jasa 100%' });
    }
  }
  tahapData.forEach((t, i) => {
    const no = i + 1;
    const nominal = (t.persentase / 100) * grandTotal;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="text-center align-middle fw-medium">${no}</td>
      <td><input type="number" class="form-control form-control-sm" value="${t.persentase}" min="0" max="100" step="0.1" oninput="ubahPersen(${i}, this)"></td>
      <td><input type="text" class="form-control form-control-sm" value="${t.deskripsi ? htmlEscape(t.deskripsi) : ''}" onchange="tahapData[${i}].deskripsi=this.value" placeholder="Deskripsi tahap"></td>
      <td class="text-end align-middle"><small id="nominal-${i}">${formatRp(nominal)}</small></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBarisTahap(${i})"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(tr);
  });
  updateSisa();
}

function ubahPersen(idx, el) {
  const val = parseFloat(el.value) || 0;
  tahapData[idx].persentase = val;
  document.getElementById('nominal-' + idx).textContent = formatRp((val / 100) * grandTotal);
  updateSisa();
}

function updateSisa() {
  const container = document.getElementById('sisaContainer');
  if (!container) return;
  const totalPct = tahapData.reduce((sum, t) => sum + (parseFloat(t.persentase) || 0), 0);
  const sisa = Math.max(0, 100 - totalPct);
  container.innerHTML = `<div class="d-flex justify-content-between border-top pt-1 mt-1 fw-bold ${sisa > 0 ? 'text-danger' : 'text-success'}"><span>Sisa:</span><span>${sisa}% (${formatRp((sisa/100)*grandTotal)})</span></div>`;
  document.getElementById('btnTambahTahap').disabled = (sisa <= 0);
}

function refreshTahapNominal() {
  tahapData.forEach((t, i) => {
    const el = document.getElementById('nominal-' + i);
    if (el) el.textContent = formatRp((t.persentase / 100) * grandTotal);
  });
  const totalPct = tahapData.reduce((sum, t) => sum + (parseFloat(t.persentase) || 0), 0);
  const sisa = Math.max(0, 100 - totalPct);
  const container = document.getElementById('sisaContainer');
  if (container) {
    container.innerHTML = `<div class="d-flex justify-content-between border-top pt-1 mt-1 fw-bold ${sisa > 0 ? 'text-danger' : 'text-success'}"><span>Sisa:</span><span>${sisa}% (${formatRp((sisa/100)*grandTotal)})</span></div>`;
  }
  document.getElementById('btnTambahTahap').disabled = (sisa <= 0);
}

function tambahBarisTahap() {
  const totalPct = tahapData.reduce((sum, t) => sum + (parseFloat(t.persentase) || 0), 0);
  if (totalPct >= 100) return;
  tahapData.push({ persentase: 0, deskripsi: '' });
  renderTahap();
}

function hapusBarisTahap(idx) {
  tahapData.splice(idx, 1);
  renderTahap();
}

function htmlEscape(s) {
  if (typeof s !== 'string') return '';
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Load existing items
<?php foreach ($items as $item): ?>
tambahItem(<?= json_encode($item) ?>);
<?php endforeach; ?>
if (itemCount === 0) tambahItem();

initTahap();
</script>

<?php require '../template/footer.php'; ?>
