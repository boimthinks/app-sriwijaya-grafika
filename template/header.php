<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $title ?? 'Sistem Administratif' ?> - Sriwijaya Grafika</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <div class="d-flex" id="app">
    <!-- Sidebar -->
    <nav class="sidebar d-none d-md-flex flex-column flex-shrink-0" id="sidebar">
      <div class="sidebar-header d-flex align-items-center gap-2 px-3 py-3">
        <i class="bi bi-shield-shaded fs-4" style="color:var(--bs-primary)"></i>
        <span class="fw-bold fs-6">Sriwijaya Grafika</span>
      </div>
      <?php
      // Get all entities for switcher (super_admin can switch)
      try {
        $stmt_entities = $pdo->query("SELECT id, name, slug FROM entity ORDER BY name ASC");
        $all_entities = $stmt_entities->fetchAll();
      } catch (Exception $e) { $all_entities = []; }
      ?>
      <div class="entity-badge px-3 py-2 mb-2">
        <?php if ($_SESSION['role'] === 'super_admin' && count($all_entities) > 1): ?>
        <div class="dropdown">
          <button class="btn btn-warning-subtle text-warning-emphasis w-100 py-2 dropdown-toggle d-flex align-items-center justify-content-center gap-1" data-bs-toggle="dropdown" style="border:none;font-size:0.8rem">
            <i class="bi bi-building"></i>
            <span id="entityNameDisplay"><?= htmlspecialchars($_SESSION['entity_name'] ?? '') ?></span>
          </button>
          <ul class="dropdown-menu w-100">
            <?php foreach ($all_entities as $e): ?>
            <li>
              <a class="dropdown-item small <?= $e['id'] == $_SESSION['entity_id'] ? 'active' : '' ?>" href="#" onclick="switchEntity(<?= $e['id'] ?>, '<?= htmlspecialchars($e['name'], ENT_QUOTES) ?>');return false">
                <?= htmlspecialchars($e['name']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php else: ?>
        <span class="badge bg-warning-subtle text-warning-emphasis w-100 py-2">
          <i class="bi bi-building me-1"></i><?= htmlspecialchars($_SESSION['entity_name'] ?? '') ?>
        </span>
        <?php endif; ?>
      </div>
      <ul class="nav nav-pills flex-column mb-auto px-2">
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/proyek/index.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], '/proyek/') ? 'active' : '' ?>">
            <i class="bi bi-folder2 me-2"></i>Proyek
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/klien/index.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], '/klien/') ? 'active' : '' ?>">
            <i class="bi bi-people me-2"></i>Klien
          </a>
        </li>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/barang/index.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], '/barang/') ? 'active' : '' ?>">
            <i class="bi bi-box-seam me-2"></i>Barang
          </a>
        </li>
        <?php if (in_array($_SESSION['role'], ['super_admin', 'owner'])): ?>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/users/index.php" class="nav-link <?= str_contains($_SERVER['PHP_SELF'], '/users/') ? 'active' : '' ?>">
            <i class="bi bi-person-gear me-2"></i>Users
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
          <a href="<?= BASE_URL ?>/arsip.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'arsip.php' ? 'active' : '' ?>">
            <i class="bi bi-archive me-2"></i>Arsip
          </a>
        </li>
      </ul>
      <div class="sidebar-footer p-2 border-top">
        <a href="<?= BASE_URL ?>/logout.php" class="nav-link text-danger">
          <i class="bi bi-box-arrow-left me-2"></i>Logout
        </a>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 d-flex flex-column min-vh-100">
      <!-- Top Navbar -->
      <nav class="navbar navbar-expand-md navbar-light bg-white border-bottom px-3 py-2">
        <div class="container-fluid">
          <button class="btn btn-outline-secondary d-md-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav" aria-expanded="false">
            <i class="bi bi-list"></i>
          </button>
          <span class="navbar-brand fw-semibold fs-6 d-md-none"><?= $title ?? 'Dashboard' ?></span>
          <div class="ms-auto d-flex align-items-center gap-2">
            <span class="text-muted small d-none d-sm-inline">
              <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?>
            </span>
            <span class="badge bg-primary-subtle text-primary-emphasis">
              <?= htmlspecialchars($_SESSION['role'] ?? '') ?>
            </span>
          </div>
        </div>
      </nav>

      <!-- Mobile Navigation Collapse -->
      <div class="collapse d-md-none" id="mobileNav">
        <div class="bg-white border-bottom px-3 py-2">
          <?php if ($_SESSION['role'] === 'super_admin' && count($all_entities) > 1): ?>
          <div class="mb-2 px-2">
            <small class="text-muted">Entity:</small>
            <select class="form-select form-select-sm mt-1" onchange="switchEntity(this.value, this.options[this.selectedIndex].text)">
              <?php foreach ($all_entities as $e): ?>
              <option value="<?= $e['id'] ?>" <?= $e['id'] == $_SESSION['entity_id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php else: ?>
          <div class="mb-2 px-2">
            <small class="text-muted">Entity: <strong><?= htmlspecialchars($_SESSION['entity_name'] ?? '') ?></strong></small>
          </div>
          <?php endif; ?>
          <ul class="nav nav-pills flex-column">
            <li class="nav-item"><a href="<?= BASE_URL ?>/dashboard.php" class="nav-link"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
            <li class="nav-item"><a href="<?= BASE_URL ?>/proyek/index.php" class="nav-link"><i class="bi bi-folder2 me-2"></i>Proyek</a></li>
            <li class="nav-item"><a href="<?= BASE_URL ?>/klien/index.php" class="nav-link"><i class="bi bi-people me-2"></i>Klien</a></li>
            <li class="nav-item"><a href="<?= BASE_URL ?>/barang/index.php" class="nav-link"><i class="bi bi-box-seam me-2"></i>Barang</a></li>
            <?php if (in_array($_SESSION['role'], ['super_admin', 'owner'])): ?>
            <li class="nav-item"><a href="<?= BASE_URL ?>/users/index.php" class="nav-link"><i class="bi bi-person-gear me-2"></i>Users</a></li>
            <?php endif; ?>
            <li class="nav-item"><a href="<?= BASE_URL ?>/arsip.php" class="nav-link"><i class="bi bi-archive me-2"></i>Arsip</a></li>
            <li class="nav-item"><a href="<?= BASE_URL ?>/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
          </ul>
        </div>
      </div>

      <!-- Content -->
      <div class="p-3 p-lg-4 flex-grow-1">
