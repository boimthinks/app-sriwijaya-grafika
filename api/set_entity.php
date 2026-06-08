<?php
session_start();
require '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$entity_id = (int)($_POST['entity_id'] ?? 0);
if (!$entity_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Entity ID required']);
    exit;
}

// super_admin can switch to any entity; others only their own
if ($_SESSION['role'] !== 'super_admin' && $entity_id != $_SESSION['entity_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, slug FROM entity WHERE id = ?");
$stmt->execute([$entity_id]);
$entity = $stmt->fetch();

if (!$entity) {
    http_response_code(404);
    echo json_encode(['error' => 'Entity not found']);
    exit;
}

$_SESSION['entity_id'] = $entity['id'];
$_SESSION['entity_name'] = $entity['name'];
$_SESSION['entity_slug'] = $entity['slug'];

echo json_encode(['success' => true, 'entity' => $entity]);
