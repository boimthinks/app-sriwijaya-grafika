<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$proyek_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT p.*, k.nama_perusahaan, k.alamat as klien_alamat, k.npwp as klien_npwp, k.pic,
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

// Separate by kategori
$items_barang = array_filter($items, fn($i) => $i['kategori'] === 'barang');
$items_pekerjaan = array_filter($items, fn($i) => $i['kategori'] === 'pekerjaan');

function hitungKategori($items, $diskon_persen, $ppn_persen) {
    $sub_total = array_sum(array_map(fn($i) => $i['jumlah'], $items));
    $diskon = $sub_total * ($diskon_persen / 100);
    $after_diskon = $sub_total - $diskon;
    $ppn = $after_diskon * ($ppn_persen / 100);
    $total = $after_diskon + $ppn;
    return compact('sub_total', 'diskon', 'after_diskon', 'ppn', 'total');
}

$hA = hitungKategori($items_barang, $proyek['diskon_persen'] ?: 0, $proyek['ppn_persen'] ?: 0);
$hB = hitungKategori($items_pekerjaan, $proyek['diskon_persen'] ?: 0, $proyek['ppn_persen'] ?: 0);
$grand_total = $hA['total'] + $hB['total'];

// Payment stages
$stmt = $pdo->prepare("SELECT * FROM proyek_tahap_pembayaran WHERE proyek_id = ? ORDER BY urutan");
$stmt->execute([$proyek_id]);
$tahap_pembayaran = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Surat Kesepakatan - <?= htmlspecialchars($proyek['no_sk']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; font-size: 11pt; color: #000; padding: 30px 40px; }
.kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
.kop h2 { font-size: 16pt; margin-bottom: 2px; }
.kop p { font-size: 9pt; color: #333; }
h1 { text-align: center; font-size: 14pt; margin-bottom: 15px; text-decoration: underline; }
table { width: 100%; border-collapse: collapse; margin: 8px 0; }
th, td { border: 1px solid #000; padding: 5px 8px; text-align: left; font-size: 10pt; }
th { background: #f0f0f0; font-weight: 600; text-align: center; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.pasal { margin: 20px 0; }
.pasal h3 { font-size: 11pt; margin-bottom: 10px; }
.pasal p { margin-bottom: 6px; text-align: justify; }
.ttd { margin-top: 40px; display: flex; justify-content: space-between; }
.ttd .pihak { width: 45%; text-align: center; }
.ttd .pihak p { margin-bottom: 2px; }
.sign-line { margin-top: 60px; margin-bottom: 5px; }
.no-print { display: none; }
@media print { body { padding: 0; } }
.sub-section { margin: 12px 0 16px 15px; }
.sub-section h4 { font-size: 10pt; margin-bottom: 8px; }
</style>
</head>
<body>

<div class="kop">
  <h2>CV <?= htmlspecialchars($proyek['entity_name']) ?></h2>
  <p><?= htmlspecialchars($proyek['entity_alamat']) ?> | Telp. <?= htmlspecialchars($proyek['entity_telp']) ?></p>
</div>

<h1>SURAT KESEPAKATAN</h1>

<p class="text-center" style="margin-bottom:10px">
  <strong>No:</strong> <?= htmlspecialchars($proyek['no_sk'] ?: '-') ?>
</p>

<?php
$bln = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$hari = (int)date('d');
$bulan = $bln[(int)date('m') - 1];
$tahun = date('Y');
?>
<p style="text-align:justify;margin-bottom:10px">
  Pada hari ini, <?= sprintf('%02d', $hari) ?> <?= $bulan ?> <?= $tahun ?> (<?= strtolower(trim(_terbilang($hari))) ?> <?= strtolower($bulan) ?> <?= strtolower(trim(_terbilang($tahun))) ?>), bertempat di Palembang, kami yang bertanda tangan di bawah ini:
</p>

<div class="pasal">
  <h3>IDENTITAS PIHAK</h3>

  <p><strong>Pihak Pertama (Klien):</strong></p>
  <table style="width:80%">
    <tr><td style="width:150px">Nama</td><td>: <?= htmlspecialchars($proyek['pic'] ?: '-') ?></td></tr>
    <tr><td>Jabatan</td><td>: Direktur</td></tr>
    <tr><td>Alamat</td><td>: <?= htmlspecialchars($proyek['klien_alamat'] ?: '-') ?></td></tr>
    <tr><td>Bertindak atas nama</td><td>: <?= htmlspecialchars($proyek['nama_perusahaan']) ?></td></tr>
  </table>

  <p style="margin-top:8px"><strong>Pihak Kedua (Pelaksana):</strong></p>
  <table style="width:80%">
    <tr><td style="width:150px">Nama</td><td>: <?= htmlspecialchars($proyek['direktur']) ?></td></tr>
    <tr><td>Jabatan</td><td>: Direktur</td></tr>
    <tr><td>Alamat</td><td>: <?= htmlspecialchars($proyek['entity_alamat']) ?></td></tr>
    <tr><td>Bertindak atas nama</td><td>: <?= htmlspecialchars($proyek['entity_name']) ?></td></tr>
  </table>
</div>

<div class="pasal">
  <h3>PASAL 1: DASAR KESEPAKATAN</h3>
  <p>Kesepakatan ini berdasarkan:</p>
  <p>1. Surat Penawaran No. <?= htmlspecialchars($proyek['no_sp'] ?: '-') ?></p>
  <p>2. Surat Pengajuan Negosiasi Pembayaran</p>
  <p>3. Purchase Order (PO) dari Pihak Pertama</p>
</div>

<div class="pasal">
  <h3>PASAL 2: OBJEK PERJANJIAN</h3>
  <p>Pihak Pertama memesan barang/jasa kepada Pihak Kedua, dan Pihak Kedua menyanggupi untuk menyediakan barang/jasa tersebut sesuai dengan spesifikasi yang tercantum dalam perjanjian ini.</p>
</div>

<div class="pasal">
  <h3>PASAL 3: RINCIAN PESANAN, HARGA DAN BIAYA</h3>

  <?php if ($items_barang): ?>
  <div class="sub-section">
    <h4>A. Barang (Produk Fisik)</h4>
    <table>
      <thead>
        <tr><th>No</th><th>Nama Barang / Spesifikasi</th><th>Harga Satuan</th><th>Qty</th><th>Jumlah</th></tr>
      </thead>
      <tbody>
        <?php $n=0; foreach ($items_barang as $item): $n++; ?>
        <tr>
          <td class="text-center"><?= $n ?></td>
          <td><?= htmlspecialchars($item['barang_nama'] ?: 'Custom') ?><?= $item['keterangan'] ? '<br><small>'.nl2br(htmlspecialchars($item['keterangan'])).'</small>' : '' ?></td>
          <td class="text-right"><?= rupiah($item['harga']) ?></td>
          <td class="text-center"><?= $item['qty'] ?></td>
          <td class="text-right"><?= rupiah($item['jumlah']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p>Sub Total A: <?= rupiah($hA['sub_total']) ?><?php if ($hA['diskon'] > 0): ?> | Diskon <?= $proyek['diskon_persen'] ?>%: -<?= rupiah($hA['diskon']) ?> | Total Setelah Diskon: <?= rupiah($hA['after_diskon']) ?><?php endif; ?> | PPN <?= $proyek['ppn_persen'] ?>%: <?= rupiah($hA['ppn']) ?> | <strong>Total A: <?= rupiah($hA['total']) ?></strong></p>
  </div>
  <?php endif; ?>

  <?php if ($items_pekerjaan): ?>
  <div class="sub-section">
    <h4>B. Jasa</h4>
    <table>
      <thead>
        <tr><th>No</th><th>Nama Jasa</th><th>Harga Satuan</th><th>Qty</th><th>Jumlah</th></tr>
      </thead>
      <tbody>
        <?php $n=0; foreach ($items_pekerjaan as $item): $n++; ?>
        <tr>
          <td class="text-center"><?= $n ?></td>
          <td><?= htmlspecialchars($item['barang_nama'] ?: 'Custom') ?><?= $item['keterangan'] ? '<br><small>'.nl2br(htmlspecialchars($item['keterangan'])).'</small>' : '' ?></td>
          <td class="text-right"><?= rupiah($item['harga']) ?></td>
          <td class="text-center"><?= $item['qty'] ?></td>
          <td class="text-right"><?= rupiah($item['jumlah']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p>Sub Total B: <?= rupiah($hB['sub_total']) ?><?php if ($hB['diskon'] > 0): ?> | Diskon <?= $proyek['diskon_persen'] ?>%: -<?= rupiah($hB['diskon']) ?> | Total Setelah Diskon: <?= rupiah($hB['after_diskon']) ?><?php endif; ?> | PPN <?= $proyek['ppn_persen'] ?>%: <?= rupiah($hB['ppn']) ?> | <strong>Total B: <?= rupiah($hB['total']) ?></strong></p>
  </div>
  <?php endif; ?>

  <p><strong>Grand Total: <?= rupiah($grand_total) ?></strong></p>
  <p><em># <?= terbilang($grand_total) ?> #</em></p>
</div>

<div class="pasal">
  <h3>PASAL 4: KETENTUAN PEMBAYARAN</h3>
  <p>Pembayaran dilakukan secara bertahap sebagai berikut:</p>
  <table>
    <thead><tr><th>Tahap</th><th>Jumlah</th><th>Keterangan</th></tr></thead>
    <tbody>
      <?php if ($tahap_pembayaran): $n=0; foreach ($tahap_pembayaran as $tp): $n++;
        $nominal = $proyek['grand_total'] * $tp['persentase'] / 100;
      ?>
      <tr><td class="text-center"><?= $n ?></td><td class="text-right"><?= rupiah($nominal) ?></td><td><?= htmlspecialchars($tp['deskripsi']) ?></td></tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  <p style="margin-top:6px">Pembayaran transfer ke:</p>
  <p>Bank: <?= htmlspecialchars($proyek['bank']) ?><br>
  A/n: <?= htmlspecialchars($proyek['atas_nama']) ?><br>
  No. Rek: <?= htmlspecialchars($proyek['no_rekening']) ?></p>
</div>

<div class="pasal">
  <h3>PASAL 5: WAKTU PELAKSANAAN</h3>
  <p>Waktu pelaksanaan jasa adalah <?= (int)$proyek['waktu_pelaksanaan_hari'] ?> (<?= strtolower(trim(_terbilang((int)$proyek['waktu_pelaksanaan_hari']))) ?>) hari kalender setelah DP diterima. Apabila terjadi perubahan waktu, akan diberitahukan secara tertulis.</p>
</div>

<div class="pasal">
  <h3>PASAL 6: HAK DAN KEWAJIBAN PIHAK PERTAMA</h3>
  <p>Pihak Pertama berkewajiban membayar tepat waktu sesuai ketentuan Pasal 4, memberikan akses informasi yang diperlukan, dan menerima hasil jasa sesuai spesifikasi yang disepakati.</p>
</div>

<div class="pasal">
  <h3>PASAL 7: HAK DAN KEWAJIBAN PIHAK KEDUA</h3>
  <p>Pihak Kedua berkewajiban mengerjakan sesuai spesifikasi yang disepakati, melaporkan progres jasa secara berkala, dan menyelesaikan jasa tepat waktu.</p>
</div>

<div class="pasal">
  <h3>PASAL 8: FORCE MAJEURE</h3>
  <p>Pihak yang mengalami force majeure (bencana alam, perang, kebakaran, demonstrasi, pandemi, atau kebijakan pemerintah) wajib memberitahukan secara tertulis dalam waktu 3×24 jam dan tidak dapat dituntut ganti rugi.</p>
</div>

<div class="pasal">
  <h3>PASAL 9: PENYELESAIAN SENGKETA</h3>
  <p>Apabila terjadi perselisihan, kedua belah pihak sepakat untuk menyelesaikannya secara musyawarah terlebih dahulu. Jika tidak tercapai kesepakatan, sengketa akan diselesaikan berdasarkan hukum yang berlaku di Indonesia.</p>
</div>

<div class="pasal">
  <h3>PASAL 10: LAIN-LAIN</h3>
  <p>Surat perjanjian ini dibuat dalam rangkap 2 (dua) yang masing-masing mempunyai kekuatan hukum yang sama dan berlaku sejak ditandatangani kedua belah pihak.</p>
</div>

<div class="ttd">
  <div class="pihak">
    <p><strong>Pihak Pertama (Klien)</strong></p>
    <p><?= htmlspecialchars($proyek['pic'] ?: '-') ?></p>
    <p>Direktur</p>
    <p><?= htmlspecialchars($proyek['nama_perusahaan']) ?></p>
    <div class="sign-line">_______________</div>
  </div>
  <div class="pihak">
    <p><strong>Pihak Kedua (Pelaksana)</strong></p>
    <p><?= htmlspecialchars($proyek['direktur']) ?></p>
    <p>Direktur</p>
    <p><?= htmlspecialchars($proyek['entity_name']) ?></p>
    <div class="sign-line">_______________</div>
  </div>
</div>

<button onclick="window.print()" class="no-print" style="position:fixed;bottom:20px;right:20px;padding:10px 20px;background:#007bff;color:#fff;border:none;border-radius:5px;cursor:pointer;z-index:999">
  Cetak / Print
</button>

</body>
</html>
