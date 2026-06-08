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
$tahap_json = $_POST['tahap'] ?? '[]';
$tahap = json_decode($tahap_json, true);

if ($proyek_id <= 0 || !is_array($tahap)) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak valid']);
    exit;
}

// Verify project ownership
$stmt = $pdo->prepare("SELECT id FROM proyek WHERE id = ? AND entity_id = ?");
$stmt->execute([$proyek_id, $_SESSION['entity_id']]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Proyek tidak ditemukan']);
    exit;
}

$pdo->beginTransaction();
try {
    // Delete existing stages
    $pdo->prepare("DELETE FROM proyek_tahap_pembayaran WHERE proyek_id = ?")->execute([$proyek_id]);

    // Insert new stages
    $insert = $pdo->prepare("INSERT INTO proyek_tahap_pembayaran (proyek_id, urutan, persentase, deskripsi) VALUES (?, ?, ?, ?)");
    $urutan = 1;
    foreach ($tahap as $t) {
        $persentase = str_replace(',', '.', $t['persentase'] ?? '0');
        $persentase = (float)$persentase;
        $deskripsi = trim($t['deskripsi'] ?? '');
        if ($persentase <= 0 || !$deskripsi) continue;
        $insert->execute([$proyek_id, $urutan++, $persentase, $deskripsi]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menyimpan: ' . $e->getMessage()]);
}
