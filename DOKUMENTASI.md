# Dokumentasi Aplikasi Sriwijaya Grafika

## 1. Tentang Aplikasi

Aplikasi **Sriwijaya Grafika** adalah sistem administratif berbasis web untuk perusahaan jasa pembuatan media promosi advertising, reklame, neon box, huruf timbul, dan cutting laser.

**Tujuan:** Mengelola proyek dari awal (surat penawaran) hingga selesai (berita acara serah terima) dalam satu tempat, dengan cetak PDF untuk setiap dokumen.

---

## 2. Dua Entity (Perusahaan)

Dimiliki oleh 1 orang yang sama, tapi melayani 2 jenis klien:

| Entity | PPN | Untuk Klien |
|--------|:---:|-------------|
| **Sriwijaya Grafika** | 11% | Klien yang butuh faktur pajak resmi |
| **Workshop Sriwijaya** | 0% | Klien yang tidak ingin kena pajak |

### Data Entity

| Field | Sriwijaya Grafika | Workshop Sriwijaya |
|-------|-------------------|-------------------|
| Direktur | M. Edy Munandar | Wenni Septiyana |
| Bank | BCA a/n Sriwijaya Grafika, CV | BCA a/n Wenni Septiyana |
| No Rekening | 114-0299100 | 0212599130 |
| Alamat | Jl. Pertanian No. 105 Talang Jambe, Palembang | (sama) |
| No Telp | 0852-15111125 | (sama) |

---

## 3. Konsep Utama: 1 Proyek → 7 Dokumen

**BUKAN** 7 kali input data terpisah. Semua dokumen bersumber dari **1 data proyek yang sama**.

### Alur:

```
Proyek Baru
  ├── Input klien (search database / add baru)
  ├── Input item barang & jasa (search database / add baru)
  │     └── Setiap item: nama_barang + keterangan (custom) + harga (custom) + qty
  └── Auto-calc: jumlah, sub_total, diskon, DPP, PPN, grand_total
        │
        ▼
  1 Data Sumber ──── bisa cetak ke ──── 7 Dokumen:

  [Cetak] Surat Penawaran       → SP-001, SP-002...
  [Cetak] Surat Kesepakatan     → SK-001, SK-002...
  [Cetak] Proforma Invoice      → PF-001, PF-002...
  [Cetak] Invoice DP            → INV/DP-001...
  [Cetak] Invoice Pelunasan     → INV/LUNAS-001...
  [Cetak] Surat Jalan           → SJ-001, SJ-002...
  [Cetak] BA Serah Terima       → BA-001, BA-002...
```

### Urutan Dokumen (Sequential Unlock)

Dokumen muncul bertahap — dokumen berikutnya hanya bisa dibuat jika dokumen sebelumnya sudah ada:

```
SP → SK → Proforma → (INV DP, INV Pelunasan, SJ, BA)
                      ↑ keempatnya muncul setelah Proforma
```

### Perbedaan Template

| Template | Kompleksitas | Keterangan |
|----------|:-----------:|------------|
| Surat Penawaran | Ringkas | Tabel item + total harga |
| Surat Kesepakatan | Kompleks | Kontrak hukum dengan pasal-pasal, dua kategori (Barang & Jasa) |
| Proforma Invoice | Ringkas | Tagihan awal sebelum DP/Lunas |
| Invoice DP | Ringkas | Tagihan DP |
| Invoice Pelunasan | Ringkas | Tagihan pelunasan |
| Surat Jalan | Sederhana | Hanya nama barang + jumlah |
| BA Serah Terima | Cukup kompleks | Surat pernyataan dengan tanda tangan |

Setiap dokumen mendapat **nomor surat** auto-increment per entity per tahun (reset tiap tahun).

---

## 4. Fitur

