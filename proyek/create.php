<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$title = 'Buat Proyek Baru';

// Get entity data for PPN default
$stmt = $pdo->prepare("SELECT * FROM entity WHERE id = ?");
$stmt->execute([$_SESSION['entity_id']]);
$entity = $stmt->fetch();

// Get klien list
$stmt = $pdo->prepare("SELECT * FROM klien WHERE entity_id = ? ORDER BY nama_perusahaan ASC");
$stmt->execute([$_SESSION['entity_id']]);
$klien_list = $stmt->fetchAll();

// Get barang list
$stmt = $pdo->prepare("SELECT * FROM barang WHERE entity_id = ? ORDER BY nama ASC");
$stmt->execute([$_SESSION['entity_id']]);
$barang_list = $stmt->fetchAll();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_referensi = generateNoReferensi();
    $klien_id = $_POST['klien_id'];
    $tanggal = $_POST['tanggal'] ?: date('Y-m-d');
    $berlaku_sampai = $_POST['berlaku_sampai'] ?: date('Y-m-d', strtotime('+1 month'));
    $diskon_persen = (float)($_POST['diskon_persen'] ?? 0);
    $ppn_persen = (float)($_POST['ppn_persen'] ?? ($entity['kena_ppn'] ? 11 : 0));
    $waktu_pelaksanaan_hari = (int)($_POST['waktu_pelaksanaan_hari'] ?? 7);
    $items = $_POST['items'] ?? [];

    $sub_total = 0;
    foreach ($items as $item) {
        $sub_total += (float)($item['harga'] ?? 0) * (float)($item['qty'] ?? 0);
    }

    $diskon_nominal = $sub_total * ($diskon_persen / 100);
    $dpp = $sub_total - $diskon_nominal;
    $ppn_nominal = $dpp * ($ppn_persen / 100);
    $grand_total = $dpp + $ppn_nominal;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO proyek (entity_id, no_referensi, klien_id, tanggal, berlaku_sampai,
                diskon_persen, ppn_persen, sub_total, dpp, grand_total, waktu_pelaksanaan_hari, dibuat_oleh)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['entity_id'], $no_referensi, $klien_id,
            $tanggal, $berlaku_sampai,
            $diskon_persen, $ppn_persen, $sub_total, $dpp, $grand_total, $waktu_pelaksanaan_hari,
            $_SESSION['user_id']
        ]);
        $proyek_id = $pdo->lastInsertId();

        foreach ($items as $i => $item) {
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
            $stmt->execute([
                $proyek_id, $i + 1,
                $barang_id,
                $item['kategori'] ?? 'barang',
                $item['keterangan'] ?? '',
                (float)($item['harga'] ?? 0),
                (float)($item['qty'] ?? 1)
            ]);
        }

        $pdo->commit();
        header("Location: detail.php?id=$proyek_id&msg=created");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Gagal menyimpan: " . $e->getMessage();
    }
}

require '../template/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0">Buat Proyek Baru</h5>
  <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger py-2 small"><?= $error ?></div>
<?php endif; ?>

<form method="post" id="proyekForm" class="needs-validation" novalidate>
  <div class="row g-3">
    <div class="col-lg-8">
      <!-- Data Klien -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent py-2">
          <h6 class="fw-bold mb-0">Data Klien</h6>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label small">Klien</label>
              <select name="klien_id" class="form-select form-select-sm" id="klienSelect" required>
                <option value="">-- Pilih Klien --</option>
                <?php foreach ($klien_list as $k): ?>
                <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_perusahaan']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#klienBaruModal">
                <i class="bi bi-plus-circle"></i> Klien Baru
              </button>
            </div>
            <div class="col-md-3">
              <label class="form-label small">Tanggal</label>
              <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label small">Berlaku Sampai</label>
              <input type="date" name="berlaku_sampai" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+1 month')) ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Items -->
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
                  <th style="width:30px">#</th>
                  <th>Nama Barang</th>
                  <th>Kategori</th>
                  <th>Keterangan</th>
                  <th style="width:130px">Harga</th>
                  <th style="width:70px">Qty</th>
                  <th style="width:130px">Jumlah</th>
                  <th style="width:40px"></th>
                </tr>
              </thead>
              <tbody id="itemsBody">
                <tr id="noItemRow">
                  <td colspan="8" class="text-center text-muted py-3">Belum ada item. Klik "Tambah Item" untuk mulai.</td>
                </tr>
              </tbody>
              <tfoot id="totalsFoot" class="table-light d-none">
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
    </div>

    <div class="col-lg-4">
      <!-- Diskon & PPN -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent py-2">
          <h6 class="fw-bold mb-0">Diskon & PPN</h6>
        </div>
        <div class="card-body">
          <div class="mb-2">
            <label class="form-label small">Diskon (%)</label>
            <input type="number" name="diskon_persen" id="diskonPersen" class="form-control form-control-sm" value="0" min="0" max="100" step="0.01" oninput="hitungTotal()">
          </div>
          <div class="mb-2">
            <label class="form-label small">PPN (%)</label>
            <input type="number" name="ppn_persen" id="ppnPersen" class="form-control form-control-sm" value="<?= $entity['kena_ppn'] ? 11 : 0 ?>" min="0" max="100" step="0.01" oninput="hitungTotal()">
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
            <input type="number" name="waktu_pelaksanaan_hari" class="form-control form-control-sm" value="7" min="1" max="365">
          </div>
        </div>
      </div>

      <!-- Kalkulasi -->
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
        <i class="bi bi-save me-1"></i>Simpan Proyek
      </button>
    </div>
  </div>
