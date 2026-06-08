<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$title = 'Detail Proyek';
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*, k.nama_perusahaan, k.alamat as klien_alamat, k.npwp, k.pic, k.no_telp as klien_telp,
           e.name as entity_name, e.direktur, e.bank, e.atas_nama, e.no_rekening, e.alamat as entity_alamat, e.no_telp as entity_telp,
           u.name as dibuat_nama
    FROM proyek p
    JOIN klien k ON p.klien_id = k.id
    JOIN entity e ON p.entity_id = e.id
    LEFT JOIN users u ON p.dibuat_oleh = u.id
    WHERE p.id = ? AND p.entity_id = ?
");
$stmt->execute([$id, $_SESSION['entity_id']]);
$proyek = $stmt->fetch();

if (!$proyek) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("
    SELECT pi.*, b.nama as barang_nama
    FROM proyek_item pi
    LEFT JOIN barang b ON pi.barang_id = b.id
    WHERE pi.proyek_id = ? ORDER BY pi.no_urut
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$has_barang = count(array_filter($items, fn($i) => $i['kategori'] === 'barang')) > 0;
$has_jasa = count(array_filter($items, fn($i) => $i['kategori'] === 'pekerjaan')) > 0;

// Payment stages
$stmt = $pdo->prepare("SELECT * FROM proyek_tahap_pembayaran WHERE proyek_id = ? ORDER BY urutan");
$stmt->execute([$id]);
$tahap_pembayaran = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM proyek_dasar_kesepakatan WHERE proyek_id = ? ORDER BY urutan");
$stmt->execute([$id]);
$dasar_kesepakatan = $stmt->fetchAll();

// Ambil riwayat pembayaran untuk menghitung sisa tagihan & progress
$stmt = $pdo->prepare("SELECT * FROM proyek_pembayaran WHERE proyek_id = ? ORDER BY tanggal ASC, id ASC");
$stmt->execute([$id]);
$pembayaran_list = $stmt->fetchAll();

$total_terbayar = 0;
foreach ($pembayaran_list as $pemb) {
    $total_terbayar += (float)$pemb['jumlah'];
}
$sisa_tagihan = max(0, $proyek['grand_total'] - $total_terbayar);
$persen_terbayar = $proyek['grand_total'] > 0 ? round(($total_terbayar / $proyek['grand_total']) * 100) : 0;
$status_saat_ini_lunas = ($sisa_tagihan <= 0);

require '../template/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">Detail Proyek</h5>
    <small class="text-muted">ID Transaksi: <code><?= htmlspecialchars($proyek['no_referensi']) ?></code></small>
  </div>
  <div class="d-flex gap-2">
    <a href="edit.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-pencil me-1"></i>Edit
    </a>
    <a href="../api/toggle_arsip.php?proyek_id=<?= $id ?>" class="btn btn-outline-secondary btn-sm" onclick="event.preventDefault(); toggleArsip(<?= $id ?>)">
      <i class="bi bi-archive me-1"></i>Arsipkan
    </a>
    <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
  </div>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success py-2 small alert-dismissible fade show">
  <?= $_GET['msg'] === 'created' ? 'Proyek berhasil dibuat.' : 'Proyek berhasil diupdate.' ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <!-- Client Info -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-transparent py-2">
        <h6 class="fw-bold mb-0">Data Klien</h6>
      </div>
      <div class="card-body">
        <div class="row g-2 small">
          <div class="col-md-6"><strong>Perusahaan:</strong> <?= htmlspecialchars($proyek['nama_perusahaan']) ?></div>
          <div class="col-md-6"><strong>NPWP:</strong> <?= htmlspecialchars($proyek['npwp'] ?: '-') ?></div>
          <div class="col-md-6"><strong>PIC:</strong> <?= htmlspecialchars($proyek['pic'] ?: '-') ?></div>
          <div class="col-md-6"><strong>Telp:</strong> <?= htmlspecialchars($proyek['klien_telp'] ?: '-') ?></div>
          <div class="col-12"><strong>Alamat:</strong> <?= htmlspecialchars($proyek['klien_alamat'] ?: '-') ?></div>
          <?php if (!empty($proyek['alamat_pengiriman'])): ?>
          <div class="col-12"><strong>Alamat Pengiriman:</strong> <?= htmlspecialchars($proyek['alamat_pengiriman']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Items Table -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-transparent py-2">
        <h6 class="fw-bold mb-0">Item Barang / Jasa</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>#</th><th>Nama Barang</th><th>Kategori</th><th>Keterangan</th>
                <th class="text-end">Harga</th><th class="text-center">Qty</th><th class="text-end">Jumlah</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td><?= $item['no_urut'] ?></td>
                <td class="fw-medium"><?= htmlspecialchars($item['barang_nama'] ?: 'Custom') ?></td>
                <td><span class="badge bg-<?= $item['kategori'] === 'pekerjaan' ? 'info' : 'secondary' ?>"><?= $item['kategori'] === 'pekerjaan' ? 'Jasa' : 'Barang' ?></span></td>
                <td><small><?= htmlspecialchars($item['keterangan'] ?: '-') ?></small></td>
                <td class="text-end"><?= rupiah($item['harga']) ?></td>
                <td class="text-center"><?= $item['qty'] ?></td>
                <td class="text-end fw-medium"><?= rupiah($item['jumlah']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
              <tr><td colspan="6" class="text-end fw-medium">Sub Total</td><td class="text-end fw-bold"><?= rupiah($proyek['sub_total']) ?></td></tr>
              <tr><td colspan="6" class="text-end fw-medium">Diskon (<?= $proyek['diskon_persen'] ?>%)</td><td class="text-end text-danger">- <?= rupiah($proyek['sub_total'] - $proyek['dpp']) ?></td></tr>
              <tr><td colspan="6" class="text-end fw-medium">DPP</td><td class="text-end fw-bold"><?= rupiah($proyek['dpp']) ?></td></tr>
              <tr><td colspan="6" class="text-end fw-medium">PPN (<?= $proyek['ppn_persen'] ?>%)</td><td class="text-end"><?= rupiah($proyek['grand_total'] - $proyek['dpp']) ?></td></tr>
              <tr class="table-primary"><td colspan="6" class="text-end fw-bold">Grand Total</td><td class="text-end fw-bold fs-6"><?= rupiah($proyek['grand_total']) ?></td></tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Tahap Pembayaran -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-transparent py-2 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Tahap Pembayaran</h6>
        <?php if ($proyek['no_sk']): ?>
        <small class="text-muted">(<?= count($tahap_pembayaran) ?> tahap)</small>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if ($tahap_pembayaran): ?>
        <table class="table table-sm mb-2">
          <thead><tr><th class="text-center" style="width:40px">#</th><th style="width:60px">%</th><th>Deskripsi</th><th class="text-end" style="width:140px">Nominal</th></tr></thead>
          <tbody>
            <?php foreach ($tahap_pembayaran as $tp): ?>
            <tr>
              <td class="text-center"><?= $tp['urutan'] ?></td>
              <td><?= (int)$tp['persentase'] ?>%</td>
              <td><small><?= htmlspecialchars($tp['deskripsi']) ?></small></td>
              <td class="text-end"><?= rupiah($proyek['grand_total'] * $tp['persentase'] / 100) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <small class="text-muted">Belum ada tahap pembayaran.</small>
        <?php endif; ?>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">
          <i class="bi bi-gear"></i> Atur Tahap Pembayaran
        </a>
      </div>
    </div>

    <!-- Riwayat Pembayaran & Kuitansi -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-transparent py-2 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Riwayat Pembayaran & Kuitansi</h6>
        <a href="riwayat_pembayaran.php" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
          <i class="bi bi-list-ul me-1"></i>Lihat Semua
        </a>
        <span class="badge bg-<?= $status_saat_ini_lunas ? 'success' : 'danger' ?>">
          <?= $status_saat_ini_lunas ? 'Lunas' : 'Belum Lunas' ?>
        </span>
      </div>
      <div class="card-body">
        <!-- Progress Bar -->
        <div class="mb-3">
          <div class="d-flex justify-content-between small fw-medium mb-1">
            <span>Progress Pembayaran</span>
            <span><?= $persen_terbayar ?>% (<?= rupiah($total_terbayar) ?> / <?= rupiah($proyek['grand_total']) ?>)</span>
          </div>
          <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-<?= $status_saat_ini_lunas ? 'success' : 'primary' ?>" role="progressbar" style="width: <?= $persen_terbayar ?>%" aria-valuenow="<?= $persen_terbayar ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <div class="text-end small mt-1 text-muted">
            Sisa Tagihan: <strong class="text-<?= $status_saat_ini_lunas ? 'success' : 'danger' ?>"><?= rupiah($sisa_tagihan) ?></strong>
          </div>
        </div>

        <!-- Tabel Riwayat -->
        <?php if ($pembayaran_list): ?>
        <div class="table-responsive mb-3">
          <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
            <thead>
              <tr>
                <th>No Kuitansi</th>
                <th>Tanggal</th>
                <th class="text-end">Jumlah</th>
                <th>Metode</th>
                <th>Rujukan Invoice</th>
                <th>Keterangan</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pembayaran_list as $pemb): ?>
              <tr>
                <td class="fw-medium"><code><?= htmlspecialchars($pemb['no_kuitansi']) ?></code></td>
                <td><?= date('d/m/Y', strtotime($pemb['tanggal'])) ?></td>
                <td class="text-end fw-semibold"><?= rupiah($pemb['jumlah']) ?></td>
                <td><?= htmlspecialchars($pemb['metode_pembayaran']) ?></td>
                <td><small class="text-muted"><?= htmlspecialchars($pemb['no_invoice'] ?: '-') ?></small></td>
                <td><small class="text-muted"><?= htmlspecialchars($pemb['keterangan'] ?: '-') ?></small></td>
                <td class="text-end">
                  <div class="d-flex gap-1 justify-content-end">
                    <a href="../template/kuitansi.php?id=<?= $pemb['id'] ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size: 0.75rem;" title="Cetak Kuitansi">
                      <i class="bi bi-printer"></i>
                    </a>
                    <button class="btn btn-xs btn-outline-danger py-0 px-1" style="font-size: 0.75rem;" onclick="hapusPembayaran(<?= $pemb['id'] ?>)" title="Hapus">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="text-center py-3 text-muted small">
          Belum ada riwayat pembayaran untuk proyek ini.
        </div>
        <?php endif; ?>

        <!-- Tombol Tambah Pembayaran -->
        <?php if (!$status_saat_ini_lunas): ?>
        <button class="btn btn-sm btn-primary w-100" onclick="tambahPembayaran()">
          <i class="bi bi-plus-lg me-1"></i> Catat Pembayaran Baru
        </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Dasar Kesepakatan -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-transparent py-2">
        <h6 class="fw-bold mb-0">Dasar Kesepakatan</h6>
      </div>
      <div class="card-body">
        <?php if ($dasar_kesepakatan): ?>
        <ol class="small mb-0 ps-3">
          <li>Surat Penawaran No. <?= htmlspecialchars($proyek['no_sp'] ?: '-') ?></li>
          <?php foreach ($dasar_kesepakatan as $dk): ?>
          <li><?= htmlspecialchars($dk['deskripsi']) ?></li>
          <?php endforeach; ?>
        </ol>
        <?php else: ?>
        <ol class="small mb-0 ps-3">
          <li>Surat Penawaran No. <?= htmlspecialchars($proyek['no_sp'] ?: '-') ?></li>
          <li>Surat Pengajuan Negosiasi Pembayaran</li>
          <li>Purchase Order (PO) dari Pihak Pertama</li>
        </ol>
        <?php endif; ?>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">
          <i class="bi bi-gear"></i> Atur Dasar Kesepakatan
        </a>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <!-- Dokumen -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-transparent py-2">
        <h6 class="fw-bold mb-0">Generate Dokumen</h6>
      </div>
      <div class="card-body">
        <?php
        $docs = [
            'sp'          => ['label' => 'Surat Penawaran',             'file' => 'surat_penawaran',     'icon' => 'file-earmark-text', 'color' => 'success', 'prasyarat' => null],
            'sk'          => ['label' => 'Surat Kesepakatan',           'file' => 'surat_kesepakatan',   'icon' => 'file-earmark-check', 'color' => 'primary', 'prasyarat' => 'no_sp'],
            'proforma'    => ['label' => 'Proforma Invoice',            'file' => 'proforma_invoice',    'icon' => 'file-earmark', 'color' => 'info', 'prasyarat' => 'no_sk'],
            'inv_dp'      => ['label' => 'Invoice DP',                 'file' => 'invoice_dp',          'icon' => 'file-earmark', 'color' => 'dark', 'prasyarat' => 'no_proforma'],
            'inv_pelunasan' => ['label' => 'Invoice Pelunasan',        'file' => 'invoice_pelunasan',   'icon' => 'file-earmark', 'color' => 'primary', 'prasyarat' => 'no_proforma'],
            'sj'          => ['label' => 'Surat Jalan',                 'file' => 'surat_jalan',         'icon' => 'truck', 'color' => 'warning', 'prasyarat' => 'no_proforma'],
            'ba'          => ['label' => 'BA Serah Terima',            'file' => 'ba_serah_terima',     'icon' => 'file-earmark', 'color' => 'secondary', 'prasyarat' => 'no_proforma'],
        ];
        ?>
        <div class="d-flex flex-column gap-2">
          <?php
          $visibleDocs = [];
          foreach ($docs as $jenis => $doc):
            $kolom = "no_$jenis";
            $ada = $proyek[$kolom];
            $prasyarat_terpenuhi = !$doc['prasyarat'] || $proyek[$doc['prasyarat']];
            if ($jenis !== 'sp' && !$ada && !$prasyarat_terpenuhi) continue;
            $visibleDocs[] = [$jenis, $doc, $ada];
          endforeach;
          ?>
          <?php if (empty($visibleDocs)): ?>
          <div class="text-center py-3 text-muted small">
            Belum ada dokumen yang bisa dibuat.<br>
            Buat <strong>Surat Penawaran</strong> terlebih dahulu.
          </div>
          <?php else: ?>
            <?php foreach ($visibleDocs as [$jenis, $doc, $ada]): ?>
          <div class="d-flex align-items-center justify-content-between p-2 rounded border">
            <div>
              <i class="bi bi-<?= $doc['icon'] ?> text-<?= $doc['color'] ?> me-1"></i>
              <small class="fw-medium"><?= $doc['label'] ?></small>
              <?php if ($ada): ?>
              <br><small class="text-muted"><?= htmlspecialchars($ada) ?></small>
              <?php endif; ?>
              <?php if ($jenis === 'inv_dp' && !$ada && $proyek['dp_persen']): ?>
              <br><small class="text-muted">DP: <?= (int)$proyek['dp_persen'] ?>%</small>
              <?php endif; ?>
            </div>
            <div class="d-flex gap-1">
              <?php if ($ada): ?>
              <a href="../template/<?= $doc['file'] ?>.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline-<?= $doc['color'] ?>">
                <i class="bi bi-printer"></i> Cetak
              </a>
              <?php elseif ($jenis === 'inv_dp'): ?>
              <button class="btn btn-sm btn-dark" onclick="generateDokumen(<?= $id ?>, '<?= $jenis ?>', this)">
                <i class="bi bi-plus-circle"></i> Buat
              </button>
              <?php else: ?>
              <button class="btn btn-sm btn-<?= $doc['color'] ?>" onclick="generateDokumen(<?= $id ?>, '<?= $jenis ?>', this)">
                <i class="bi bi-plus-circle"></i> Buat
              </button>
              <?php endif; ?>
            </div>
          </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Info Ringkas -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent py-2">
        <h6 class="fw-bold mb-0">Info Proyek</h6>
      </div>
      <div class="card-body small">
        <div class="mb-1"><strong>Entity:</strong> <?= htmlspecialchars($proyek['entity_name']) ?></div>
        <div class="mb-1"><strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($proyek['tanggal'])) ?></div>
        <div class="mb-1"><strong>Berlaku:</strong> <?= $proyek['berlaku_sampai'] ? date('d/m/Y', strtotime($proyek['berlaku_sampai'])) : '-' ?></div>
        <div class="mb-1"><strong>Dibuat oleh:</strong> <?= htmlspecialchars($proyek['dibuat_nama'] ?: '-') ?></div>
        <div><strong>Dibuat:</strong> <?= date('d/m/Y H:i', strtotime($proyek['created_at'])) ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Atur DP -->
<div class="modal fade" id="modalAturDP" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold">Atur Jumlah DP</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form onsubmit="simpanDP(event)">
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label small">Persentase DP (%)</label>
            <div class="input-group">
              <input type="number" id="dpInput" class="form-control" value="<?= (int)($proyek['dp_persen'] ?: 50) ?>" min="1" max="100" step="0.1" required>
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="small text-muted">Grand Total: <strong><?= rupiah($proyek['grand_total']) ?></strong></div>
          <div class="small text-muted" id="dpPreview"></div>
        </div>
        <div class="modal-footer py-1">
          <button type="submit" class="btn btn-primary btn-sm" id="btnSimpanDP">
            <i class="bi bi-check-lg"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Catat Pembayaran -->
<div class="modal fade" id="modalCatatPembayaran" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title fw-bold">Catat Pembayaran Baru</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form onsubmit="simpanPembayaran(event)">
        <input type="hidden" name="proyek_id" value="<?= $id ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-medium">Tanggal Pembayaran</label>
            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-medium">Telah Diterima Dari</label>
            <input type="text" name="telah_diterima_dari" class="form-control" value="<?= htmlspecialchars($proyek['pic'] ?: $proyek['nama_perusahaan']) ?>" required>
            <div class="form-text small" style="font-size: 0.75rem;">Nama pembayar atau penanggung jawab pembayaran.</div>
          </div>

          <div class="row g-2">
              <div class="col-md-6 mb-3">
                <label class="form-label small fw-medium">Nominal Pembayaran</label>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <input type="text" name="jumlah" id="jumlahInput" class="form-control" value="<?= number_format(round($sisa_tagihan), 0, ',', '.') ?>" required inputmode="numeric" pattern="[0-9.]*">
                </div>
                <div class="form-text small" style="font-size: 0.75rem;">Sisa: <?= rupiah($sisa_tagihan) ?></div>
              </div>
            
            <div class="col-md-6 mb-3">
              <label class="form-label small fw-medium">Metode Pembayaran</label>
              <select name="metode_pembayaran" class="form-select" required>
                <option value="Transfer BCA">Transfer BCA</option>
                <option value="Transfer Mandiri">Transfer Mandiri</option>
                <option value="Tunai">Tunai / Cash</option>
                <option value="Lainnya">Lainnya</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-medium">Rujukan Nomor Invoice / Dokumen</label>
            <select name="no_invoice" class="form-select">
              <option value="">-- Tanpa Rujukan --</option>
              <?php if ($proyek['no_inv_dp']): ?>
                <option value="<?= htmlspecialchars($proyek['no_inv_dp']) ?>">Invoice DP (<?= htmlspecialchars($proyek['no_inv_dp']) ?>)</option>
              <?php endif; ?>
              <?php if ($proyek['no_inv_pelunasan']): ?>
                <option value="<?= htmlspecialchars($proyek['no_inv_pelunasan']) ?>">Invoice Pelunasan (<?= htmlspecialchars($proyek['no_inv_pelunasan']) ?>)</option>
              <?php endif; ?>
              <?php if ($proyek['no_proforma']): ?>
                <option value="<?= htmlspecialchars($proyek['no_proforma']) ?>">Proforma Invoice (<?= htmlspecialchars($proyek['no_proforma']) ?>)</option>
              <?php endif; ?>
              <?php if ($proyek['no_sp']): ?>
                <option value="<?= htmlspecialchars($proyek['no_sp']) ?>">Surat Penawaran (<?= htmlspecialchars($proyek['no_sp']) ?>)</option>
              <?php endif; ?>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label small fw-medium">Keterangan / Deskripsi</label>
            <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Pembayaran Uang Muka DP 50% atau Pembayaran Tahap Pelunasan"></textarea>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm" id="btnSimpanPembayaran">
            <i class="bi bi-check-lg me-1"></i>Simpan Catatan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function formatRupiahInput(input) {
  let value = input.value.replace(/[^0-9]/g, '');
  if (value) {
    value = parseInt(value, 10).toLocaleString('id-ID');
  }
  input.value = value;
}

function getRawNumber(formattedValue) {
  return parseInt(formattedValue.replace(/[^0-9]/g, ''), 10) || 0;
}

document.getElementById('jumlahInput')?.addEventListener('input', function(e) {
  const cursorPos = e.target.selectionStart;
  const oldValue = e.target.value;
  formatRupiahInput(e.target);
  const newValue = e.target.value;
  const diff = newValue.length - oldValue.length;
  e.target.setSelectionRange(cursorPos + diff, cursorPos + diff);
});

function tambahPembayaran() {
  new bootstrap.Modal(document.getElementById('modalCatatPembayaran')).show();
}

function simpanPembayaran(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSimpanPembayaran');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
  
  const form = e.target;
  const formData = new FormData(form);
  
  const jumlahInput = document.getElementById('jumlahInput');
  if (jumlahInput) {
    formData.set('jumlah', getRawNumber(jumlahInput.value));
  }

  fetch('../api/simpan_pembayaran.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert(data.error || 'Gagal menyimpan pembayaran');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Simpan Catatan';
      }
    })
    .catch(() => {
      alert('Gagal menghubungi server');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Simpan Catatan';
    });
}

