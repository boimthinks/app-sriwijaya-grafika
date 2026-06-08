<?php
session_start();
require '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$nama = $_POST['nama_perusahaan'] ?? '';
$alamat = $_POST['alamat'] ?? '';
$pic = $_POST['pic'] ?? '';
$no_telp = $_POST['no_telp'] ?? '';
$npwp = $_POST['npwp'] ?? '';

if (!$nama) {
    http_response_code(400);
    echo json_encode(['error' => 'Nama perusahaan wajib diisi']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO klien (entity_id, nama_perusahaan, alamat, pic, no_telp, npwp) VALUES (?,?,?,?,?,?)");
$stmt->execute([$_SESSION['entity_id'], $nama, $alamat, $pic, $no_telp, $npwp]);
$id = $pdo->lastInsertId();

echo json_encode(['id' => $id, 'nama_perusahaan' => $nama]);
