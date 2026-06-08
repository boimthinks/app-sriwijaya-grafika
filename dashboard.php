<?php
session_start();
require 'config/database.php';
require 'config/functions.php';
cekLogin();

$title = 'Dashboard';

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM proyek WHERE entity_id = ? AND is_archived = 0");
$stmt->execute([$_SESSION['entity_id']]);
$total_proyek = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM klien WHERE entity_id = ?");
$stmt->execute([$_SESSION['entity_id']]);
$total_klien = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(grand_total),0) FROM proyek WHERE entity_id = ? AND is_archived = 0 AND YEAR(created_at) = YEAR(CURDATE())");
$stmt->execute([$_SESSION['entity_id']]);
$total_revenue = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM proyek WHERE entity_id = ? AND is_archived = 0 AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$stmt->execute([$_SESSION['entity_id']]);
$bulan_ini = $stmt->fetchColumn();

// Completion indicator (Fear & Greed style)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM proyek WHERE entity_id = ? AND is_archived = 0 AND no_sp IS NOT NULL");
$stmt->execute([$_SESSION['entity_id']]);
$with_sp = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM proyek WHERE entity_id = ? AND is_archived = 0 AND no_ba IS NOT NULL");
$stmt->execute([$_SESSION['entity_id']]);
$with_ba = (int)$stmt->fetchColumn();

$indicator_value = $with_sp > 0 ? round(($with_ba / $with_sp) * 100) : 0;

function indicator_color($v): string {
    if ($v <= 20) return '#dc3545';
    if ($v <= 40) return '#fd7e14';
    if ($v <= 60) return '#ffc107';
    if ($v <= 80) return '#20c997';
    return '#198754';
}

function indicator_label($v): string {
    if ($v <= 20) return 'Sangat Rendah';
    if ($v <= 40) return 'Rendah';
    if ($v <= 60) return 'Sedang';
    if ($v <= 80) return 'Tinggi';
    return 'Sangat Tinggi';
}

// Recent projects
$stmt = $pdo->prepare("
    SELECT p.*, k.nama_perusahaan
    FROM proyek p
    JOIN klien k ON p.klien_id = k.id
    WHERE p.entity_id = ? AND p.is_archived = 0
    ORDER BY p.created_at DESC LIMIT 5
");
$stmt->execute([$_SESSION['entity_id']]);
$recent_proyek = $stmt->fetchAll();

require 'template/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0">Dashboard</h5>
  <a href="proyek/create.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Proyek Baru
  </a>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm stat-card">
      <div class="card-body p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary-subtle p-2 rounded">
            <i class="bi bi-folder2 text-primary fs-4"></i>
          </div>
          <div>
            <small class="text-muted">Total Proyek</small>
            <h5 class="fw-bold mb-0"><?= $total_proyek ?></h5>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm stat-card">
      <div class="card-body p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-success-subtle p-2 rounded">
            <i class="bi bi-people text-success fs-4"></i>
          </div>
          <div>
            <small class="text-muted">Total Klien</small>
            <h5 class="fw-bold mb-0"><?= $total_klien ?></h5>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm stat-card">
      <div class="card-body p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-warning-subtle p-2 rounded">
            <i class="bi bi-currency-dollar text-warning fs-4"></i>
          </div>
          <div>
            <small class="text-muted">Revenue Tahun Ini</small>
            <h5 class="fw-bold mb-0"><?= rupiah($total_revenue) ?></h5>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm stat-card">
      <div class="card-body p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-info-subtle p-2 rounded">
            <i class="bi bi-calendar-check text-info fs-4"></i>
          </div>
          <div>
            <small class="text-muted">Bulan Ini</small>
            <h5 class="fw-bold mb-0"><?= $bulan_ini ?></h5>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="bg-dark bg-opacity-10 p-2 rounded">
            <i class="bi bi-graph-up-arrow fs-4"></i>
          </div>
          <div>
            <small class="text-muted">Indeks Penyelesaian</small>
          </div>
        </div>
        <div class="indicator-wrapper">
          <div class="indicator-bar">
            <div class="indicator-pointer" style="left: <?= $indicator_value ?>%">
              <div class="indicator-value" style="background:<?= indicator_color($indicator_value) ?>"><?= $indicator_value ?></div>
              <div class="indicator-arrow" style="border-top-color:<?= indicator_color($indicator_value) ?>"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-transparent py-3">
    <h6 class="fw-bold mb-0">Proyek Terbaru</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>ID Transaksi</th>
            <th>Klien</th>
            <th>Tanggal</th>
            <th>Grand Total</th>
            <th>Dokumen</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$recent_proyek): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Belum ada proyek</td></tr>
          <?php else: ?>
          <?php foreach ($recent_proyek as $p): ?>
          <tr>
            <td><code><?= htmlspecialchars($p['no_referensi']) ?></code></td>
            <td><?= htmlspecialchars($p['nama_perusahaan']) ?></td>
            <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
            <td class="fw-medium"><?= rupiah($p['grand_total']) ?></td>
            <td>
              <?php if ($p['no_sp']): ?><span class="badge bg-success me-1">SP</span><?php endif; ?>
              <?php if ($p['no_sk']): ?><span class="badge bg-primary me-1">SK</span><?php endif; ?>
              <?php if ($p['no_inv']): ?><span class="badge bg-info me-1">INV</span><?php endif; ?>
              <?php if ($p['no_sj']): ?><span class="badge bg-warning me-1">SJ</span><?php endif; ?>
              <?php if ($p['no_ba']): ?><span class="badge bg-secondary me-1">BA</span><?php endif; ?>
              <?php if (!$p['no_sp'] && !$p['no_sk'] && !$p['no_inv'] && !$p['no_sj'] && !$p['no_ba']): ?>
              <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="proyek/detail.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Detail</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require 'template/footer.php'; ?>
