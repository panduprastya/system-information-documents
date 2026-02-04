# Update: Role-Specific Pending & Reviewing Logic

## Masalah yang Diperbaiki

**Masalah:** Ketika S&D login, terdapat dokumen yang masih pending untuk S&D review, tetapi dashboard tidak menampilkan jumlah dokumen pending tersebut karena logika sebelumnya hanya menghitung dokumen sebagai "Pending" jika **KEDUA status** (HSSE dan S&D) pending.

**Contoh Kasus:**

- Dokumen A: `hsse_status = 'approved'`, `snd_status = 'pending'`
- S&D perlu review dokumen ini, tapi tidak muncul di dashboard S&D

**Solusi:** Mengubah logika "Pending" dan "Reviewing" menjadi **role-specific** sehingga setiap reviewer melihat dokumen yang menunggu/sedang mereka review.

---

## Logika Lengkap Semua Status (Role-Specific)

### 📊 **1. Pending Count**

#### **HSSE Role:**

```php
$pendingCount = where('hsse_status', 'pending')->count();
```

- Menampilkan dokumen yang menunggu HSSE review
- **Tidak peduli** status S&D
- Label: **"Pending HSSE"**

#### **S&D Role:**

```php
$pendingCount = where('snd_status', 'pending')->count();
```

- Menampilkan dokumen yang menunggu S&D review
- **Tidak peduli** status HSSE
- Label: **"Pending S&D"**

#### **Admin & Mitra Role:**

```php
$pendingCount = where('hsse_status', 'pending')
    ->where('snd_status', 'pending')
    ->count();
```

- Menampilkan dokumen yang belum direview sama sekali
- Label: **"Menunggu review"**

---

### 👁️ **2. Reviewing Count**

#### **HSSE Role:**

```php
$reviewingCount = where('hsse_status', 'reviewing')->count();
```

- Menampilkan dokumen yang sedang HSSE review
- Label: **"Reviewing HSSE"**

#### **S&D Role:**

```php
$reviewingCount = where('snd_status', 'reviewing')->count();
```

- Menampilkan dokumen yang sedang S&D review
- Label: **"Reviewing S&D"**

#### **Admin & Mitra Role:**

```php
$reviewingCount = where('hsse_status', 'reviewing')
    ->orWhere('snd_status', 'reviewing')
    ->count();
```

- Menampilkan dokumen yang sedang direview (salah satu atau kedua)
- Label: **"Sedang direview"**

---

### 🔄 **3. Revisi Count** (Tetap sama untuk semua role)

```php
$revisiCount = where('hsse_status', 'revisi')
    ->orWhere('snd_status', 'revisi')
    ->count();
```

- Menampilkan dokumen yang perlu revisi (dari HSSE atau S&D)
- Label: **"Perlu perbaikan"**

---

### ✅ **4. Approved Count** (Sudah diupdate sebelumnya)

#### **HSSE Role:**

```php
$approvedCount = where('hsse_status', 'approved')->count();
```

- Label: **"Disetujui HSSE"**

#### **S&D Role:**

```php
$approvedCount = where('snd_status', 'approved')->count();
```

- Label: **"Disetujui S&D"**

#### **Admin & Mitra Role:**

```php
$approvedCount = where('hsse_status', 'approved')
    ->where('snd_status', 'approved')
    ->count();
```

- Label: **"Fully Approved"**

---

### ❌ **5. Rejected Count** (Sudah diupdate sebelumnya)

#### **HSSE Role:**

```php
$rejectedCount = where('hsse_status', 'rejected')->count();
```

- Label: **"Ditolak HSSE"**

#### **S&D Role:**

```php
$rejectedCount = where('snd_status', 'rejected')->count();
```

- Label: **"Ditolak S&D"**

#### **Admin & Mitra Role:**

```php
$rejectedCount = where('hsse_status', 'rejected')
    ->orWhere('snd_status', 'rejected')
    ->count();
```

- Label: **"Ditolak"**

---

## Contoh Skenario Lengkap

### Skenario 1: Dokumen Baru Diupload

**Kondisi:**

- `hsse_status` = 'pending'
- `snd_status` = 'pending'

**Dashboard:**

| Role      | Pending                | Reviewing | Approved | Rejected |
| --------- | ---------------------- | --------- | -------- | -------- |
| **HSSE**  | ✅ 1 (Pending HSSE)    | 0         | 0        | 0        |
| **S&D**   | ✅ 1 (Pending S&D)     | 0         | 0        | 0        |
| **Admin** | ✅ 1 (Menunggu review) | 0         | 0        | 0        |
| **Mitra** | ✅ 1 (Menunggu review) | 0         | 0        | 0        |

---

### Skenario 2: HSSE Sedang Review

**Kondisi:**

- `hsse_status` = 'reviewing'
- `snd_status` = 'pending'

**Dashboard:**

| Role      | Pending            | Reviewing              | Approved | Rejected |
| --------- | ------------------ | ---------------------- | -------- | -------- |
| **HSSE**  | 0                  | ✅ 1 (Reviewing HSSE)  | 0        | 0        |
| **S&D**   | ✅ 1 (Pending S&D) | 0                      | 0        | 0        |
| **Admin** | 0                  | ✅ 1 (Sedang direview) | 0        | 0        |
| **Mitra** | 0                  | ✅ 1 (Sedang direview) | 0        | 0        |

**✅ Fix:** S&D sekarang bisa melihat dokumen ini di "Pending S&D"!

---

### Skenario 3: HSSE Approved, S&D Pending

