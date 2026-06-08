<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$title = 'Daftar Proyek';

$search = $_GET['search'] ?? '';
$sql = "SELECT p.*, k.nama_perusahaan FROM proyek p JOIN klien k ON p.klien_id = k.id WHERE p.entity_id = ? AND p.is_archived = 0";
$params = [$_SESSION['entity_id']];
if ($search) {
    $sql .= " AND (p.no_referensi LIKE ? OR k.nama_perusahaan LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$proyek_list = $stmt->fetchAll();

$docs = [
  'no_sp' => ['label' => 'SP', 'icon' => 'bi-file-earmark-text', 'color' => '#198754'],
  'no_sk' => ['label' => 'SK', 'icon' => 'bi-file-earmark-check', 'color' => '#0d6efd'],
  'no_proforma' => ['label' => 'Proforma', 'icon' => 'bi-receipt', 'color' => '#6f42c1'],
  'no_inv_dp' => ['label' => 'INV DP', 'icon' => 'bi-coin', 'color' => '#fd7e14'],
  'no_inv_pelunasan' => ['label' => 'INV Lunas', 'icon' => 'bi-cash-coin', 'color' => '#20c997'],
  'no_sj' => ['label' => 'SJ', 'icon' => 'bi-truck', 'color' => '#ffc107'],
  'no_ba' => ['label' => 'BA', 'icon' => 'bi-file-earmark-check-fill', 'color' => '#dc3545'],
];

require '../template/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0">Daftar Proyek</h5>
  <a href="create.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Proyek Baru
  </a>
</div>

<form class="row g-2 mb-4">
  <div class="col">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari ID transaksi atau klien..." value="<?= htmlspecialchars($search) ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
  </div>
</form>

<?php if (!$proyek_list): ?>
<div class="text-center text-muted py-5">
  <i class="bi bi-folder2-open fs-1 d-block mb-2"></i>
  <span>Belum ada proyek</span>
</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($proyek_list as $p): ?>
  <?php
    $progress = 0;
    $total = count($docs);
    $done = 0;
    foreach (array_keys($docs) as $col) { if ($p[$col]) $done++; }
    $progress = $total > 0 ? round(($done / $total) * 100) : 0;
  ?>
  <div class="col-12">
    <div class="card border-0 shadow-sm project-card">
      <div class="card-body p-3">
        <div class="row g-3 align-items-start">
          <div class="col-md-5">
            <div class="d-flex align-items-start gap-3">
              <div class="project-icon">
                <i class="bi bi-layers"></i>
              </div>
              <div class="min-w-0 flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <a href="detail.php?id=<?= $p['id'] ?>" class="text-decoration-none fw-semibold text-reset stretched-link"><?= htmlspecialchars($p['no_referensi']) ?></a>
                  <span class="badge bg-light text-muted border"><?= date('d/m/Y', strtotime($p['tanggal'])) ?></span>
                </div>
                <div class="text-muted small mb-1"><?= htmlspecialchars($p['nama_perusahaan']) ?></div>
                <div class="fw-bold" style="color:var(--bs-primary)"><?= rupiah($p['grand_total']) ?></div>
              </div>
            </div>
          </div>
          <div class="col-md-7">
            <div class="doc-map">
              <div class="doc-map-track">
                <?php foreach ($docs as $col => $doc): ?>
                <div class="doc-node<?= $p[$col] ? ' done' : '' ?>" style="--doc-color:<?= $doc['color'] ?>">
                  <div class="doc-node-icon">
                    <i class="bi <?= $doc['icon'] ?>"></i>
                  </div>
                  <div class="doc-node-label"><?= $doc['label'] ?></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require '../template/footer.php'; ?>