- **Auth**: Login/logout dengan session, multi-role (super_admin, owner, admin, karyawan)
- **Dashboard**: Statistik (total proyek, klien, revenue, bulan ini) + **Indeks Penyelesaian** (Fear & Greed style gauge 0-100: persentase penawaran → penyelesaian)
- **CRUD Proyek**: Buat, edit, detail, arsip — auto-calc sub_total, diskon, DPP, PPN, grand_total
- **CRUD Klien**: Master data klien per entity
- **CRUD Barang**: Master data barang/jasa per entity
- **CRUD Users**: Manajemen user (super_admin/owner only)
- **7 Template Cetak**: Masing-masing dengan nomor dokumen auto-increment
- **Entity Switcher**: Super Admin bisa ganti entity via dropdown sidebar
- **Search**: Cari proyek, klien, barang
- **Doc Map View**: Card view dengan visual pipeline dokumen (SP → SK → Proforma → INV DP → INV Lunas → SJ → BA)
- **Auto-add Barang**: Ketik nama barang baru di form proyek → otomatis masuk database
- **Klien Baru Modal**: Tambah klien baru dari form proyek tanpa pindah halaman
- **Tahap Pembayaran**: Atur persentase & deskripsi tiap tahap (di edit proyek), live preview nominal, auto-hitungan sisa
- **Arsip Proyek**: Soft delete proyek (toggle arsip), tampil di halaman arsip terpisah
- **Riwayat Pembayaran** _(rencana)_: Catat pembayaran klien per proyek — lihat section 13

---

## 5. Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Frontend | PHP Native + Bootstrap 5.3.3 + Bootstrap Icons |
| Font | Inter (Google Fonts) |
| Backend | PHP 8.1 (PDO, session-based auth) |
| Database | MySQL 8.0 |
| Server | Apache 2.4 (via Laragon) |
| PDF | Browser `window.print()` |
| ID Transaksi | Random 10 digit numeric (`random_int`) |

---

## 6. Struktur Project

```
swgrafika/
├── index.php                   # Redirect ke dashboard
├── login.php                   # Halaman login
├── logout.php                  # Logout
├── dashboard.php               # Dashboard + Indeks Penyelesaian
├── arsip.php                   # Daftar proyek yang diarsipkan
├── config/
│   ├── database.php            # Koneksi PDO MySQL
│   └── functions.php           # Helper: rupiah(), terbilang(), getNextNoSurat(), dll
├── database/
│   ├── backup_sgrafika_kosong.sql   # Struktur tabel saja (kosong)
│   └── backup_sgrafika_data.sql     # Struktur + data dummy 100 proyek
├── proyek/
│   ├── index.php               # Daftar proyek (card view + doc map)
│   ├── create.php              # Buat proyek baru
│   ├── edit.php                # Edit proyek
│   └── detail.php              # Detail proyek + generate/cetak dokumen
├── klien/
│   └── index.php               # CRUD klien + tambah via modal
├── barang/
│   └── index.php               # CRUD barang + auto-add dari form proyek
├── users/
│   └── index.php               # CRUD user (super_admin/owner only)
├── api/
│   ├── get_barang.php          # Search barang (AJAX)
│   ├── get_klien.php           # Search klien (AJAX)
│   ├── save_klien.php          # Simpan klien baru via modal
│   ├── barang_baru.php         # Auto-add barang baru dari form proyek
│   ├── klien_baru.php          # Tambah klien dari modal dalam form proyek
│   ├── generate_dokumen.php    # Generate nomor dokumen
│   ├── set_dp_persen.php       # Simpan persentase DP proyek
│   ├── save_tahap_pembayaran.php # Simpan tahap pembayaran proyek
│   ├── set_entity.php          # Switch entity (super admin)
│   └── toggle_arsip.php        # Arsip / aktifkan proyek
├── template/
│   ├── header.php              # Header HTML + sidebar + navbar
│   ├── footer.php              # Footer HTML + scripts
│   ├── surat_penawaran.php     # Template cetak SP
│   ├── surat_kesepakatan.php   # Template cetak SK
│   ├── proforma_invoice.php    # Template cetak Proforma
│   ├── invoice_dp.php          # Template cetak INV DP
│   ├── invoice_pelunasan.php   # Template cetak INV Pelunasan
│   ├── surat_jalan.php         # Template cetak SJ
│   ├── ba_serah_terima.php     # Template cetak BA
│   └── invoice.php             # Template invoice legacy
└── assets/
    ├── css/
    │   └── style.css           # Custom CSS
    └── js/
        └── app.js              # JavaScript interaksi
```

