<?php
session_start();
require '../config/database.php';
require '../config/functions.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);

// Fetch receipt details with client and entity information
$stmt = $pdo->prepare("
    SELECT pp.*, p.no_referensi, p.grand_total, k.nama_perusahaan, k.pic,
           e.name as entity_name, e.direktur, e.bank, e.atas_nama, e.no_rekening, e.alamat as entity_alamat, e.no_telp as entity_telp
    FROM proyek_pembayaran pp
    JOIN proyek p ON pp.proyek_id = p.id
    JOIN klien k ON p.klien_id = k.id
    JOIN entity e ON pp.entity_id = e.id
    WHERE pp.id = ? AND pp.entity_id = ?
");
$stmt->execute([$id, $_SESSION['entity_id']]);
$kuitansi = $stmt->fetch();

if (!$kuitansi) {
    echo "Kuitansi tidak ditemukan atau akses ditolak.";
    exit;
}

// Calculate total payments made up to this receipt to get current remaining balance
$stmt_total = $pdo->prepare("
    SELECT SUM(jumlah) 
    FROM proyek_pembayaran 
    WHERE proyek_id = ? AND id <= ?
");
$stmt_total->execute([$kuitansi['proyek_id'], $kuitansi['id']]);
$total_terbayar_saat_ini = (float)$stmt_total->fetchColumn();
$sisa_tagihan_saat_ini = max(0, $kuitansi['grand_total'] - $total_terbayar_saat_ini);

$is_lunas = ($sisa_tagihan_saat_ini <= 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Kuitansi Pembayaran - <?= htmlspecialchars($kuitansi['no_kuitansi']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Playfair+Display:ital,wght@1,600&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { 
  font-family: 'Inter', sans-serif; 
  font-size: 11pt; 
  color: #000; 
  padding: 40px;
  background: #fff;
}
.kuitansi-border {
  border: 4px double #333;
  padding: 30px;
  position: relative;
  background: #fff;
}
/* Kop Surat */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #333;
  padding-bottom: 15px;
  margin-bottom: 25px;
}
.logo-container {
  display: flex;
  align-items: center;
  gap: 15px;
}
.logo-img {
  height: 50px;
  width: 50px;
  object-fit: contain;
}
.entity-info h2 {
  font-size: 14pt;
  font-weight: 700;
  margin-bottom: 2px;
}
.entity-info p {
  font-size: 8.5pt;
  color: #555;
  line-height: 1.3;
}
.receipt-title {
  text-align: right;
}
.receipt-title h1 {
  font-size: 16pt;
  font-weight: 700;
  letter-spacing: 1px;
  text-decoration: underline;
  margin-bottom: 4px;
}
.receipt-title p {
  font-size: 11pt;
  font-weight: 600;
}

/* Receipt Details */
.row-detail {
  display: flex;
  margin-bottom: 15px;
  align-items: flex-start;
}
.label-detail {
  width: 200px;
  font-weight: 600;
  font-size: 10.5pt;
  flex-shrink: 0;
  position: relative;
}
.label-detail::after {
  content: ":";
  position: absolute;
  right: 15px;
}
.value-detail {
  flex-grow: 1;
  font-size: 11pt;
  border-bottom: 1px dashed #bbb;
  padding-bottom: 3px;
  min-height: 24px;
}
.value-detail.terbilang-box {
  background: #f8f9fa;
  border: 1px solid #ddd;
  padding: 8px 12px;
  font-family: 'Playfair Display', serif;
  font-style: italic;
  font-size: 12pt;
  border-radius: 4px;
}

/* Footer Section */
.footer {
  margin-top: 35px;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}
.amount-box {
  border: 2px solid #333;
  padding: 10px 25px;
  font-size: 16pt;
  font-weight: 700;
  background: #f1f3f5;
  display: inline-block;
  min-width: 200px;
  text-align: center;
  position: relative;
}
.amount-box::before, .amount-box::after {
  content: '';
  position: absolute;
  top: 3px; left: 3px; right: 3px; bottom: 3px;
  border: 1px dashed #777;
  pointer-events: none;
}
.meta-info {
  font-size: 9pt;
  color: #555;
  line-height: 1.5;
}
.signature-box {
  text-align: center;
  width: 250px;
}
.signature-box .date {
  margin-bottom: 60px;
}
.signature-box .name {
  font-weight: 700;
  text-decoration: underline;
}
.signature-box .role {
  font-size: 9.5pt;
  color: #555;
}

/* Action Bar for Non-Print */
.action-bar {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-bottom: 20px;
}
.btn {
  padding: 8px 16px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 9.5pt;
  cursor: pointer;
  text-decoration: none;
  border: 1px solid #ccc;
  background: #fff;
  color: #333;
  transition: all 0.2s ease;
}
.btn-primary {
  background: #F96C1A;
  color: #fff;
  border-color: #F96C1A;
}
.btn:hover {
  opacity: 0.9;
}

@media print {
  body { padding: 0; }
  .action-bar { display: none !important; }
  .kuitansi-border { border-color: #000; }
  .value-detail { border-bottom-color: #000; }
}
</style>
</head>
<body>

<div class="action-bar">
  <button onclick="window.history.back()" class="btn">Kembali</button>
  <button onclick="window.print()" class="btn btn-primary">Cetak Kuitansi</button>
</div>

<div class="kuitansi-border">
  <!-- Kop Surat -->
  <div class="header">
    <div class="logo-container">
      <img src="<?= BASE_URL ?>/img/<?= $kuitansi['entity_id'] == 2 ? 'workshop_sriwijaya.svg' : 'sriwijaya_grafika.svg' ?>" alt="Logo" class="logo-img">
      <div class="entity-info">
        <h2>CV. <?= htmlspecialchars($kuitansi['entity_name']) ?></h2>
        <p><?= htmlspecialchars($kuitansi['entity_alamat']) ?></p>
        <p>Telp. <?= htmlspecialchars($kuitansi['entity_telp']) ?></p>
      </div>
    </div>
    <div class="receipt-title">
      <h1>KUITANSI</h1>
      <p>No: <?= htmlspecialchars($kuitansi['no_kuitansi']) ?></p>
    </div>
  </div>

  <!-- Detail Pembayaran -->
  <div class="row-detail">
    <div class="label-detail">Telah diterima dari</div>
    <div class="value-detail" style="font-weight:700;">
      <?= htmlspecialchars($kuitansi['telah_diterima_dari']) ?> (<?= htmlspecialchars($kuitansi['nama_perusahaan']) ?>)
    </div>
  </div>

  <div class="row-detail" style="margin-bottom: 20px;">
    <div class="label-detail">Uang Sejumlah</div>
    <div class="value-detail terbilang-box">
      # <?= htmlspecialchars(terbilang($kuitansi['jumlah'])) ?> #
    </div>
  </div>

  <div class="row-detail">
    <div class="label-detail">Untuk Pembayaran</div>
    <div class="value-detail">
      <?= htmlspecialchars($kuitansi['keterangan'] ?: 'Pembayaran Proyek') ?>
      <?php if ($kuitansi['no_invoice']): ?>
        (Rujukan Invoice: <?= htmlspecialchars($kuitansi['no_invoice']) ?>)
      <?php endif; ?>
    </div>
  </div>

  <div class="row-detail">
    <div class="label-detail">Keterangan Sisa</div>
    <div class="value-detail" style="font-weight: 600;">
      <?php if ($is_lunas): ?>
        <span style="color: green; text-transform: uppercase;">LUNAS</span>
      <?php else: ?>
        Belum Lunas &mdash; Sisa Pembayaran: <?= rupiah($sisa_tagihan_saat_ini) ?> (dari Total Proyek: <?= rupiah($kuitansi['grand_total']) ?>)
      <?php endif; ?>
    </div>
  </div>

  <!-- Bagian Tanda Tangan & Nominal -->
  <div class="footer">
    <div>
      <div class="amount-box">
        Rp <?= number_format($kuitansi['jumlah'], 0, ',', '.') ?>,-
      </div>
      <div class="meta-info" style="margin-top: 15px;">
        <strong>Metode Pembayaran:</strong> <?= htmlspecialchars($kuitansi['metode_pembayaran']) ?><br>
        <strong>Rujukan Proyek ID:</strong> <code><?= htmlspecialchars($kuitansi['no_referensi']) ?></code>
      </div>
    </div>
    
    <div class="signature-box">
      <div class="date">Palembang, <?= date('d F Y', strtotime($kuitansi['tanggal'])) ?></div>
      <div class="name"><?= htmlspecialchars($kuitansi['direktur']) ?></div>
      <div class="role">Direktur CV. <?= htmlspecialchars($kuitansi['entity_name']) ?></div>
    </div>
  </div>
</div>

</body>
</html>