</form>

<!-- Modal: Klien Baru -->
<div class="modal fade" id="klienBaruModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold">Klien Baru</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label small">Nama Perusahaan</label>
          <input type="text" id="klienBaruNama" class="form-control form-control-sm">
        </div>
        <div class="mb-2">
          <label class="form-label small">Alamat</label>
          <textarea id="klienBaruAlamat" class="form-control form-control-sm" rows="2"></textarea>
        </div>
        <div class="row g-2">
          <div class="col">
            <label class="form-label small">PIC</label>
            <input type="text" id="klienBaruPic" class="form-control form-control-sm">
          </div>
          <div class="col">
            <label class="form-label small">No. Telp</label>
            <input type="text" id="klienBaruTelp" class="form-control form-control-sm">
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label small">NPWP</label>
          <input type="text" id="klienBaruNpwp" class="form-control form-control-sm">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-primary" onclick="simpanKlienBaru()">Simpan & Pilih</button>
      </div>
    </div>
  </div>
</div>

<script>
const barangList = <?= json_encode($barang_list) ?>;
let itemCount = 0;

function tambahItem(data) {
  itemCount++;
  const no = itemCount;
  const tbody = document.getElementById('itemsBody');
  const noRow = document.getElementById('noItemRow');
  if (noRow) noRow.remove();

  const barangOpts = barangList.map(b =>
    `<option value="${b.id}">${b.nama}</option>`
  ).join('');

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
    <td>
      <select name="items[${no}][kategori]" class="form-select form-select-sm">
        <option value="barang" ${data?.kategori === 'barang' || !data ? 'selected' : ''}>Barang</option>
        <option value="pekerjaan" ${data?.kategori === 'pekerjaan' ? 'selected' : ''}>Jasa</option>
      </select>
    </td>
    <td><input type="text" name="items[${no}][keterangan]" class="form-control form-control-sm" value="${data?.keterangan || ''}"></td>
    <td><input type="number" name="items[${no}][harga]" class="form-control form-control-sm" step="0.01" min="0" oninput="hitungItem(${no})" value="${data?.harga || 0}"></td>
    <td><input type="number" name="items[${no}][qty]" class="form-control form-control-sm" min="0" step="0.01" oninput="hitungItem(${no})" value="${data?.qty || 1}"></td>
    <td class="fw-bold" id="jumlah-${no}">Rp 0</td>
    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusItem(${no})"><i class="bi bi-x"></i></button></td>
  `;
  tbody.appendChild(row);
  document.getElementById('totalsFoot').classList.remove('d-none');
  hitungItem(no);
}

function itemBarangChange(sel, no) {
  const customInput = document.getElementById(`barangCustom-${no}`);
  customInput.style.display = sel.value ? 'none' : 'block';
  if (!sel.value) customInput.focus();
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
  const jumlah = harga * qty;
  document.getElementById(`jumlah-${no}`).textContent = formatRp(jumlah);
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

  const diskonPersen = parseFloat(document.getElementById('diskonPersen').value) || 0;
  const ppnPersen = parseFloat(document.getElementById('ppnPersen').value) || 0;

  const diskonNominal = subTotal * (diskonPersen / 100);
  const dpp = subTotal - diskonNominal;
  const ppnNominal = dpp * (ppnPersen / 100);
  const grandTotal = dpp + ppnNominal;

  document.getElementById('subTotalDisplay').textContent = formatRp(subTotal);
  document.getElementById('subTotalSummary').textContent = formatRp(subTotal);
  document.getElementById('diskonLabel').textContent = `(${diskonPersen}%)`;
  document.getElementById('diskonSummary').textContent = `- ${formatRp(diskonNominal)}`;
  document.getElementById('dppSummary').textContent = formatRp(dpp);
  document.getElementById('ppnLabel').textContent = `(${ppnPersen}%)`;
  document.getElementById('ppnSummary').textContent = formatRp(ppnNominal);
  document.getElementById('grandTotalSummary').textContent = formatRp(grandTotal);
}

function formatRp(n) {
  return 'Rp ' + n.toLocaleString('id-ID');
}

function simpanKlienBaru() {
  const nama = document.getElementById('klienBaruNama').value;
  const alamat = document.getElementById('klienBaruAlamat').value;
  const pic = document.getElementById('klienBaruPic').value;
  const telp = document.getElementById('klienBaruTelp').value;
  const npwp = document.getElementById('klienBaruNpwp').value;
  if (!nama) return alert('Nama perusahaan harus diisi');

  const formData = new FormData();
  formData.append('nama_perusahaan', nama);
  formData.append('alamat', alamat);
  formData.append('pic', pic);
  formData.append('no_telp', telp);
  formData.append('npwp', npwp);

  fetch('../api/klien_baru.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.id) {
        const sel = document.getElementById('klienSelect');
        const opt = document.createElement('option');
        opt.value = data.id;
        opt.text = nama;
        sel.add(opt);
        sel.value = data.id;
        const modal = bootstrap.Modal.getInstance(document.getElementById('klienBaruModal'));
        modal.hide();
        document.getElementById('klienBaruNama').value = '';
        document.getElementById('klienBaruAlamat').value = '';
        document.getElementById('klienBaruPic').value = '';
        document.getElementById('klienBaruTelp').value = '';
        document.getElementById('klienBaruNpwp').value = '';
      }
    });
}

// Auto-add first row
tambahItem();
</script>

<?php require '../template/footer.php'; ?>