---

## 7. Database Schema

### Entity Relationship

```
entity 1──N users
entity 1──N klien
entity 1──N barang
entity 1──N counter_dokumen
entity 1──N proyek

proyek N──1 klien
proyek 1──N proyek_item
proyek 1──N proyek_tahap_pembayaran
proyek 1──N proyek_pembayaran      -- (rencana)
proyek_item N──1 barang
```

### Tables

```sql
-- MASTER DATA
entity           -- Sriwijaya Grafika & Workshop Sriwijaya
users            -- Multi-role users (super_admin, owner, admin, karyawan)
klien            -- Client/customer data (nama_perusahaan, alamat, npwp, pic, no_telp)
barang           -- Product/item names (reusable per entity)

-- COUNTER (auto-increment per entity per tahun)
counter_dokumen  -- entity_id + jenis (VARCHAR(20)) + tahun + nomor_terakhir
                 -- jenis: 'sp', 'sk', 'proforma', 'inv_dp', 'inv_pelunasan', 'sj', 'ba'

-- INTI APLIKASI
proyek           -- 1 proyek untuk semua dokumen
  ├── no_referensi       -- 10 digit random
  ├── klien_id           -- relasi ke klien
  ├── entity_id          -- relasi ke entity
  ├── tanggal            -- default now, bisa edit
  ├── berlaku_sampai     -- default +1 bulan
  ├── diskon_persen      -- bisa 0
  ├── ppn_persen         -- default dari entity (11 atau 0)
  ├── sub_total          -- auto-calc (SUM jumlah item)
  ├── dpp                -- sub_total - diskon
  ├── grand_total        -- dpp + ppn
  ├── dp_persen          -- persentase DP (default 50, fallback jika belum ada tahap)
  ├── waktu_pelaksanaan_hari -- default 7
  ├── no_sp              -- null = belum dibuat
  ├── no_sk              -- null = belum dibuat
  ├── no_inv             -- legacy (tidak dipakai)
  ├── no_proforma        -- null = belum dibuat
  ├── no_inv_dp          -- null = belum dibuat
  ├── no_inv_pelunasan   -- null = belum dibuat
  ├── no_sj              -- null = belum dibuat
  ├── no_ba              -- null = belum dibuat
  ├── dibuat_oleh        -- relasi ke users
  ├── is_archived        -- soft delete
  └── created_at / updated_at

proyek_item      -- Items dalam proyek
  ├── proyek_id         -- relasi ke proyek
  ├── no_urut           -- urutan item
  ├── barang_id         -- relasi ke barang (nullable)
  ├── kategori          -- 'barang' atau 'pekerjaan' (untuk SK)
  ├── keterangan        -- custom tiap proyek
  ├── harga             -- custom tiap proyek
  ├── qty
  ├── satuan            -- satuan unit (misal: pcs, meter)
  └── jumlah            -- VIRTUAL GENERATED (harga * qty)
```

### Kolom konfigurasi
| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| `dp_persen` | DECIMAL(5,1) | Persentase DP (default 50). Diatur via tombol "Atur Jumlah DP" sebelum generate INV DP. |

### Kolom nomor dokumen di tabel `proyek`

| Kolom | Prefix | Contoh |
|-------|--------|--------|
| `no_sp` | SP | SP-001 |
| `no_sk` | SK | SK-001 |
| `no_proforma` | PF | PF-001 |
| `no_inv_dp` | INV/DP | INV/DP-001 |
| `no_inv_pelunasan` | INV/LUNAS | INV/LUNAS-001 |
| `no_sj` | SJ | SJ-001 |
| `no_ba` | BA | BA-001 |

### Tabel: `proyek_tahap_pembayaran`

Menyimpan daftar tahap pembayaran (persentase & deskripsi) per proyek.

