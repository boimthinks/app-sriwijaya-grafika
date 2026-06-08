<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$proyek_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT p.*, k.nama_perusahaan, k.alamat as klien_alamat, k.pic,
           e.name as entity_name, e.direktur, e.alamat as entity_alamat, e.no_telp as entity_telp
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
<title>BA Serah Terima - <?= htmlspecialchars($proyek['no_ba']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; font-size: 11pt; color: #000; padding: 30px 40px; }
.kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
.kop h2 { font-size: 16pt; margin-bottom: 2px; }
.kop p { font-size: 9pt; color: #333; }
h1 { text-align: center; font-size: 14pt; margin-bottom: 15px; text-decoration: underline; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; font-size: 10pt; }
th { background: #f0f0f0; font-weight: 600; text-align: center; }
.text-center { text-align: center; }
.info { margin: 10px 0; }
.info p { margin-bottom: 4px; }
.ttd { margin-top: 40px; display: flex; justify-content: space-between; }
.ttd .pihak { width: 45%; text-align: center; }
.ttd .pihak p { margin-bottom: 2px; }
.sign-line { margin-top: 60px; margin-bottom: 5px; }
.no-print { display: none; }
@media print { body { padding: 0; } }
</style>
</head>
<body>

<div class="kop">
  <h2>CV <?= htmlspecialchars($proyek['entity_name']) ?></h2>
  <p><?= htmlspecialchars($proyek['entity_alamat']) ?> | Telp. <?= htmlspecialchars($proyek['entity_telp']) ?></p>
</div>

<h1>BERITA ACARA SERAH TERIMA</h1>

<p style="text-align:center;margin-bottom:15px">
  <strong>No:</strong> <?= htmlspecialchars($proyek['no_ba'] ?: '-') ?>
</p>

<p style="text-align:justify;margin-bottom:10px">
  Pada hari ini, <?= date('d F Y') ?>, bertempat di Palembang, kami yang bertanda tangan di bawah ini:
</p>

<div class="info">
  <p><strong>Pihak Pertama (Pelaksana):</strong> <?= htmlspecialchars($proyek['entity_name']) ?></p>
  <p style="text-indent:30px">Dalam hal ini diwakili oleh <?= htmlspecialchars($proyek['direktur']) ?>, selaku Direktur, beralamat di <?= htmlspecialchars($proyek['entity_alamat']) ?>.</p>

  <p style="margin-top:8px"><strong>Pihak Kedua (Klien):</strong> <?= htmlspecialchars($proyek['nama_perusahaan']) ?></p>
  <p style="text-indent:30px">Dalam hal ini diwakili oleh <?= htmlspecialchars($proyek['pic'] ?: '-') ?>, selaku Direktur, beralamat di <?= htmlspecialchars($proyek['klien_alamat'] ?: '-') ?>.</p>
</div>

<p style="text-align:justify;margin-bottom:10px">
  Dengan ini menyatakan bahwa Pihak Pertama telah menyerahkan pekerjaan kepada Pihak Kedua berupa:
</p>

<table>
  <thead>
    <tr>
      <th style="width:30px">No</th>
      <th>Nama Pekerjaan / Barang</th>
      <th>Jumlah</th>
      <th>Keterangan</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $i => $item): ?>
    <tr>
      <td class="text-center"><?= $i+1 ?></td>
      <td><?= htmlspecialchars($item['barang_nama'] ?: 'Custom') ?></td>
      <td class="text-center"><?= $item['qty'] ?></td>
      <td><?= htmlspecialchars($item['keterangan'] ?: '-') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p style="text-align:justify;margin-bottom:10px">
  Pekerjaan tersebut telah selesai dilaksanakan dan diterima dengan baik oleh Pihak Kedua dalam keadaan lengkap dan baik.
</p>

<p style="text-align:justify;margin-bottom:10px">
  Demikian Berita Acara Serah Terima ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.
</p>

<div class="ttd">
  <div class="pihak">
    <p><strong>Pihak Pertama (Pelaksana)</strong></p>
    <p><?= htmlspecialchars($proyek['entity_name']) ?></p>
    <br><br>
    <p>(<?= htmlspecialchars($proyek['direktur']) ?>)</p>
    <p>Direktur</p>
  </div>
  <div class="pihak">
    <p><strong>Pihak Kedua (Klien)</strong></p>
    <p><?= htmlspecialchars($proyek['nama_perusahaan']) ?></p>
    <br><br>
    <p>(<?= htmlspecialchars($proyek['pic'] ?: '________') ?>)</p>
    <p>Direktur</p>
  </div>
</div>

<button onclick="window.print()" class="no-print" style="position:fixed;bottom:20px;right:20px;padding:10px 20px;background:#007bff;color:#fff;border:none;border-radius:5px;cursor:pointer;z-index:999">
  Cetak / Print
</button>

</body>
</html>
