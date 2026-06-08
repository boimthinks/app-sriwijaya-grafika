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
$dp_persen = str_replace(',', '.', $_POST['dp_persen'] ?? '');
$dp_persen = (float)$dp_persen;

if ($proyek_id <= 0 || $dp_persen <= 0 || $dp_persen > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'DP persen harus antara 1 - 100']);
    exit;
}

$stmt = $pdo->prepare("UPDATE proyek SET dp_persen = ? WHERE id = ? AND entity_id = ?");
$stmt->execute([$dp_persen, $proyek_id, $_SESSION['entity_id']]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Proyek tidak ditemukan']);
    exit;
}

echo json_encode(['success' => true, 'dp_persen' => $dp_persen]);
