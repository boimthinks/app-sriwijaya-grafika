<?php
session_start();
require 'config/database.php';
require 'config/functions.php';
cekLogin();

$title = 'Arsip';

if (isset($_GET['restore'])) {
    $stmt = $pdo->prepare("UPDATE proyek SET is_archived = 0 WHERE id = ? AND entity_id = ?");
    $stmt->execute([$_GET['restore'], $_SESSION['entity_id']]);
    header('Location: arsip.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, k.nama_perusahaan
    FROM proyek p
    JOIN klien k ON p.klien_id = k.id
    WHERE p.entity_id = ? AND p.is_archived = 1
    ORDER BY p.updated_at DESC
");
$stmt->execute([$_SESSION['entity_id']]);
$arsip_list = $stmt->fetchAll();

require 'template/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0">Arsip Proyek</h5>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr><th>ID Transaksi</th><th>Klien</th><th>Grand Total</th><th>Diarsipkan</th><th></th></tr>
        </thead>
        <tbody>
          <?php if (!$arsip_list): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada proyek di arsip</td></tr>
          <?php else: ?>
          <?php foreach ($arsip_list as $p): ?>
          <tr>
            <td><code><?= htmlspecialchars($p['no_referensi']) ?></code></td>
            <td><?= htmlspecialchars($p['nama_perusahaan']) ?></td>
            <td><?= rupiah($p['grand_total']) ?></td>
            <td><small><?= date('d/m/Y H:i', strtotime($p['updated_at'])) ?></small></td>
            <td>
              <a href="?restore=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Kembalikan proyek ini?')">
                <i class="bi bi-arrow-counterclockwise"></i> Restore
              </a>
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
