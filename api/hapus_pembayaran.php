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

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
}

// Verify payment exists and belongs to active entity
$stmt = $pdo->prepare("
    SELECT pp.id 
    FROM proyek_pembayaran pp
    JOIN proyek p ON pp.proyek_id = p.id
    WHERE pp.id = ? AND p.entity_id = ?
");
$stmt->execute([$id, $_SESSION['entity_id']]);
$pembayaran = $stmt->fetch();

if (!$pembayaran) {
    http_response_code(404);
    echo json_encode(['error' => 'Pembayaran tidak ditemukan atau tidak sah']);
    exit;
}

try {
    $stmt_delete = $pdo->prepare("DELETE FROM proyek_pembayaran WHERE id = ?");
    $stmt_delete->execute([$id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menghapus pembayaran: ' . $e->getMessage()]);
}
