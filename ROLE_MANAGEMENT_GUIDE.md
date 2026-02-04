# Role Management - Panduan Penggunaan

## Konfigurasi yang Telah Dilakukan

Aplikasi menggunakan plugin **Filament Spatie Roles Permissions** yang telah dikonfigurasi khusus untuk kebutuhan aplikasi Pertamina Document Review.

### ✅ Perubahan Konfigurasi:

1. **Navigation Group**: Dipindahkan ke grup **"Settings"**
    - Sebelumnya: "Roles and Permissions"
    - Sekarang: "Settings"

2. **Form Permissions**: **DIHILANGKAN**
    - Field untuk memilih permissions tidak ditampilkan
    - Aplikasi hanya menggunakan role-based access control

3. **Guard Name**: **OTOMATIS "web"**
    - Field guard name disembunyikan
    - Otomatis menggunakan guard "web" untuk semua role
    - Tidak perlu (dan tidak bisa) diubah

4. **Redirect After Save**: **Aktif**
    - Setelah create role → redirect ke list roles
    - Setelah edit role → redirect ke list roles

---

## Cara Menggunakan

### Menambahkan Role Baru

1. Login sebagai **Admin**
2. Buka menu **Settings** → **Roles**
3. Klik tombol **New** atau **Create**
4. Isi form:
    - **Name**: Masukkan nama role (contoh: "Supervisor", "Manager")
    - Guard name otomatis "web" (tidak perlu diisi)
5. Klik **Create**
6. Anda akan diarahkan kembali ke halaman list roles

### Mengedit Role

1. Di halaman **Roles**, klik icon **Edit** pada role yang ingin diubah
2. Ubah **Name** jika diperlukan
3. Klik **Save changes**
4. Anda akan diarahkan kembali ke halaman list roles

### Menghapus Role

1. Di halaman **Roles**, klik icon **Delete** pada role yang ingin dihapus
2. Konfirmasi penghapusan

**⚠️ Catatan:**

- Role yang masih digunakan oleh user mungkin tidak bisa dihapus
- Sebaiknya hapus assignment role dari user terlebih dahulu

---

## Field yang Tersedia

### Name

- **Type**: Text Input
- **Required**: Yes
- **Unique**: Yes
- **Deskripsi**: Nama role yang akan dibuat

### Guard Name (Hidden)

- **Type**: Hidden/Auto
- **Default**: "web"
- **Deskripsi**: Otomatis diset ke "web", tidak perlu diisi manual

---

## Apa itu Guard Name?

**Guard Name** adalah mekanisme autentikasi yang digunakan Laravel untuk mengidentifikasi **bagaimana** user melakukan login.

### Jenis-jenis Guard:

1. **WEB Guard** (Session-based) 🌐
    - Untuk aplikasi web biasa
    - Menggunakan session dan cookies
    - **✅ Ini yang digunakan aplikasi Pertamina**

2. **API Guard** (Token-based) 📱
    - Untuk mobile app atau API
    - Menggunakan token authentication

3. **Admin Guard** (Separate auth) 🛡️
    - Untuk admin panel terpisah
    - Menggunakan tabel database berbeda

### Untuk Aplikasi Pertamina:

**Selalu menggunakan guard "web"** karena:

- ✅ Aplikasi web biasa (bukan API)
- ✅ Session-based authentication
- ✅ Semua user login melalui form yang sama
- ✅ Menggunakan tabel `users` yang sama

**Kesimpulan**: Anda tidak perlu khawatir tentang guard name, karena sudah dikonfigurasi otomatis ke "web".

---

## Tabel Roles

Tabel menampilkan informasi berikut:

| Kolom          | Deskripsi                           |
| -------------- | ----------------------------------- |
| **ID**         | ID role                             |
| **Name**       | Nama role                           |
| **Guard Name** | Guard yang digunakan (selalu "web") |
| **Created At** | Tanggal pembuatan                   |
| **Updated At** | Tanggal update terakhir             |

---

## Role Default

Role-role berikut sudah ada di sistem:

- ✅ **Admin** - Full access ke semua fitur
- ✅ **HSSE** - Reviewer HSSE
- ✅ **S&D** / **SND** - Reviewer S&D
- ✅ **Mitra** - Partner yang upload dokumen

---

## Assign Role ke User

Untuk memberikan role ke user:

1. Buka menu **Users**
2. Klik **Edit** pada user yang ingin diberi role
3. Di field **Roles**, pilih role yang sesuai
4. Klik **Save**

**Catatan**: Satu user hanya bisa memiliki **1 role** (sudah dikonfigurasi `maxItems(1)` di UserResource).

---

## FAQ

### Q: Kenapa tidak ada field untuk memilih permissions?

**A:** Aplikasi ini menggunakan role-based access control yang sederhana. Cukup menggunakan role saja tanpa perlu permission yang lebih granular.

### Q: Kenapa guard name tidak muncul di form?

**A:** Untuk menyederhanakan form dan mencegah kesalahan konfigurasi. Guard name otomatis diset ke "web" yang sesuai untuk aplikasi web.

### Q: Bisa membuat role dengan nama yang sama?

**A:** Tidak. Nama role harus unique. Sistem akan menampilkan error jika Anda mencoba membuat role dengan nama yang sudah ada.

### Q: Apa bedanya role di menu "Settings" dengan "Roles and Permissions"?

**A:** Tidak ada bedanya. Sekarang hanya ada 1 menu "Roles" di grup "Settings". Menu "Roles and Permissions" sudah tidak digunakan.

### Q: Kenapa menu Permissions tidak muncul?

**A:** Menu Permissions telah disembunyikan karena tidak digunakan dalam aplikasi ini. Konfigurasi: `'should_register_on_navigation' => ['permissions' => false]`

---

## Konfigurasi File

Semua konfigurasi ada di file:

```
config/filament-spatie-roles-permissions.php
```

### Konfigurasi Penting:

```php
// Navigation group
'navigation_section_group' => 'Settings',

// Hide permissions field in role form
'should_show_permissions_for_roles' => false,

// Hide permissions from navigation
'should_register_on_navigation' => [
    'permissions' => false,
    'roles' => true,
],

// Auto redirect after create/edit
'should_redirect_to_index' => [
    'roles' => [
        'after_create' => true,
        'after_edit' => true
    ],
],

// Default guard name
'default_guard_name' => 'web',

// Hide guard field
'should_show_guard' => false,
```

---

## Troubleshooting

### Error: "The name has already been taken"

**Solusi:** Nama role sudah digunakan. Gunakan nama yang berbeda.

### Role tidak muncul di menu

**Solusi:**

1. Clear cache: `php artisan cache:clear`
2. Refresh browser
3. Pastikan Anda login sebagai Admin

### Tidak bisa menghapus role

**Solusi:**

- Role masih digunakan oleh user
- Hapus assignment role tersebut dari user terlebih dahulu

### Muncul 2 menu "Roles"

**Solusi:**

- Sudah diperbaiki dengan menghapus custom RoleResource
- Hanya menggunakan RoleResource dari plugin

---

## Kesimpulan

Role management sekarang lebih sederhana dengan:

- ✅ Hanya 1 menu "Roles" di grup "Settings"
- ✅ Form yang lebih simpel (tanpa permissions dan guard name)
- ✅ Guard name otomatis "web"
- ✅ Auto redirect setelah create/edit
- ✅ Konfigurasi yang sudah optimal untuk aplikasi ini

Untuk penjelasan lebih detail tentang Guard Name, baca file `GUARD_NAME_EXPLANATION.md`.
