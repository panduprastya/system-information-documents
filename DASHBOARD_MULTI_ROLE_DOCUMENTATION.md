# Dashboard Multi-Role - Dokumentasi Lengkap

## Fitur Dashboard yang Telah Dibuat

Dashboard sekarang menampilkan statistik lengkap tentang status dokumen dengan visualisasi yang menarik dan informatif. **Dashboard dapat diakses oleh semua role** dengan data yang disesuaikan berdasarkan hak akses masing-masing.

### 1. Widget Stats Overview (Kartu Statistik)

Widget ini menampilkan 6 kartu statistik dengan informasi berikut:

#### **Total Dokumen**

- Menampilkan jumlah total dokumen yang dapat diakses user
- Warna: Primary (Amber)
- Icon: Document Text
- Deskripsi: "Total semua dokumen"

#### **Pending**

- Menampilkan jumlah dokumen yang menunggu review
- Kriteria: `hsse_status = 'pending'` DAN `snd_status = 'pending'`
- Warna: Gray
- Icon: Clock
- Deskripsi: "Menunggu review"

#### **Dalam Review**

- Menampilkan jumlah dokumen yang sedang dalam proses review
- Kriteria: `hsse_status = 'reviewing'` ATAU `snd_status = 'reviewing'`
- Warna: Warning (Amber)
- Icon: Eye
- Deskripsi: "Sedang direview"

#### **Revisi**

- Menampilkan jumlah dokumen yang perlu diperbaiki
- Kriteria: `hsse_status = 'revisi'` ATAU `snd_status = 'revisi'`
- Warna: Info (Blue)
- Icon: Arrow Path
- Deskripsi: "Perlu perbaikan"

#### **Approved**

- Menampilkan jumlah dokumen yang telah disetujui
- Kriteria: `hsse_status = 'approved'` DAN `snd_status = 'approved'`
- Warna: Success (Green)
- Icon: Check Circle
- Deskripsi: "Telah disetujui"

#### **Rejected**

- Menampilkan jumlah dokumen yang ditolak
- Kriteria: `hsse_status = 'rejected'` ATAU `snd_status = 'rejected'`
- Warna: Danger (Red)
- Icon: X Circle
- Deskripsi: "Ditolak"

### 2. Widget Bar Chart (Diagram Batang)

Widget ini menampilkan diagram batang dengan:

- **Judul**: "Statistik Status Dokumen"
- **Tipe**: Bar Chart (Diagram Batang)
- **Data**: Jumlah dokumen untuk setiap status
- **Warna Batang**:
    - Pending: Gray (Abu-abu)
    - Dalam Review: Amber (Kuning)
    - Revisi: Blue (Biru)
    - Approved: Green (Hijau)
    - Rejected: Red (Merah)
- **Fitur**:
    - Sumbu Y dimulai dari 0
    - Step size 1 untuk memudahkan pembacaan
    - Legend ditampilkan

### 3. Widget Pie Chart (Diagram Lingkaran)

Widget ini menampilkan diagram lingkaran dengan:

- **Judul**: "Distribusi Status Dokumen"
- **Tipe**: Pie Chart (Diagram Lingkaran)
- **Data**: Persentase distribusi dokumen berdasarkan status
- **Warna Segmen**: Sama dengan bar chart
- **Fitur**:
    - Legend ditampilkan di bagian bawah
    - Menunjukkan proporsi setiap status secara visual

## Akses Dashboard Berdasarkan Role

### 🔐 Hak Akses dan Filter Data

Dashboard sekarang dapat dilihat oleh **semua role** dengan data yang disesuaikan:

#### **👨‍💼 Admin**

- **Akses**: Melihat **semua dokumen** dalam sistem
- **Filter**: Tidak ada filter (full access)
- **Statistik**: Mencakup seluruh database dokumen
- **Use Case**: Monitoring keseluruhan sistem

#### **🏢 Mitra**

- **Akses**: Melihat **hanya dokumen yang mereka upload**
- **Filter**: `id_mitra = user.id`
- **Statistik**: Hanya menampilkan dokumen milik mereka sendiri
- **Use Case**: Tracking status dokumen yang mereka submit

#### **🛡️ HSSE**

- **Akses**: Melihat dokumen yang relevan untuk review HSSE
- **Filter**:
    - Dokumen yang sudah di-assign ke mereka (`id_hsse = user.id`), ATAU
    - Dokumen yang masih pending untuk HSSE review (`hsse_status = 'pending'`)
- **Statistik**: Mencakup dokumen yang perlu/sedang mereka review
- **Use Case**: Monitoring workload dan progress review HSSE

#### **📋 S&D**

- **Akses**: Melihat dokumen yang relevan untuk review S&D
- **Filter**:
    - Dokumen yang sudah di-assign ke mereka (`id_snd = user.id`), ATAU
    - Dokumen yang masih pending untuk S&D review (`snd_status = 'pending'`)
- **Statistik**: Mencakup dokumen yang perlu/sedang mereka review
- **Use Case**: Monitoring workload dan progress review S&D

### 📍 Cara Mengakses

