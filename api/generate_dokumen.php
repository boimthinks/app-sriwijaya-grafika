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
$jenis = $_POST['jenis'] ?? ''; // sp, sk, proforma, inv_dp, inv_pelunasan, sj, ba

$valid = ['sp', 'sk', 'proforma', 'inv_dp', 'inv_pelunasan', 'sj', 'ba'];
if (!in_array($jenis, $valid)) {
    http_response_code(400);
    echo json_encode(['error' => 'Jenis dokumen tidak valid']);
    exit;
}

// Cek proyek
$stmt = $pdo->prepare("SELECT * FROM proyek WHERE id = ? AND entity_id = ?");
$stmt->execute([$proyek_id, $_SESSION['entity_id']]);
$proyek = $stmt->fetch();

if (!$proyek) {
    http_response_code(404);
    echo json_encode(['error' => 'Proyek tidak ditemukan']);
    exit;
}

// Cek apakah sudah ada
$kolom = "no_$jenis";
if ($proyek[$kolom]) {
    echo json_encode(['no_surat' => $proyek[$kolom], 'exists' => true]);
    exit;
}

// Generate nomor
$no_surat = getNextNoSurat($pdo, $_SESSION['entity_id'], $jenis);

$stmt = $pdo->prepare("UPDATE proyek SET $kolom = ? WHERE id = ?");
$stmt->execute([$no_surat, $proyek_id]);

echo json_encode(['no_surat' => $no_surat, 'exists' => false]);
