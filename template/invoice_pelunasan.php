<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$proyek_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT p.*, k.nama_perusahaan, k.alamat as klien_alamat, k.npwp as klien_npwp,
           e.name as entity_name, e.direktur, e.bank, e.atas_nama, e.no_rekening, e.alamat as entity_alamat, e.no_telp as entity_telp
    FROM proyek p
    JOIN klien k ON p.klien_id = k.id
    JOIN entity e ON p.entity_id = e.id
    WHERE p.id = ? AND p.entity_id = ?
");
$stmt->execute([$proyek_id, $_SESSION['entity_id']]);
$proyek = $stmt->fetch();
if (!$proyek) { echo "Proyek tidak ditemukan"; exit; }

// Ambil tahap pertama dari payment stages sebagai DP
$stmt = $pdo->prepare("SELECT * FROM proyek_tahap_pembayaran WHERE proyek_id = ? ORDER BY urutan LIMIT 1");
$stmt->execute([$proyek_id]);
$tahap_pertama = $stmt->fetch();

if ($tahap_pertama) {
    $dp_persen = $tahap_pertama['persentase'];
} else {
    $dp_persen = $proyek['dp_persen'] ?: 50;
}
$dp_nominal = $proyek['grand_total'] * ($dp_persen / 100);
$sisa = $proyek['grand_total'] - $dp_nominal;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Invoice Pelunasan - <?= htmlspecialchars($proyek['no_inv_pelunasan']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Inter',sans-serif; font-size:11pt; color:#000; padding:30px 40px; }
.kop { text-align:center; border-bottom:3px double #000; padding-bottom:12px; margin-bottom:20px; }
.kop h2 { font-size:16pt; margin-bottom:2px; }
.kop p { font-size:9pt; color:#333; }
h1 { text-align:center; font-size:14pt; margin-bottom:15px; text-decoration:underline; }
table { width:100%; border-collapse:collapse; margin:10px 0; }
th, td { border:1px solid #000; padding:6px 8px; text-align:left; font-size:10pt; }
th { background:#f0f0f0; font-weight:600; text-align:center; }
.text-right { text-align:right; }
.text-center { text-align:center; }
.info-box { border:1px solid #000; padding:12px; margin:10px 0; }
.info-box p { margin-bottom:4px; }
.footer { margin-top:30px; display:flex; justify-content:space-between; }
.footer .left { width:45%; }
.footer .right { width:45%; text-align:center; }
.no-print { display:none; }
@media print { body { padding:0; } }
</style>
</head>
<body>

<div class="kop">
  <h2>CV <?= htmlspecialchars($proyek['entity_name']) ?></h2>
  <p><?= htmlspecialchars($proyek['entity_alamat']) ?> | Telp. <?= htmlspecialchars($proyek['entity_telp']) ?></p>
</div>

<h1>INVOICE PELUNASAN</h1>

<p style="margin-bottom:4px"><strong>No:</strong> <?= htmlspecialchars($proyek['no_inv_pelunasan'] ?: '-') ?></p>
<p style="margin-bottom:4px"><strong>ID Transaksi:</strong> <?= htmlspecialchars($proyek['no_referensi']) ?></p>
<p style="margin-bottom:4px"><strong>Kepada Yth:</strong> <?= htmlspecialchars($proyek['nama_perusahaan']) ?></p>
<p style="margin-bottom:4px"><strong>NPWP:</strong> <?= htmlspecialchars($proyek['klien_npwp'] ?: '-') ?></p>

<p style="margin-top:10px;margin-bottom:10px">
  Dengan hormat,<br>
  Mohon pembayaran <strong>pelunasan</strong> untuk pekerjaan sebagai berikut:
</p>

<div class="info-box">
  <p><strong>Pekerjaan:</strong> <?= htmlspecialchars($proyek['nama_perusahaan']) ?></p>
  <p><strong>No. Proforma Invoice:</strong> <?= htmlspecialchars($proyek['no_proforma'] ?: '-') ?></p>
  <p><strong>Nilai Proyek:</strong> <?= rupiah($proyek['grand_total']) ?></p>
  <p><strong>DP telah dibayar (<?= $dp_persen ?>%):</strong> <?= rupiah($dp_nominal) ?></p>
  <p><strong>Sisa Pelunasan:</strong> <?= rupiah($sisa) ?></p>
</div>

<p style="font-style:italic;margin-bottom:10px"># Terbilang: <?= terbilang($sisa) ?> #</p>

<table style="width:auto;margin-left:auto">
  <tr><td style="border:none;font-weight:600">Sisa Pelunasan</td><td style="border:none;text-align:right;font-weight:700;font-size:13pt"><?= rupiah($sisa) ?></td></tr>
</table>

<div class="footer">
  <div class="left">
    <p><strong>Transfer ke:</strong></p>
    <p>Bank: <?= htmlspecialchars($proyek['bank']) ?></p>
    <p>A/n: <?= htmlspecialchars($proyek['atas_nama']) ?></p>
    <p>No. Rek: <?= htmlspecialchars($proyek['no_rekening']) ?></p>
  </div>
  <div class="right">
    <p>Palembang, <?= date('d F Y') ?></p>
    <p><strong><?= htmlspecialchars($proyek['entity_name']) ?></strong></p>
    <br><br><br>
    <p>(<u><?= htmlspecialchars($proyek['direktur']) ?></u>)</p>
    <p>Direktur</p>
  </div>
</div>

<button onclick="window.print()" class="no-print" style="position:fixed;bottom:20px;right:20px;padding:10px 20px;background:#007bff;color:#fff;border:none;border-radius:5px;cursor:pointer;z-index:999">
  Cetak / Print
</button>

</body>
</html>
