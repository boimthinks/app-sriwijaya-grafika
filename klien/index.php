<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$title = 'Data Klien';

// Handle delete (archive)
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM klien WHERE id = ? AND entity_id = ?");
    $stmt->execute([$_GET['delete'], $_SESSION['entity_id']]);
    header('Location: index.php?msg=deleted');
    exit;
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_perusahaan'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $npwp = $_POST['npwp'] ?? '';
    $pic = $_POST['pic'] ?? '';
    $no_telp = $_POST['no_telp'] ?? '';

    if (isset($_POST['id']) && $_POST['id']) {
        $stmt = $pdo->prepare("UPDATE klien SET nama_perusahaan=?, alamat=?, npwp=?, pic=?, no_telp=? WHERE id=? AND entity_id=?");
        $stmt->execute([$nama, $alamat, $npwp, $pic, $no_telp, $_POST['id'], $_SESSION['entity_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO klien (entity_id, nama_perusahaan, alamat, npwp, pic, no_telp) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$_SESSION['entity_id'], $nama, $alamat, $npwp, $pic, $no_telp]);
    }
    header('Location: index.php?msg=saved');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM klien WHERE id = ? AND entity_id = ?");
    $stmt->execute([$_GET['edit'], $_SESSION['entity_id']]);
    $edit = $stmt->fetch();
}

$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM klien WHERE entity_id = ?";
$params = [$_SESSION['entity_id']];
if ($search) {
    $sql .= " AND (nama_perusahaan LIKE ? OR pic LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY nama_perusahaan ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$klien_list = $stmt->fetchAll();

require '../template/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0">Data Klien</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#klienModal">
    <i class="bi bi-plus-lg me-1"></i>Tambah Klien
  </button>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success py-2 small alert-dismissible fade show">
  Data berhasil disimpan.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <form class="row g-2 mb-3">
      <div class="col">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari klien..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="col-auto">
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover table-sm align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Perusahaan</th>
            <th>PIC</th>
            <th>No. Telp</th>
            <th>NPWP</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$klien_list): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data klien</td></tr>
          <?php else: ?>
          <?php foreach ($klien_list as $i => $k): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td class="fw-medium"><?= htmlspecialchars($k['nama_perusahaan'] ?? '') ?></td>
            <td><?= htmlspecialchars($k['pic'] ?? '') ?></td>
            <td><?= htmlspecialchars($k['no_telp'] ?? '') ?></td>
            <td><small><?= htmlspecialchars($k['npwp'] ?? '') ?></small></td>
            <td>
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" onclick="editKlien(<?= $k['id'] ?>)"><i class="bi bi-pencil"></i></button>
                <a href="?delete=<?= $k['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus klien ini?')"><i class="bi bi-trash"></i></a>
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

<!-- Modal -->
<div class="modal fade" id="klienModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="id" id="klienId" value="">
        <div class="modal-header">
          <h6 class="modal-title fw-bold" id="modalTitle">Tambah Klien</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label small">Nama Perusahaan</label>
            <input type="text" name="nama_perusahaan" id="fnama_perusahaan" class="form-control form-control-sm" required>
          </div>
          <div class="mb-2">
            <label class="form-label small">Alamat</label>
            <textarea name="alamat" id="falamat" class="form-control form-control-sm" rows="2"></textarea>
          </div>
          <div class="row g-2">
            <div class="col">
              <label class="form-label small">PIC</label>
              <input type="text" name="pic" id="fpic" class="form-control form-control-sm">
            </div>
            <div class="col">
              <label class="form-label small">No. Telp</label>
              <input type="text" name="no_telp" id="fno_telp" class="form-control form-control-sm">
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label small">NPWP</label>
            <input type="text" name="npwp" id="fnpwp" class="form-control form-control-sm">
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
const klienData = <?= json_encode($klien_list) ?>;

function editKlien(id) {
  const k = klienData.find(x => x.id == id);
  if (!k) return;
  document.getElementById('klienId').value = k.id;
  document.getElementById('fnama_perusahaan').value = k.nama_perusahaan;
  document.getElementById('falamat').value = k.alamat || '';
  document.getElementById('fpic').value = k.pic || '';
  document.getElementById('fno_telp').value = k.no_telp || '';
  document.getElementById('fnpwp').value = k.npwp || '';
  document.getElementById('modalTitle').textContent = 'Edit Klien';
  new bootstrap.Modal(document.getElementById('klienModal')).show();
}
</script>

<?php require '../template/footer.php'; ?>
