<?php
session_start();
require '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$proyek_id = (int)($_POST['proyek_id'] ?? 0);
$stmt = $pdo->prepare("UPDATE proyek SET is_archived = NOT is_archived WHERE id = ? AND entity_id = ?");
$stmt->execute([$proyek_id, $_SESSION['entity_id']]);

echo json_encode(['success' => true]);