```sql
proyek_tahap_pembayaran
  ├── id              -- PK auto-increment
  ├── proyek_id       -- FK → proyek.id
  ├── urutan          -- INT nomor urut tahap
  ├── persentase      -- DECIMAL(5,1) misal 20.0, 30.0, 50.0
  └── deskripsi       -- VARCHAR(255) misal "DP", "Pelunasan"
```

Catatan:
- DP untuk Invoice DP mengambil **tahap pertama** (`urutan = 1`) dari tabel ini
- Jika tabel kosong, fallback ke kolom `dp_persen` di tabel proyek (default 50)
- Setiap kali edit proyek, semua record di-delete lalu re-insert (atomic via `api/save_tahap_pembayaran.php`)

### Tabel: `proyek_pembayaran` _(rencana)_

Riwayat pembayaran yang diterima dari klien per proyek.

```sql
proyek_pembayaran   -- (rencana)
  ├── id              -- PK auto-increment
  ├── proyek_id       -- FK → proyek.id
  ├── tahap_id        -- nullable, FK → proyek_tahap_pembayaran.id
  ├── jumlah          -- DECIMAL nominal dibayar
  ├── tanggal         -- DATE tanggal bayar
  ├── metode          -- ENUM('transfer', 'tunai') atau VARCHAR
  ├── keterangan      -- TEXT catatan tambahan
  └── created_at      -- TIMESTAMP
```

---

## 8. Role & Hak Akses

| Role | Buat/Edit Proyek | Lihat Semua | Arsip | Kelola User | Entity Scope |
|------|:---:|:---:|:---:|:---:|-------------|
| **Super Admin** | ✅ | ✅ | ✅ | ✅ | Semua entity |
| **Owner** | ✅ | ✅ | ✅ | ✅ Entity-nya | Entity sendiri |
| **Admin** | ✅ | ✅ | ✅ | ❌ | Entity sendiri |
| **Karyawan** | ✅ (milik sendiri) | ❌ | ❌ | ❌ | Entity sendiri |

---

## 9. Cara Install & Jalankan

### Prasyarat
- PHP 8.1+
- MySQL 8+
- Apache 2.4+

### Via Laragon (Rekomendasi)

```bash
# 1. Letakkan project di C:\laragon\www\swgrafika
# 2. Start Laragon (Apache + MySQL)
# 3. Import database (pilih salah satu):
mysql -u root -p < database/backup_sgrafika_kosong.sql   # struktur aja
mysql -u root -p < database/backup_sgrafika_data.sql     # + data dummy 100 proyek
# 4. Buka di browser:
http://localhost/swgrafika
```

### Login Default

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `admin@sriwijayagrafika.com` | `admin123` |

---

## 10. Helper Functions (`config/functions.php`)

| Fungsi | Deskripsi |
|--------|-----------|
| `rupiah($nilai)` | Format number ke Rp (contoh: Rp 1.500.000) |
| `terbilang($nilai)` | Angka ke terbilang (contoh: satu juta lima ratus ribu rupiah) |
| `getNextNoSurat($pdo, $entity_id, $jenis)` | Auto-increment nomor dokumen per entity/tahun |
| `generateNoReferensi()` | Random 10 digit numeric |
| `cekLogin()` | Redirect ke login jika session tidak ada |

Prefix mapping nomor surat:
- `sp` → `SP-`
- `sk` → `SK-`
- `proforma` → `PF-`
- `inv_dp` → `INV/DP-`
- `inv_pelunasan` → `INV/LUNAS-`
- `sj` → `SJ-`
- `ba` → `BA-`

---

## 11. Auto-Calc Proyek

```
jumlah         = harga × qty
sub_total      = SUM(jumlah seluruh item)
diskon_nominal = sub_total × (diskon_persen / 100)
dpp            = sub_total - diskon_nominal
ppn_nominal    = dpp × (ppn_persen / 100)
grand_total    = dpp + ppn_nominal
```

---

## 12. Catatan Penting

