---
description: Cara kerja sistem split dokumen HSSE dan CRM
---

# Document Type Split System

## Overview

Sistem ini memungkinkan user Mitra untuk mengirim dokumen ke departemen yang berbeda (HSSE atau CRM) dengan isi dokumen yang berbeda pula.

## Cara Kerja

### 1. Upload Dokumen (User Mitra)

Ketika user Mitra mengupload dokumen:

- Mereka akan melihat dropdown **"Tipe Dokumen"** dengan pilihan:
    - **HSSE** (Health, Safety, Security & Environment)
    - **CRM** (Customer Relationship Management)
- User memilih departemen mana yang akan mereview dokumen tersebut
- Sistem menyimpan pilihan ini di field `document_type`

### 2. Filtering Otomatis Berdasarkan Role

#### User HSSE:

- **Hanya melihat** dokumen dengan `document_type = 'hsse'`
- **Tidak bisa akses** dokumen CRM (akan error 403)
- Bisa review, approve, reject dokumen HSSE saja
- **Hanya `hsse_status` yang relevan** - `crm_status` tidak ditampilkan

#### User CRM:

- **Hanya melihat** dokumen dengan `document_type = 'crm'`
- **Tidak bisa akses** dokumen HSSE (akan error 403)
- Bisa review, approve, reject dokumen CRM saja
- **Hanya `crm_status` yang relevan** - `hsse_status` tidak ditampilkan

#### User Mitra:

- Melihat **semua dokumen** yang mereka upload (baik HSSE maupun CRM)
- Bisa edit/delete sesuai status dokumen **yang relevan**:
    - Dokumen HSSE: cek `hsse_status`
    - Dokumen CRM: cek `crm_status`
- **Download tersedia** setelah status yang relevan = 'approved':
    - Dokumen HSSE: download jika `hsse_status = 'approved'`
    - Dokumen CRM: download jika `crm_status = 'approved'`

#### User Admin:

- Melihat **semua dokumen** (HSSE dan CRM)
- Bisa manage semua dokumen

### 3. Status Tracking Terpisah

**PENTING:** Status sekarang terpisah berdasarkan tipe dokumen!

#### Dokumen HSSE:

- **Hanya `hsse_status` yang aktif dan ditampilkan**
- `crm_status` tidak relevan dan **tidak ditampilkan** di UI
- Review, approve, reject hanya mempengaruhi `hsse_status`
- Comments hanya di `hsse_comments` table

#### Dokumen CRM:

- **Hanya `crm_status` yang aktif dan ditampilkan**
- `hsse_status` tidak relevan dan **tidak ditampilkan** di UI
- Review, approve, reject hanya mempengaruhi `crm_status`
- Comments hanya di `crm_comments` table

### 4. Download Dokumen

**Sebelumnya (SALAH ❌):**

- Mitra harus menunggu **KEDUA status** (hsse_status DAN crm_status) approved
- Ini tidak masuk akal karena satu dokumen hanya untuk satu departemen

**Sekarang (BENAR ✅):**

- **Dokumen HSSE**: Download tersedia jika `hsse_status = 'approved'`
- **Dokumen CRM**: Download tersedia jika `crm_status = 'approved'`
- Tidak perlu menunggu status yang tidak relevan

### 5. Comments Terpisah

- Dokumen HSSE → `hsse_comments` table
- Dokumen CRM → `crm_comments` table

## Database Schema

### Field Baru: `document_type`

```sql
ALTER TABLE documents ADD COLUMN document_type ENUM('hsse', 'crm') DEFAULT 'hsse';
```

## Keuntungan Sistem Ini

✅ **Jelas dan Eksplisit** - User langsung tahu dokumen untuk departemen mana
✅ **Aman** - HSSE tidak bisa lihat dokumen CRM, begitu juga sebaliknya
✅ **Mudah Maintain** - Filtering otomatis berdasarkan role
✅ **Scalable** - Mudah ditambah departemen baru di masa depan
✅ **Clean Separation** - Setiap departemen fokus pada dokumen mereka saja

## Testing Checklist

- [ ] User Mitra bisa pilih tipe dokumen saat upload
- [ ] User HSSE hanya melihat dokumen HSSE di list
- [ ] User CRM hanya melihat dokumen CRM di list
- [ ] User HSSE tidak bisa akses URL dokumen CRM (403 error)
- [ ] User CRM tidak bisa akses URL dokumen HSSE (403 error)
- [ ] Filter "Tipe Dokumen" berfungsi di halaman list
- [ ] Badge tipe dokumen muncul di tabel dan view page
- [ ] Admin bisa lihat semua dokumen
