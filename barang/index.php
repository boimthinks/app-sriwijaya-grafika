<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$title = 'Data Barang';

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM barang WHERE id = ? AND entity_id = ?");
    $stmt->execute([$_GET['delete'], $_SESSION['entity_id']]);
    header('Location: index.php?msg=deleted');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    if (isset($_POST['id']) && $_POST['id']) {
        $stmt = $pdo->prepare("UPDATE barang SET nama=? WHERE id=? AND entity_id=?");
        $stmt->execute([$nama, $_POST['id'], $_SESSION['entity_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO barang (entity_id, nama) VALUES (?,?)");
        $stmt->execute([$_SESSION['entity_id'], $nama]);
    }
    header('Location: index.php?msg=saved');
    exit;
}

$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM barang WHERE entity_id = ?";
$params = [$_SESSION['entity_id']];
if ($search) {
    $sql .= " AND nama LIKE ?";
    $params[] = "%$search%";
}
$sql .= " ORDER BY nama ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$barang_list = $stmt->fetchAll();

require '../template/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0">Data Barang</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#barangModal">
    <i class="bi bi-plus-lg me-1"></i>Tambah Barang
  </button>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success py-2 small alert-dismissible fade show">Data berhasil disimpan.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <form class="row g-2 mb-3">
      <div class="col">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari barang..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-auto">
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
      </div>
    </form>
    <div class="table-responsive">
      <table class="table table-hover table-sm align-middle">
        <thead><tr><th>#</th><th>Nama Barang</th><th></th></tr></thead>
        <tbody>
          <?php if (!$barang_list): ?>
          <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data barang</td></tr>
          <?php else: ?>
          <?php foreach ($barang_list as $i => $b): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td class="fw-medium"><?= htmlspecialchars($b['nama']) ?></td>
            <td>
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" onclick="editBarang(<?= $b['id'] ?>)"><i class="bi bi-pencil"></i></button>
                <a href="?delete=<?= $b['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus barang ini?')"><i class="bi bi-trash"></i></a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="barangModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="id" id="barangId" value="">
        <div class="modal-header">
          <h6 class="modal-title fw-bold" id="modalTitle">Tambah Barang</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label small">Nama Barang</label>
            <input type="text" name="nama" id="fnama" class="form-control form-control-sm" required>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-sm btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const barangData = <?= json_encode($barang_list) ?>;
function editBarang(id) {
  const b = barangData.find(x => x.id == id);
  if (!b) return;
  document.getElementById('barangId').value = b.id;
  document.getElementById('fnama').value = b.nama;
  document.getElementById('modalTitle').textContent = 'Edit Barang';
  new bootstrap.Modal(document.getElementById('barangModal')).show();
}
</script>

<?php require '../template/footer.php'; ?>
