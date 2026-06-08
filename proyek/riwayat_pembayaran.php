<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$title = 'Riwayat Pembayaran';

$stmt = $pdo->prepare("
    SELECT pp.*, p.no_referensi, p.no_sp, k.nama_perusahaan
    FROM proyek_pembayaran pp
    JOIN proyek p ON pp.proyek_id = p.id
    JOIN klien k ON p.klien_id = k.id
    WHERE pp.entity_id = ?
    ORDER BY pp.tanggal DESC, pp.id DESC
");
$stmt->execute([$_SESSION['entity_id']]);
$pembayaran_list = $stmt->fetchAll();

$total_terbayar = array_sum(array_column($pembayaran_list, 'jumlah'));

require '../template/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">Riwayat Pembayaran</h5>
    <small class="text-muted">Semua catatan pembayaran yang pernah dicatat dari fitur Catat Pembayaran.</small>
  </div>
  <a href="index.php" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Total Pembayaran</div>
        <div class="fs-4 fw-bold"><?= rupiah($total_terbayar) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Jumlah Transaksi</div>
        <div class="fs-4 fw-bold"><?= count($pembayaran_list) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Aksi Cepat</div>
        <a href="index.php" class="btn btn-sm btn-outline-primary mt-2">
          <i class="bi bi-list-ul me-1"></i>Lihat Semua Proyek
        </a>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-transparent py-2 d-flex justify-content-between align-items-center">
    <h6 class="fw-bold mb-0">Daftar Riwayat Pembayaran</h6>
    <span class="badge bg-primary-subtle text-primary"><?= count($pembayaran_list) ?> data</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>No Kuitansi</th>
            <th>Proyek</th>
            <th>Klien</th>
            <th>Tanggal</th>
            <th class="text-end">Jumlah</th>
            <th>Metode</th>
            <th>Rujukan Invoice</th>
            <th>Keterangan</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($pembayaran_list): foreach ($pembayaran_list as $pemb): ?>
          <tr>
            <td class="fw-medium"><code><?= htmlspecialchars($pemb['no_kuitansi']) ?></code></td>
            <td>
              <div class="fw-medium">#<?= htmlspecialchars($pemb['no_referensi']) ?></div>
              <small class="text-muted">SP: <?= htmlspecialchars($pemb['no_sp'] ?: '-') ?></small>
            </td>
            <td><?= htmlspecialchars($pemb['nama_perusahaan']) ?></td>
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
                <button class="btn btn-xs btn-outline-danger py-0 px-1" style="font-size: 0.75rem;" onclick="hapusPembayaran(<?= $pemb['id'] ?>)" title="Hapus Catatan">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr>
            <td colspan="9" class="text-center py-4 text-muted">Belum ada riwayat pembayaran yang dicatat.</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function hapusPembayaran(id) {
  if (!confirm('Apakah Anda yakin ingin menghapus catatan pembayaran ini?')) return;

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
</script>

<?php require '../template/footer.php'; ?>
