<?php
session_start();
require '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$nama = $_POST['nama'] ?? '';
if (!$nama) {
    http_response_code(400);
    echo json_encode(['error' => 'Nama barang wajib diisi']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO barang (entity_id, nama) VALUES (?,?)");
$stmt->execute([$_SESSION['entity_id'], $nama]);
$id = $pdo->lastInsertId();

echo json_encode(['id' => $id, 'nama' => $nama]);