function hapusPembayaran(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus catatan pembayaran ini? Tindakan ini tidak dapat dibatalkan.')) return;
  
  const formData = new FormData();
  formData.append('id', id);

  fetch('../api/hapus_pembayaran.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert(data.error || 'Gagal menghapus pembayaran');
      }
    })
    .catch(() => {
      alert('Gagal menghubungi server');
    });
}

function aturDP(proyekId) {
  hitungPreview();
  document.getElementById('dpInput').dataset.proyekId = proyekId;
  new bootstrap.Modal(document.getElementById('modalAturDP')).show();
}

document.getElementById('dpInput')?.addEventListener('input', hitungPreview);
function hitungPreview() {
  const pct = parseFloat(document.getElementById('dpInput').value) || 0;
  const total = <?= $proyek['grand_total'] ?>;
  document.getElementById('dpPreview').textContent = 'DP: ' + pct + '% = Rp ' + new Intl.NumberFormat('id-ID').format(total * pct / 100);
}

function simpanDP(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSimpanDP');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  const proyekId = document.getElementById('dpInput').dataset.proyekId;
  const dpPersen = document.getElementById('dpInput').value;
  const formData = new FormData();
  formData.append('proyek_id', proyekId);
  formData.append('dp_persen', dpPersen);
  fetch('../api/set_dp_persen.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert(data.error || 'Gagal menyimpan DP');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Simpan';
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-lg"></i> Simpan';
    });
}

function generateDokumen(proyekId, jenis, btn) {
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

  const formData = new FormData();
  formData.append('proyek_id', proyekId);
  formData.append('jenis', jenis);

  fetch('../api/generate_dokumen.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.no_surat) {
        location.reload();
      } else {
        alert('Gagal generate dokumen');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus-circle"></i> Buat';
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-plus-circle"></i> Buat';
    });
}

function toggleArsip(id) {
  if (!confirm('Arsipkan proyek ini?')) return;
  const formData = new FormData();
  formData.append('proyek_id', id);
  fetch('../api/toggle_arsip.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(() => { window.location.href = 'index.php'; });
}
</script>

<?php require '../template/footer.php'; ?>
