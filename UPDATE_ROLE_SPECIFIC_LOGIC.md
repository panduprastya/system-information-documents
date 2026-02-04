# Update: Role-Specific Approved & Rejected Logic

## Masalah yang Diperbaiki

**Masalah:** Ketika HSSE login dan melakukan approve dokumen, dashboard tidak menampilkan jumlah dokumen yang diapprove karena logika sebelumnya hanya menghitung dokumen sebagai "Approved" jika **KEDUA status** (HSSE dan S&D) sudah approved.

**Solusi:** Mengubah logika "Approved" dan "Rejected" menjadi **role-specific** sehingga setiap role melihat data yang relevan dengan pekerjaan mereka.

---

## Logika Baru

### 📊 **Approved Count**

#### **HSSE Role:**

```php
// HSSE melihat dokumen yang MEREKA sudah approve
$approvedCount = where('hsse_status', 'approved')->count();
```

- Menampilkan dokumen yang sudah di-approve oleh HSSE
- **Tidak peduli** status S&D (bisa pending, reviewing, approved, dll)
- Label: **"Disetujui HSSE"**

#### **S&D Role:**

```php
// S&D melihat dokumen yang MEREKA sudah approve
$approvedCount = where('snd_status', 'approved')->count();
```

- Menampilkan dokumen yang sudah di-approve oleh S&D
- **Tidak peduli** status HSSE (bisa pending, reviewing, approved, dll)
- Label: **"Disetujui S&D"**

#### **Admin & Mitra Role:**

```php
// Admin dan Mitra melihat dokumen yang FULLY approved
$approvedCount = where('hsse_status', 'approved')
    ->where('snd_status', 'approved')
    ->count();
```

- Menampilkan dokumen yang sudah di-approve oleh **KEDUA** reviewer
- Hanya dihitung jika HSSE **DAN** S&D sudah approve
- Label: **"Fully Approved"**

---

### ❌ **Rejected Count**

#### **HSSE Role:**

```php
// HSSE melihat dokumen yang MEREKA reject
$rejectedCount = where('hsse_status', 'rejected')->count();
```

- Menampilkan dokumen yang di-reject oleh HSSE
- Label: **"Ditolak HSSE"**

#### **S&D Role:**

```php
// S&D melihat dokumen yang MEREKA reject
$rejectedCount = where('snd_status', 'rejected')->count();
```

- Menampilkan dokumen yang di-reject oleh S&D
- Label: **"Ditolak S&D"**

#### **Admin & Mitra Role:**

```php
// Admin dan Mitra melihat dokumen yang di-reject (salah satu atau kedua)
$rejectedCount = where('hsse_status', 'rejected')
    ->orWhere('snd_status', 'rejected')
    ->count();
```

- Menampilkan dokumen yang di-reject oleh **salah satu atau kedua** reviewer
- Label: **"Ditolak"**

---

## Contoh Skenario

### Skenario 1: HSSE Approve Dokumen

**Kondisi Dokumen:**

- `hsse_status` = 'approved' (HSSE sudah approve)
- `snd_status` = 'pending' (S&D belum review)

**Dashboard yang Terlihat:**

| Role      | Approved Count | Keterangan                         |
| --------- | -------------- | ---------------------------------- |
| **HSSE**  | ✅ **1**       | Dokumen muncul di "Disetujui HSSE" |
| **S&D**   | ❌ **0**       | Dokumen belum mereka approve       |
| **Admin** | ❌ **0**       | Dokumen belum fully approved       |
| **Mitra** | ❌ **0**       | Dokumen belum fully approved       |

---

### Skenario 2: HSSE dan S&D Sudah Approve

**Kondisi Dokumen:**

- `hsse_status` = 'approved'
- `snd_status` = 'approved'

**Dashboard yang Terlihat:**

