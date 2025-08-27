# Fitur Revisi Dokumen untuk Role Mitra

## Deskripsi
Fitur ini memungkinkan role mitra untuk mengedit dokumen ketika status HSSE atau SND adalah 'revisi'. Setelah melakukan revisi, status dokumen akan kembali ke 'pending' untuk review ulang.

## Cara Kerja

### 1. Kondisi untuk Edit dan Review

#### Edit (Role Mitra)
- Role mitra dapat mengedit dokumen hanya ketika:
  - `hsse_status` = 'revisi' ATAU
  - `snd_status` = 'revisi'
- Mitra hanya dapat mengedit dokumen milik mereka sendiri

#### Review (Role HSSE/S&D)
- Role HSSE dapat melakukan review ketika:
  - `hsse_status` = 'pending' ATAU
  - `hsse_status` = 'revisi'
- Role S&D dapat melakukan review ketika:
  - `snd_status` = 'pending' ATAU
  - `snd_status` = 'revisi'
- Role HSSE dan S&D TIDAK dapat mengedit dokumen (hanya review)

### 2. Proses Revisi
1. **HSSE/SND memberikan feedback**: Reviewer memberikan komentar dan mengubah status menjadi 'revisi'
2. **Mitra melihat notifikasi**: Mitra dapat melihat alasan revisi di halaman edit
3. **Mitra melakukan edit**: Mitra dapat mengubah file dan keterangan dokumen
4. **Status berubah**: Setelah edit, status HSSE dan SND berubah menjadi 'pending'
5. **Review ulang**: Dokumen siap untuk review ulang oleh HSSE dan SND

### 3. Fitur yang Tersedia

#### Halaman List Dokumen
- Tombol "Info Revisi" untuk dokumen yang memerlukan revisi (hanya untuk role Mitra)
- Tombol "Edit" hanya muncul untuk role Mitra dengan dokumen status revisi
- Tombol "Review" hanya muncul untuk role HSSE/S&D dengan status pending atau revisi
- Filter berdasarkan status HSSE dan SND

#### Halaman Edit Dokumen
- Section "Revision Information" yang menampilkan:
  - Status dokumen (HSSE dan SND) dengan warna yang berbeda
  - Alasan revisi dari reviewer
  - Informasi reviewer dan tanggal
- Form edit untuk file dan keterangan dokumen

#### Validasi dan Keamanan
- Validasi bahwa mitra hanya dapat mengedit dokumen milik mereka
- Validasi bahwa dokumen memerlukan revisi
- Reset reviewer dan timestamp ketika status berubah

## Status Dokumen

### HSSE Status
- `pending`: Menunggu review
- `reviewing`: Sedang direview
- `approved`: Disetujui
- `rejected`: Ditolak
- `revisi`: Memerlukan revisi

### SND Status
- `pending`: Menunggu review
- `reviewing`: Sedang direview
- `approved`: Disetujui
- `rejected`: Ditolak
- `revisi`: Memerlukan revisi

## Alur Kerja Lengkap

```
1. Dokumen diupload oleh Mitra
   ↓
2. HSSE review (status pending/revisi) → Setujui/Revisi/Reject
   ↓
3. SND review (status pending/revisi) → Setujui/Revisi/Reject
   ↓
4. Jika ada status 'revisi':
   - Mitra dapat melihat alasan revisi
   - Mitra dapat mengedit dokumen (hanya role Mitra)
   - Setelah edit, status kembali ke 'pending'
   ↓
5. Review ulang oleh HSSE/SND (hanya tombol Review, tidak ada Edit)
   ↓
6. Jika semua approved → Dokumen selesai
   Jika masih ada revisi → Kembali ke langkah 4
```

## Aturan Visibility Tombol

### Tombol Edit
- **Role Mitra**: Muncul hanya ketika `hsse_status` = 'revisi' ATAU `snd_status` = 'revisi'
- **Role HSSE**: Tidak pernah muncul (hanya dapat review)
- **Role S&D**: Tidak pernah muncul (hanya dapat review)
- **Role Admin**: Tidak pernah muncul

### Tombol Review
- **Role HSSE**: Muncul ketika `hsse_status` = 'pending' ATAU 'revisi'
- **Role S&D**: Muncul ketika `snd_status` = 'pending' ATAU 'revisi'
- **Role Mitra**: Tidak pernah muncul
- **Role Admin**: Tidak pernah muncul

## File yang Dimodifikasi

1. **Model Document** (`app/Models/document.php`)
   - Menambahkan method `canBeEditedByMitra()`
   - Menambahkan method `needsRevision()`
   - Menambahkan method `getRevisionReasons()`

2. **DocumentResource** (`app/Filament/Resources/DocumentResource.php`)
   - Memperbaiki logika visibility tombol edit
   - Menambahkan tombol "Info Revisi"
   - Menambahkan filter status

3. **EditDocument Page** (`app/Filament/Resources/DocumentResource/Pages/EditDocument.php`)
   - Menambahkan validasi revisi
   - Menambahkan section informasi revisi
   - Memperbaiki logika update status

## Penggunaan

### Untuk Mitra
1. Login ke sistem
2. Lihat daftar dokumen
3. Cari dokumen dengan status 'revisi' (akan ada tombol "Info Revisi")
4. Klik tombol "Edit" pada dokumen yang memerlukan revisi
5. Lihat alasan revisi di section "Revision Information"
6. Upload file baru atau ubah keterangan
7. Simpan perubahan
8. Status akan berubah menjadi 'pending' untuk review ulang

### Untuk HSSE/SND
1. Review dokumen yang sudah direvisi
2. Berikan feedback jika masih ada yang perlu diperbaiki
3. Set status sesuai hasil review

## Keamanan

- Role-based access control (RBAC)
- Validasi kepemilikan dokumen
- Validasi status dokumen
- Audit trail untuk perubahan status
- Soft delete untuk dokumen

## Notifikasi

- Notifikasi sukses ketika dokumen diperbarui
- Notifikasi khusus ketika status berubah
- Notifikasi informasi revisi yang persisten
