# Panduan Penggunaan Fitur Revisi Dokumen

## Untuk Role Mitra

### Kapan Anda Dapat Mengedit Dokumen?
Anda dapat mengedit dokumen ketika:
- Status HSSE = **'revisi'** ATAU
- Status S&D = **'revisi'**

### Cara Melakukan Revisi

1. **Lihat Daftar Dokumen**
   - Login ke sistem
   - Buka menu Documents
   - Cari dokumen dengan tombol "Info Revisi" (berwarna kuning)

2. **Lihat Alasan Revisi**
   - Klik tombol "Info Revisi" untuk melihat detail feedback
   - Atau langsung klik tombol "Edit"

3. **Edit Dokumen**
   - Upload file baru (PDF)
   - Ubah keterangan jika diperlukan
   - Lihat alasan revisi di section "Revision Information"

4. **Simpan Perubahan**
   - Klik "Save"
   - Status akan otomatis berubah menjadi 'pending'
   - Dokumen siap untuk review ulang

### Yang Terjadi Setelah Revisi
- Status HSSE dan S&D berubah menjadi 'pending'
- Reviewer akan melakukan review ulang
- Jika masih ada yang perlu diperbaiki, status akan kembali menjadi 'revisi'

## Untuk Role HSSE

### Memberikan Feedback Revisi
1. Buka dokumen yang akan direview
2. Klik tombol "Review" (hanya muncul untuk status pending atau revisi)
3. Berikan komentar di field "Add New HSSE Comment"
4. Klik "Save"
5. Status otomatis berubah menjadi 'revisi'

### Review Dokumen yang Sudah Direvisi
1. Dokumen dengan status 'pending' siap untuk review
2. Klik tombol "Review" untuk memulai review
3. Berikan feedback baru jika diperlukan

### Catatan Penting
- **Tidak ada tombol Edit** untuk role HSSE
- Hanya dapat melakukan review, tidak dapat mengubah dokumen
- Tombol Review hanya muncul untuk status pending atau revisi

## Untuk Role S&D

### Memberikan Feedback Revisi
1. Buka dokumen yang akan direview
2. Klik tombol "Review" (hanya muncul untuk status pending atau revisi)
3. Berikan komentar di field "Add New S&D Comment"
4. Klik "Save"
5. Status otomatis berubah menjadi 'revisi'

### Review Dokumen yang Sudah Direvisi
1. Dokumen dengan status 'pending' siap untuk review
2. Klik tombol "Review" untuk memulai review
3. Berikan feedback baru jika diperlukan

### Catatan Penting
- **Tidak ada tombol Edit** untuk role S&D
- Hanya dapat melakukan review, tidak dapat mengubah dokumen
- Tombol Review hanya muncul untuk status pending atau revisi

## Status Dokumen

| Status | Arti | Warna |
|--------|------|-------|
| `pending` | Menunggu review | Abu-abu |
| `reviewing` | Sedang direview | Biru |
| `approved` | Disetujui | Hijau |
| `rejected` | Ditolak | Merah |
| `revisi` | Memerlukan revisi | Kuning |

## Aturan Visibility Tombol

### Tombol Edit
- **Role Mitra**: ✅ Muncul ketika status HSSE atau S&D = 'revisi'
- **Role HSSE**: ❌ Tidak pernah muncul
- **Role S&D**: ❌ Tidak pernah muncul
- **Role Admin**: ❌ Tidak pernah muncul

### Tombol Review
- **Role HSSE**: ✅ Muncul ketika status HSSE = 'pending' atau 'revisi'
- **Role S&D**: ✅ Muncul ketika status S&D = 'pending' atau 'revisi'
- **Role Mitra**: ❌ Tidak pernah muncul
- **Role Admin**: ❌ Tidak pernah muncul

## Tips

- **Untuk Mitra**: Selalu lihat alasan revisi sebelum melakukan edit
- **Untuk Reviewer**: Berikan feedback yang jelas dan spesifik
- **Untuk Semua**: Gunakan tombol "Info Revisi" untuk melihat detail feedback

## Troubleshooting

**Q: Saya tidak bisa mengedit dokumen**
A: Pastikan status HSSE atau S&D adalah 'revisi'

**Q: Tombol Edit tidak muncul**
A: Periksa apakah Anda memiliki role Mitra dan dokumen memerlukan revisi

**Q: Status tidak berubah setelah edit**
A: Pastikan Anda menyimpan perubahan dengan tombol "Save"

**Q: File tidak terupload**
A: Pastikan file adalah PDF dan ukuran maksimal 10MB