| Role      | Approved Count | Keterangan                         |
| --------- | -------------- | ---------------------------------- |
| **HSSE**  | ✅ **1**       | Dokumen muncul di "Disetujui HSSE" |
| **S&D**   | ✅ **1**       | Dokumen muncul di "Disetujui S&D"  |
| **Admin** | ✅ **1**       | Dokumen muncul di "Fully Approved" |
| **Mitra** | ✅ **1**       | Dokumen muncul di "Fully Approved" |

---

### Skenario 3: HSSE Reject, S&D Approve

**Kondisi Dokumen:**

- `hsse_status` = 'rejected'
- `snd_status` = 'approved'

**Dashboard yang Terlihat:**

| Role      | Approved | Rejected | Keterangan                            |
| --------- | -------- | -------- | ------------------------------------- |
| **HSSE**  | 0        | ✅ **1** | Muncul di "Ditolak HSSE"              |
| **S&D**   | ✅ **1** | 0        | Muncul di "Disetujui S&D"             |
| **Admin** | 0        | ✅ **1** | Muncul di "Ditolak" (ada yang reject) |
| **Mitra** | 0        | ✅ **1** | Muncul di "Ditolak" (ada yang reject) |

---

## File yang Diupdate

1. ✅ **`app/Filament/Widgets/DocumentStatsOverview.php`**
    - Added role-specific approved/rejected logic
    - Updated card descriptions based on role

2. ✅ **`app/Filament/Widgets/DocumentStatusChart.php`**
    - Added role-specific approved/rejected logic for bar chart

3. ✅ **`app/Filament/Widgets/DocumentStatusPieChart.php`**
    - Added role-specific approved/rejected logic for pie chart

---

## Keuntungan Perubahan Ini

✅ **Transparansi untuk Reviewer:**

- HSSE dapat melihat berapa dokumen yang sudah mereka approve/reject
- S&D dapat melihat berapa dokumen yang sudah mereka approve/reject
- Memudahkan tracking progress pekerjaan masing-masing

✅ **Akurasi untuk Admin:**

- Admin tetap melihat dokumen yang benar-benar fully approved
- Mudah monitor dokumen yang stuck di salah satu reviewer

✅ **Clarity untuk Mitra:**

- Mitra melihat dokumen yang benar-benar sudah selesai (fully approved)
- Atau dokumen yang ditolak (perlu action dari mereka)

✅ **Workflow Awareness:**

- Setiap role memahami posisi mereka dalam workflow
- Data yang ditampilkan relevan dengan tanggung jawab mereka

---

## Testing

### Test 1: Login sebagai HSSE

1. Login sebagai HSSE
2. Approve beberapa dokumen
3. Refresh dashboard
4. ✅ Verifikasi: Dokumen yang di-approve muncul di kartu "Approved" dengan label **"Disetujui HSSE"**

### Test 2: Login sebagai S&D

1. Login sebagai S&D
2. Approve beberapa dokumen
3. Refresh dashboard
4. ✅ Verifikasi: Dokumen yang di-approve muncul di kartu "Approved" dengan label **"Disetujui S&D"**

### Test 3: Login sebagai Admin

1. Login sebagai Admin
2. Lihat dashboard
3. ✅ Verifikasi: Hanya dokumen dengan KEDUA status approved yang muncul di "Fully Approved"

### Test 4: Login sebagai Mitra

1. Login sebagai Mitra
2. Lihat dashboard
3. ✅ Verifikasi: Hanya dokumen milik mereka yang fully approved yang muncul

---

## Catatan Penting

⚠️ **Workflow Dokumen:**

- Dokumen baru dianggap **benar-benar selesai** jika HSSE **DAN** S&D sudah approve
- Ini sesuai dengan business logic: dokumen butuh dual approval
- Dashboard sekarang mencerminkan workflow ini dengan lebih jelas

💡 **Label Dinamis:**

- Label "Approved" dan "Rejected" berubah otomatis berdasarkan role yang login
- Memberikan konteks yang lebih jelas untuk setiap user

🎯 **Konsistensi:**

- Logika yang sama diterapkan di semua widget (Stats, Bar Chart, Pie Chart)
- Memastikan data konsisten di seluruh dashboard
