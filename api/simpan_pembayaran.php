<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$proyek_id = (int)($_POST['proyek_id'] ?? 0);
$telah_diterima_dari = trim($_POST['telah_diterima_dari'] ?? '');
$jumlah = round((float)($_POST['jumlah'] ?? 0));
$tanggal = $_POST['tanggal'] ?? '';
$metode_pembayaran = trim($_POST['metode_pembayaran'] ?? '');
$no_invoice = trim($_POST['no_invoice'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

if ($proyek_id <= 0 || !$telah_diterima_dari || $jumlah <= 0 || !$tanggal || !$metode_pembayaran) {
    http_response_code(400);
    echo json_encode(['error' => 'Semua data wajib diisi dengan benar']);
    exit;
}

// Verify project ownership and get active entity_id
$stmt = $pdo->prepare("SELECT id, entity_id FROM proyek WHERE id = ? AND entity_id = ?");
$stmt->execute([$proyek_id, $_SESSION['entity_id']]);
$proyek = $stmt->fetch();

if (!$proyek) {
    http_response_code(404);
    echo json_encode(['error' => 'Proyek tidak ditemukan']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Auto-generate receipt number (prefix 'kw' results in 'KW-xxx')
    $no_kuitansi = getNextNoSurat($pdo, $proyek['entity_id'], 'kw');

    $stmt_insert = $pdo->prepare("
        INSERT INTO proyek_pembayaran (entity_id, proyek_id, no_kuitansi, no_invoice, telah_diterima_dari, jumlah, tanggal, metode_pembayaran, keterangan)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_insert->execute([
        $proyek['entity_id'],
        $proyek_id,
        $no_kuitansi,
        $no_invoice ?: null,
        $telah_diterima_dari,
        $jumlah,
        $tanggal,
        $metode_pembayaran,
        $keterangan ?: null
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'no_kuitansi' => $no_kuitansi]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mencatat pembayaran: ' . $e->getMessage()]);
}