**Kondisi:**

- `hsse_status` = 'approved'
- `snd_status` = 'pending'

**Dashboard:**

| Role      | Pending            | Reviewing | Approved              | Rejected |
| --------- | ------------------ | --------- | --------------------- | -------- |
| **HSSE**  | 0                  | 0         | ✅ 1 (Disetujui HSSE) | 0        |
| **S&D**   | ✅ 1 (Pending S&D) | 0         | 0                     | 0        |
| **Admin** | 0                  | 0         | 0                     | 0        |
| **Mitra** | 0                  | 0         | 0                     | 0        |

**✅ Fix:** S&D bisa melihat dokumen yang menunggu review mereka!

---

### Skenario 4: HSSE Approved, S&D Reviewing

**Kondisi:**

- `hsse_status` = 'approved'
- `snd_status` = 'reviewing'

**Dashboard:**

| Role      | Pending | Reviewing              | Approved              | Rejected |
| --------- | ------- | ---------------------- | --------------------- | -------- |
| **HSSE**  | 0       | 0                      | ✅ 1 (Disetujui HSSE) | 0        |
| **S&D**   | 0       | ✅ 1 (Reviewing S&D)   | 0                     | 0        |
| **Admin** | 0       | ✅ 1 (Sedang direview) | 0                     | 0        |
| **Mitra** | 0       | ✅ 1 (Sedang direview) | 0                     | 0        |

---

### Skenario 5: Fully Approved

**Kondisi:**

- `hsse_status` = 'approved'
- `snd_status` = 'approved'

**Dashboard:**

| Role      | Pending | Reviewing | Approved              | Rejected |
| --------- | ------- | --------- | --------------------- | -------- |
| **HSSE**  | 0       | 0         | ✅ 1 (Disetujui HSSE) | 0        |
| **S&D**   | 0       | 0         | ✅ 1 (Disetujui S&D)  | 0        |
| **Admin** | 0       | 0         | ✅ 1 (Fully Approved) | 0        |
| **Mitra** | 0       | 0         | ✅ 1 (Fully Approved) | 0        |

---

## File yang Diupdate

1. ✅ **`app/Filament/Widgets/DocumentStatsOverview.php`**
    - Added role-specific pending/reviewing logic
    - Updated all card descriptions to be role-aware

2. ✅ **`app/Filament/Widgets/DocumentStatusChart.php`**
    - Added role-specific pending/reviewing logic for bar chart

3. ✅ **`app/Filament/Widgets/DocumentStatusPieChart.php`**
    - Added role-specific pending/reviewing logic for pie chart

---

## Ringkasan Perubahan

### Sebelum Update:

```
❌ Pending = KEDUA status pending
❌ Reviewing = SALAH SATU status reviewing
❌ S&D tidak melihat dokumen yang HSSE sudah approve tapi S&D pending
❌ HSSE tidak melihat dokumen yang S&D sudah approve tapi HSSE pending
```

### Setelah Update:

```
✅ Pending = Status pending untuk ROLE MASING-MASING
✅ Reviewing = Status reviewing untuk ROLE MASING-MASING
✅ S&D melihat SEMUA dokumen yang menunggu/sedang mereka review
✅ HSSE melihat SEMUA dokumen yang menunggu/sedang mereka review
✅ Admin/Mitra melihat overview keseluruhan
```

---

## Keuntungan Update Ini

✅ **Workload Visibility:**

- HSSE dan S&D dapat melihat berapa dokumen yang menunggu mereka
- Memudahkan prioritas pekerjaan

✅ **Accurate Tracking:**

- Setiap reviewer melihat progress mereka sendiri
- Tidak ada dokumen yang "hilang" dari dashboard

✅ **Better UX:**

- Label yang jelas dan spesifik per role
- Tidak membingungkan antara pending HSSE vs pending S&D

✅ **Workflow Clarity:**

- Admin melihat dokumen yang benar-benar stuck
- Mitra melihat dokumen yang benar-benar selesai

---

## Testing

### Test 1: S&D dengan Dokumen Pending

1. Login sebagai S&D
2. Pastikan ada dokumen dengan `snd_status = 'pending'`
3. Refresh dashboard
4. ✅ Verifikasi: Dokumen muncul di kartu "Pending" dengan label **"Pending S&D"**

### Test 2: HSSE dengan Dokumen Pending

1. Login sebagai HSSE
2. Pastikan ada dokumen dengan `hsse_status = 'pending'`
3. Refresh dashboard
4. ✅ Verifikasi: Dokumen muncul di kartu "Pending" dengan label **"Pending HSSE"**

### Test 3: Mixed Status

1. Buat dokumen dengan `hsse_status = 'approved'`, `snd_status = 'pending'`
2. Login sebagai S&D
3. ✅ Verifikasi: Dokumen muncul di "Pending S&D"
4. Login sebagai HSSE
5. ✅ Verifikasi: Dokumen muncul di "Disetujui HSSE"

---

## Catatan Penting

⚠️ **Semua Status Sekarang Role-Specific:**

- Pending ✅
- Reviewing ✅
- Revisi (tetap sama untuk semua)
- Approved ✅
- Rejected ✅

💡 **Konsistensi:**

- Logika yang sama diterapkan di Stats, Bar Chart, dan Pie Chart
- Label dinamis di semua widget

🎯 **Business Logic:**

- Dual approval workflow tetap terjaga
- Setiap reviewer bertanggung jawab atas bagian mereka
- Admin tetap melihat big picture
