# Dashboard Admin - Dokumentasi

## Fitur Dashboard yang Telah Dibuat

Dashboard admin sekarang menampilkan statistik lengkap tentang status dokumen dengan visualisasi yang menarik dan informatif.

### 1. Widget Stats Overview (Kartu Statistik)

Widget ini menampilkan 6 kartu statistik dengan informasi berikut:

#### **Total Dokumen**

- Menampilkan jumlah total semua dokumen dalam sistem
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

## Akses Dashboard

### Hak Akses

- **Admin**: Dapat melihat semua widget dashboard
- **Role lain (Mitra, HSSE, S&D)**: Tidak dapat melihat widget statistik ini

### Cara Mengakses

1. Login ke sistem dengan akun Admin
2. Buka halaman: `http://localhost:8000/admin`
3. Dashboard akan otomatis menampilkan semua widget

## File-file yang Dibuat/Dimodifikasi

### 1. Widget Files

- `app/Filament/Widgets/DocumentStatsOverview.php` - Stats cards widget
- `app/Filament/Widgets/DocumentStatusChart.php` - Bar chart widget
- `app/Filament/Widgets/DocumentStatusPieChart.php` - Pie chart widget

### 2. Configuration Files

- `app/Providers/Filament/AdminPanelProvider.php` - Registrasi widget

## Teknologi yang Digunakan

- **Filament v3**: Framework admin panel untuk Laravel
- **Chart.js**: Library untuk membuat diagram (bar chart dan pie chart)
- **Tailwind CSS**: Styling untuk kartu statistik

## Catatan Penting

1. **Real-time Data**: Semua widget menampilkan data real-time dari database
2. **Performance**: Query dioptimalkan untuk performa yang baik
3. **Responsive**: Dashboard responsive dan dapat diakses dari berbagai perangkat
4. **Color Coding**: Setiap status memiliki warna yang konsisten di semua widget untuk memudahkan identifikasi

## Cara Testing

1. Pastikan server Laravel berjalan:

    ```bash
    php artisan serve
    ```

2. Login sebagai Admin di: `http://localhost:8000/admin`

3. Verifikasi bahwa dashboard menampilkan:
    - 6 kartu statistik di bagian atas
    - Diagram batang di bawah kartu statistik
    - Diagram lingkaran di bawah diagram batang

4. Cek bahwa angka-angka pada widget sesuai dengan data di tabel dokumen

## Troubleshooting

### Widget tidak muncul

- Pastikan Anda login sebagai Admin
- Clear cache: `php artisan cache:clear`
- Clear view cache: `php artisan view:clear`

### Data tidak akurat

- Refresh halaman
- Cek koneksi database
- Verifikasi data di tabel `documents`

### Error saat load dashboard

- Cek log Laravel: `storage/logs/laravel.log`
- Pastikan semua dependencies terinstall
- Jalankan: `composer dump-autoload`