### PPN
- Default dari entity (Sriwijaya Grafika = 11%, Workshop Sriwijaya = 0%)
- Bisa diubah manual per proyek

### Auto-add Barang
- Saat user mengetik nama barang baru di form proyek, barang otomatis masuk ke database

### Counter Nomor
- Reset setiap tahun (2026: SP-001, 2027: reset ke SP-001 lagi)
- Per entity masing-masing
- `counter_dokumen.jenis` menggunakan VARCHAR(20) untuk akomodasi prefix panjang (`inv_dp`, `inv_pelunasan`)

### Sequential Document Unlock
- SP → SK → Proforma → (INV DP, INV Pelunasan, SJ, BA)
- Dokumen yang belum bisa diakses di-hidden (tidak tampil)
- Implementasi di `proyek/detail.php`

### Indeks Penyelesaian
- Visual Fear & Greed style gauge di dashboard
- Rumus: (proyek dengan BA ÷ proyek dengan SP) × 100
- Range: 0-100, warna merah → jingga → kuning → hijau

### Arsip (Soft Delete)
- Tidak ada hapus permanen
- Data diarsipkan dengan flag `is_archived = 1`
- Tidak muncul di daftar proyek utama

### Database Backup
- File backup ada di folder `database/`:
  - `backup_sgrafika_kosong.sql` — struktur tabel saja
  - `backup_sgrafika_data.sql` — struktur + data dummy (100 proyek, 20 klien, 29 barang)
- Data dummy seed via script ad-hoc, sudah dihapus setelah digunakan

### Multi-User Default (data dummy)
| Nama | Email | Role |
|------|-------|------|
| Super Admin | `admin@sriwijayagrafika.com` | super_admin |
| Budi Santoso | `budi@sriwijayagrafika.com` | admin |
| Siti Rahmawati | `siti@sriwijayagrafika.com` | admin |
| Ahmad Fauzi | `ahmad@workshop.com` | owner (Workshop) |
| Dewi Lestari | `dewi@sriwijayagrafika.com` | karyawan |

---

## 13. Rencana: Pencatatan Pembayaran Klien

Fitur untuk mencatat pembayaran yang diterima dari klien secara fleksibel (tidak harus sesuai persentase tahap).

### Alur

```
Proyek selesai di-input + tahap pembayaran sudah diatur
        │
        ▼
Klien bayar DP → Catat di Modul Riwayat Pembayaran
  (pilih tahap "DP", input nominal, tanggal, metode)
        │
        ▼
Klien bayar pelunasan → Catat lagi
  (bisa bertahap / tidak sesuai nominal tahap)
        │
        ▼
Progress bar di detail.php:
  Total Bayar vs Grand Total
```

### Fitur

- Modal "Catat Pembayaran" di detail.php (pilih tahap via dropdown, input nominal, tanggal, metode, keterangan)
- Card "Riwayat Pembayaran" → tabel daftar pembayaran
- Progress bar total bayar vs grand_total
- Badge hijau di setiap baris Tahap Pembayaran jika nominal sudah terbayar penuh
- API `api/simpan_pembayaran.php` untuk simpan / hapus

### Aturan Bisnis

- Satu pembayaran bisa dikaitkan ke satu tahap (`tahap_id` nullable) atau tidak
- Jumlah nominal bisa berbeda dari nominal tahap (misal: klien bayar 5jt, padahal nominal tahap 5.5jt)
- Riwayat tetap utuh meskipun tahap pembayaran diedit (tidak cascade)
- Tidak ada validasi "kelebihan bayar" — sistem hanya mencatat, tidak memblokir

### Files Terkait

| File | Peran |
|------|-------|
| `proyek/detail.php` | Card Riwayat Pembayaran + progress bar + modal Catat Pembayaran |
| `api/simpan_pembayaran.php` | Endpoint AJAX untuk insert/delete |
| `template/invoice_dp.php` | Bacaan DP tetap dari tahap pertama (`proyek_tahap_pembayaran`) |
| `template/invoice_pelunasan.php` | Sama dengan invoice DP |