1. Login ke sistem dengan akun Anda (Admin/Mitra/HSSE/S&D)
2. Buka halaman: `http://localhost:8000/admin`
3. Dashboard akan otomatis menampilkan semua widget dengan data yang sesuai role Anda

## File-file yang Dibuat/Dimodifikasi

### 1. Widget Files

- `app/Filament/Widgets/DocumentStatsOverview.php` - Stats cards widget dengan role-based filtering
- `app/Filament/Widgets/DocumentStatusChart.php` - Bar chart widget dengan role-based filtering
- `app/Filament/Widgets/DocumentStatusPieChart.php` - Pie chart widget dengan role-based filtering

### 2. Configuration Files

- `app/Providers/Filament/AdminPanelProvider.php` - Registrasi widget

## Teknologi yang Digunakan

- **Filament v3**: Framework admin panel untuk Laravel
- **Chart.js**: Library untuk membuat diagram (bar chart dan pie chart)
- **Tailwind CSS**: Styling untuk kartu statistik
- **Laravel Eloquent**: Query builder dengan role-based filtering

## Contoh Skenario Penggunaan

### Skenario 1: Admin Monitoring Sistem

```
Admin login → Dashboard menampilkan:
- Total: 100 dokumen
- Pending: 25 dokumen
- Reviewing: 15 dokumen
- Revisi: 10 dokumen
- Approved: 45 dokumen
- Rejected: 5 dokumen
```

### Skenario 2: Mitra Tracking Dokumen

```
Mitra "PT ABC" login → Dashboard menampilkan:
- Total: 10 dokumen (hanya milik PT ABC)
- Pending: 3 dokumen
- Reviewing: 2 dokumen
- Revisi: 1 dokumen
- Approved: 4 dokumen
- Rejected: 0 dokumen
```

### Skenario 3: HSSE Monitoring Workload

```
HSSE "John Doe" login → Dashboard menampilkan:
- Total: 30 dokumen (assigned ke John + semua pending HSSE)
- Pending: 20 dokumen (belum di-assign)
- Reviewing: 5 dokumen (sedang John review)
- Revisi: 2 dokumen (John minta revisi)
- Approved: 3 dokumen (John sudah approve)
- Rejected: 0 dokumen
```

### Skenario 4: S&D Monitoring Workload

```
S&D "Jane Smith" login → Dashboard menampilkan:
- Total: 35 dokumen (assigned ke Jane + semua pending S&D)
- Pending: 25 dokumen (belum di-assign)
- Reviewing: 4 dokumen (sedang Jane review)
- Revisi: 3 dokumen (Jane minta revisi)
- Approved: 3 dokumen (Jane sudah approve)
- Rejected: 0 dokumen
```

## Catatan Penting

1. **Real-time Data**: Semua widget menampilkan data real-time dari database
2. **Role-Based Access**: Setiap role hanya melihat data yang relevan dengan mereka
3. **Performance**: Query dioptimalkan dengan clone query untuk mencegah konflik
4. **Responsive**: Dashboard responsive dan dapat diakses dari berbagai perangkat
5. **Color Coding**: Setiap status memiliki warna yang konsisten di semua widget
6. **Privacy**: Mitra tidak dapat melihat dokumen mitra lain
7. **Workload Management**: HSSE dan S&D dapat melihat pending documents untuk claim

## Cara Testing

1. Pastikan server Laravel berjalan:

    ```bash
    php artisan serve
    ```

2. **Test sebagai Admin:**
    - Login sebagai Admin
    - Verifikasi dashboard menampilkan semua dokumen

3. **Test sebagai Mitra:**
    - Login sebagai Mitra
    - Verifikasi dashboard hanya menampilkan dokumen milik mitra tersebut

4. **Test sebagai HSSE:**
    - Login sebagai HSSE
    - Verifikasi dashboard menampilkan dokumen yang di-assign + pending HSSE

5. **Test sebagai S&D:**
    - Login sebagai S&D
    - Verifikasi dashboard menampilkan dokumen yang di-assign + pending S&D

## Troubleshooting

### Widget tidak muncul

- Clear cache: `php artisan optimize:clear`
- Refresh halaman browser
- Pastikan sudah login

### Data tidak sesuai role

- Logout dan login kembali
- Clear browser cache
- Cek role user di database

### Error saat load dashboard

- Cek log Laravel: `storage/logs/laravel.log`
- Pastikan semua dependencies terinstall
- Jalankan: `composer dump-autoload`

### Chart tidak tampil

- Pastikan JavaScript enabled di browser
- Clear browser cache
- Cek console browser untuk error

## Keuntungan Implementasi Multi-Role Dashboard

✅ **Transparansi**: Setiap user dapat melihat progress dokumen mereka
✅ **Efisiensi**: Reviewer dapat melihat workload mereka
✅ **Monitoring**: Admin dapat monitor keseluruhan sistem
✅ **Privacy**: Data terisolasi berdasarkan role
✅ **User Experience**: Interface yang sama untuk semua role
✅ **Scalability**: Mudah untuk menambah role baru di masa depan
