<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();
cekRole(['super_admin', 'owner']);

$title = 'Manajemen User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'karyawan';
    $entity_id = $_SESSION['role'] === 'super_admin' ? ($_POST['entity_id'] ?? $_SESSION['entity_id']) : $_SESSION['entity_id'];

    if (isset($_POST['id']) && $_POST['id']) {
        if ($_POST['password']) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, password=? WHERE id=?");
            $stmt->execute([$nama, $email, $role, $pass, $_POST['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
            $stmt->execute([$nama, $email, $role, $_POST['id']]);
        }
    } else {
        $pass = password_hash($_POST['password'] ?: '123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (entity_id, name, email, password, role) VALUES (?,?,?,?,?)");
        $stmt->execute([$entity_id, $nama, $email, $pass, $role]);
    }
    header('Location: index.php?msg=saved');
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.*, e.name as entity_name
    FROM users u
    JOIN entity e ON u.entity_id = e.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();

$entities = $pdo->query("SELECT * FROM entity ORDER BY name ASC")->fetchAll();

require '../template/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0">Manajemen User</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">
    <i class="bi bi-plus-lg me-1"></i>Tambah User
  </button>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success py-2 small alert-dismissible fade show">Data berhasil disimpan.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr><th>Nama</th><th>Email</th><th>Role</th><th>Entity</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td class="fw-medium"><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge bg-<?= $u['role'] === 'super_admin' ? 'danger' : ($u['role'] === 'owner' ? 'warning' : ($u['role'] === 'admin' ? 'info' : 'secondary')) ?>"><?= $u['role'] ?></span></td>
            <td><small><?= htmlspecialchars($u['entity_name']) ?></small></td>
            <td>
              <?php if ($_SESSION['role'] === 'super_admin' || ($_SESSION['role'] === 'owner' && $u['entity_id'] == $_SESSION['entity_id'])): ?>
              <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?= $u['id'] ?>)"><i class="bi bi-pencil"></i></button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="id" id="userId" value="">
        <div class="modal-header">
          <h6 class="modal-title fw-bold" id="modalTitle">Tambah User</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label small">Nama</label>
            <input type="text" name="nama" id="fnama" class="form-control form-control-sm" required>
          </div>
          <div class="mb-2">
            <label class="form-label small">Email</label>
            <input type="email" name="email" id="femail" class="form-control form-control-sm" required>
          </div>
          <div class="mb-2">
            <label class="form-label small">Password <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
            <input type="password" name="password" class="form-control form-control-sm">
          </div>
          <div class="mb-2">
            <label class="form-label small">Role</label>
            <select name="role" id="frole" class="form-select form-select-sm">
              <option value="karyawan">Karyawan</option>
              <option value="admin">Admin</option>
              <option value="owner">Owner</option>
              <?php if ($_SESSION['role'] === 'super_admin'): ?>
              <option value="super_admin">Super Admin</option>
              <?php endif; ?>
            </select>
          </div>
          <?php if ($_SESSION['role'] === 'super_admin'): ?>
          <div class="mb-2">
            <label class="form-label small">Entity</label>
            <select name="entity_id" id="fentity_id" class="form-select form-select-sm">
              <?php foreach ($entities as $e): ?>
              <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button class="btn btn-sm btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const userData = <?= json_encode($users) ?>;
function editUser(id) {
  const u = userData.find(x => x.id == id);
  if (!u) return;
  document.getElementById('userId').value = u.id;
  document.getElementById('fnama').value = u.nama;
  document.getElementById('femail').value = u.email;
  document.getElementById('frole').value = u.role;
  const fe = document.getElementById('fentity_id');
  if (fe) fe.value = u.entity_id;
  document.getElementById('modalTitle').textContent = 'Edit User';
  new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>

<?php require '../template/footer.php'; ?>
