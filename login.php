<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'config/database.php';

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("
        SELECT u.*, e.name as entity_name, e.slug as entity_slug
        FROM users u
        JOIN entity e ON u.entity_id = e.id
        WHERE u.email = ? AND u.active = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nama'] = $user['name'];
        $_SESSION['entity_id'] = $user['entity_id'];
        $_SESSION['entity_name'] = $user['entity_name'];
        $_SESSION['entity_slug'] = $user['entity_slug'];
        $_SESSION['role'] = $user['role'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Email atau password salah';
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Sriwijaya Grafika</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card shadow-sm border-0" style="max-width:420px;width:100%">
      <div class="card-body p-4 p-lg-5">
        <div class="text-center mb-4">
          <i class="bi bi-shield-shaded display-4" style="color:var(--bs-primary)"></i>
          <h4 class="fw-bold mt-2">Sriwijaya Grafika</h4>
          <p class="text-muted small">Sistem Administratif</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= $error ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label class="form-label small fw-medium">Email</label>
            <input type="email" name="email" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-medium">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-medium">Masuk</button>
        </form>

        <p class="text-center text-muted small mt-4 mb-0">
          Demo: admin@sriwijayagrafika.com / admin123
        </p>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
