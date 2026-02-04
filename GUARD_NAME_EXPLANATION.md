# Penjelasan Guard Name dalam Laravel

## Apa itu Guard Name?

**Guard Name** adalah mekanisme autentikasi yang digunakan oleh Laravel untuk mengidentifikasi **bagaimana** dan **dari mana** user melakukan login/autentikasi.

## Konsep Dasar

Dalam Laravel, Anda bisa memiliki **berbagai cara** untuk user login, misalnya:

- Login sebagai **user biasa** (web)
- Login sebagai **admin** (admin)
- Login melalui **API** (api)
- Login sebagai **customer** (customer)

Setiap cara login ini disebut sebagai **"guard"**.

## Contoh Penggunaan

### 1. Guard "web" (Default)

Ini adalah guard yang paling umum digunakan untuk aplikasi web biasa.

```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
],
```

**Kapan digunakan?**

- Login melalui form login biasa
- Session-based authentication
- Aplikasi web tradisional (seperti aplikasi Pertamina Document Review Anda)

### 2. Guard "api"

Digunakan untuk autentikasi API (biasanya menggunakan token).

```php
'guards' => [
    'api' => [
        'driver' => 'token',
        'provider' => 'users',
    ],
],
```

**Kapan digunakan?**

- Mobile app yang mengakses API
- Third-party integration
- Stateless authentication

### 3. Guard Custom (Contoh: "admin")

Anda bisa membuat guard khusus untuk admin.

```php
'guards' => [
    'admin' => [
        'driver' => 'session',
        'provider' => 'admins',
    ],
],
```

**Kapan digunakan?**

- Ketika admin dan user biasa menggunakan tabel database yang berbeda
- Ketika admin memiliki mekanisme login yang berbeda

## Guard Name dalam Spatie Permission

Ketika Anda membuat **Role** atau **Permission**, Anda harus menentukan **guard_name** agar Laravel tahu role/permission tersebut untuk guard yang mana.

### Contoh:

```php
// Role untuk web guard
Role::create(['name' => 'Admin', 'guard_name' => 'web']);

// Role untuk api guard
Role::create(['name' => 'API User', 'guard_name' => 'api']);
```

### Mengapa Penting?

Karena satu user bisa memiliki role yang berbeda di guard yang berbeda:

```php
// User bisa jadi Admin di web
$user->assignRole('Admin'); // guard: web

// Tapi bisa jadi regular user di API
$user->assignRole('API User'); // guard: api
```

## Untuk Aplikasi Pertamina Document Review

Dalam aplikasi Anda, **guard_name selalu "web"** karena:

1. ✅ Aplikasi adalah web-based (bukan API atau mobile app)
2. ✅ Menggunakan session-based authentication
3. ✅ Semua user (Admin, HSSE, S&D, Mitra) login melalui form login yang sama
4. ✅ Menggunakan tabel `users` yang sama

### Kesimpulan untuk Aplikasi Anda:

**Anda TIDAK perlu mengubah guard_name dari "web"**. Biarkan tetap "web" untuk semua role yang Anda buat.

```php
// ✅ Benar - Semua role menggunakan guard "web"
Role::create(['name' => 'Admin', 'guard_name' => 'web']);
Role::create(['name' => 'HSSE', 'guard_name' => 'web']);
Role::create(['name' => 'S&D', 'guard_name' => 'web']);
Role::create(['name' => 'Mitra', 'guard_name' => 'web']);

// ❌ Salah - Jangan gunakan guard lain kecuali Anda tahu apa yang Anda lakukan
Role::create(['name' => 'Admin', 'guard_name' => 'api']); // JANGAN!
```

## Kapan Anda Perlu Guard Berbeda?

Anda hanya perlu guard berbeda jika:

1. **Membuat API** untuk mobile app atau third-party
    - Buat guard "api" dengan token-based auth
    - Buat role khusus untuk API users

2. **Memisahkan Admin dan User**
    - Admin login di `/admin`
    - User login di `/user`
    - Menggunakan tabel database berbeda

3. **Multi-tenant Application**
    - Setiap tenant punya guard sendiri
    - Isolasi data antar tenant

## Kesimpulan

Untuk aplikasi Pertamina Document Review Anda:

- **Guard Name**: Selalu gunakan **"web"**
- **Alasan**: Aplikasi web biasa dengan session-based authentication
- **Jangan diubah**: Kecuali Anda menambahkan fitur API atau sistem login terpisah

Field `guard_name` di form sudah saya set **disabled** dan **default "web"**, jadi admin tidak perlu (dan tidak bisa) mengubahnya saat membuat role baru.
