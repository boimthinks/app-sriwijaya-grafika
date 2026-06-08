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

$stmt = $pdo->prepare("
    SELECT pi.*, b.nama as barang_nama
    FROM proyek_item pi
    LEFT JOIN barang b ON pi.barang_id = b.id
    WHERE pi.proyek_id = ? ORDER BY pi.no_urut
");
$stmt->execute([$proyek_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Proforma Invoice - <?= htmlspecialchars($proyek['no_proforma']) ?></title>
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
.total-table { width:auto; margin-left:auto; }
.total-table td { border:none; padding:3px 12px; text-align:right; }
.total-table td:first-child { text-align:left; font-weight:600; }
.grand-total { font-size:13pt; font-weight:700; }
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

<h1>PROFORMA INVOICE</h1>

<p style="margin-bottom:4px"><strong>No:</strong> <?= htmlspecialchars($proyek['no_proforma'] ?: '-') ?></p>
<p style="margin-bottom:10px"><strong>Kepada Yth:</strong> <?= htmlspecialchars($proyek['nama_perusahaan']) ?></p>

<table>
  <thead>
    <tr>
      <th style="width:30px">No</th>
      <th>Nama Barang / Jasa / Spesifikasi</th>
      <th style="width:110px">Harga Satuan</th>
      <th style="width:45px">Qty</th>
      <th style="width:110px">Jumlah</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $item): ?>
    <tr>
      <td class="text-center"><?= $item['no_urut'] ?></td>
      <td>
        <strong><?= htmlspecialchars($item['barang_nama'] ?: 'Custom') ?></strong>
        <?php if ($item['keterangan']): ?><br><small><?= nl2br(htmlspecialchars($item['keterangan'])) ?></small><?php endif; ?>
      </td>
      <td class="text-right"><?= rupiah($item['harga']) ?></td>
      <td class="text-center"><?= $item['qty'] ?></td>
      <td class="text-right"><?= rupiah($item['jumlah']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<table class="total-table">
  <tr><td>Sub Total</td><td><?= rupiah($proyek['sub_total']) ?></td></tr>
  <tr><td>Diskon (<?= $proyek['diskon_persen'] ?>%)</td><td style="color:red">- <?= rupiah($proyek['sub_total'] - $proyek['dpp']) ?></td></tr>
  <tr><td>DPP</td><td><?= rupiah($proyek['dpp']) ?></td></tr>
  <tr><td>PPN (<?= $proyek['ppn_persen'] ?>%)</td><td><?= rupiah($proyek['grand_total'] - $proyek['dpp']) ?></td></tr>
  <tr><td class="grand-total">Grand Total</td><td class="grand-total"><?= rupiah($proyek['grand_total']) ?></td></tr>
</table>

<div style="font-style:italic;margin-top:10px"># <?= terbilang($proyek['grand_total']) ?> #</div>

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
