<?php
define('BASE_URL', '/swgrafika');

function generateNoReferensi(): string {
    return str_pad(random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
}

function getNextNoSurat(PDO $pdo, int $entity_id, string $jenis): string {
    $tahun = date('Y');
    $stmt = $pdo->prepare("
        INSERT INTO counter_dokumen (entity_id, jenis, tahun, nomor_terakhir)
        VALUES (?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE nomor_terakhir = nomor_terakhir + 1
    ");
    $stmt->execute([$entity_id, $jenis, $tahun]);

    $stmt = $pdo->prepare("
        SELECT nomor_terakhir FROM counter_dokumen
        WHERE entity_id = ? AND jenis = ? AND tahun = ?
    ");
    $stmt->execute([$entity_id, $jenis, $tahun]);
    $row = $stmt->fetch();
    $no = str_pad($row['nomor_terakhir'], 3, '0', STR_PAD_LEFT);

    $prefix_map = [
        'sp' => 'SP', 'sk' => 'SK', 'proforma' => 'PROFORMA',
        'inv_dp' => 'INV/DP', 'inv_pelunasan' => 'INV/LUNAS',
        'sj' => 'SJ', 'ba' => 'BA',
    ];
    $prefix = $prefix_map[$jenis] ?? strtoupper($jenis);
    return "$prefix-$no";
}

function rupiah($angka): string {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function _terbilang(int $angka): string {
    $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
    if ($angka < 12) return $huruf[$angka];
    if ($angka < 20) return _terbilang($angka - 10) . ' Belas';
    if ($angka < 100) return _terbilang((int)($angka / 10)) . ' Puluh ' . _terbilang($angka % 10);
    if ($angka < 200) return 'Seratus ' . _terbilang($angka - 100);
    if ($angka < 1000) return _terbilang((int)($angka / 100)) . ' Ratus ' . _terbilang($angka % 100);
    if ($angka < 2000) return 'Seribu ' . _terbilang($angka - 1000);
    if ($angka < 1000000) return _terbilang((int)($angka / 1000)) . ' Ribu ' . _terbilang($angka % 1000);
    if ($angka < 1000000000) return _terbilang((int)($angka / 1000000)) . ' Juta ' . _terbilang($angka % 1000000);
    if ($angka < 1000000000000) return _terbilang((int)($angka / 1000000000)) . ' Miliar ' . _terbilang($angka % 1000000000);
    return _terbilang((int)($angka / 1000000000000)) . ' Triliun ' . _terbilang($angka % 1000000000000);
}

function terbilang($angka): string {
    return trim(_terbilang((int)abs((float)$angka))) . ' Rupiah';
}

function isLogin(): bool {
    return isset($_SESSION['user_id']);
}

function cekLogin(): void {
    if (!isLogin()) {
        header('Location: login.php');
        exit;
    }
}

function cekRole(array $roles): void {
    if (!in_array($_SESSION['role'], $roles)) {
        echo "<script>alert('Akses ditolak!'); window.location.href='dashboard.php';</script>";
        exit;
    }
}
