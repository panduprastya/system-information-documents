# Optimasi N+1 Query - Pertamina Document Review

## Ringkasan

Dokumen ini menjelaskan optimasi yang telah dilakukan untuk mengatasi masalah N+1 query dalam aplikasi Pertamina Document Review. Optimasi ini bertujuan untuk meningkatkan performa aplikasi dengan mengurangi jumlah query database yang dieksekusi.

## Apa itu N+1 Query Problem?

N+1 query problem terjadi ketika aplikasi melakukan:

1. **1 query** untuk mengambil data utama (misalnya: daftar dokumen)
2. **N query tambahan** untuk mengambil data relasi dari setiap record (misalnya: nama mitra, reviewer, comments)

Contoh:

```php
// Query 1: Ambil 10 dokumen
$documents = Document::all(); // 1 query

// Query 2-11: Untuk setiap dokumen, ambil nama mitra
foreach ($documents as $doc) {
    echo $doc->mitra->name; // 10 query tambahan!
}
// Total: 11 query (1 + 10)
```

Dengan **eager loading**, kita bisa mengurangi menjadi hanya **2 query**:

```php
// Query 1: Ambil 10 dokumen
// Query 2: Ambil semua mitra yang terkait
$documents = Document::with('mitra')->all(); // 2 query total!

foreach ($documents as $doc) {
    echo $doc->mitra->name; // Tidak ada query tambahan
}
```

## File yang Dioptimasi

### 1. **UserResource.php**

**Lokasi:** `app/Filament/Resources/UserResource.php`

**Masalah:**

- Menampilkan `roles.name` di tabel tanpa eager loading
- Setiap user akan trigger 1 query tambahan untuk mengambil roles

**Solusi:**

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with('roles'); // Eager load roles
}
```

**Dampak:**

- Dari: 1 + N query (N = jumlah users)
- Menjadi: 2 query (1 untuk users, 1 untuk semua roles)

---

### 2. **DocumentResource.php**

**Lokasi:** `app/Filament/Resources/DocumentResource.php`

**Masalah:**

- Menampilkan `mitra.name`, `HSSE.name`, `snd.name` di tabel
- Menampilkan comments dengan user information
- Setiap dokumen trigger multiple queries untuk relasi

**Solusi:**

```php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery()
        ->withoutGlobalScopes([
            SoftDeletingScope::class,
        ])
        ->with(['mitra', 'hsse', 'snd', 'hsseComments.user', 'sndComments.user']);

    // ... rest of the code
}
```

**Dampak:**

- Dari: 1 + (N × 5) query untuk N dokumen
- Menjadi: 6 query total (1 documents, 1 mitra, 1 hsse, 1 snd, 1 hsseComments, 1 sndComments dengan users)

---

### 3. **ViewDocument.php**

**Lokasi:** `app/Filament/Resources/DocumentResource/Pages/ViewDocument.php`

**Masalah:**

- Mengakses relasi `mitra`, `hsse`, `snd`, `hsseComments.user`, `sndComments.user` di halaman view
- Setiap akses relasi trigger query baru

**Solusi:**

```php
protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
{
    $record = parent::resolveRecord($key);

    // Eager load relationships to prevent N+1 queries
    $record->load(['mitra', 'hsse', 'snd', 'hsseComments.user', 'sndComments.user']);

    // ... rest of the code
}
```

**Dampak:**

- Dari: 1 + 5 + (jumlah comments × 2) query
- Menjadi: 6 query total

---

### 4. **EditDocument.php**

**Lokasi:** `app/Filament/Resources/DocumentResource/Pages/EditDocument.php`

**Masalah:**

- Sama seperti ViewDocument, mengakses multiple relasi
- Menampilkan existing comments dengan user information

**Solusi:**

```php
protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
{
    $record = parent::resolveRecord($key);

    // Eager load relationships to prevent N+1 queries
    $record->load(['mitra', 'hsse', 'snd', 'hsseComments.user', 'sndComments.user']);

    // ... rest of the code
}
```

**Dampak:**

- Dari: 1 + 5 + (jumlah comments × 2) query
- Menjadi: 6 query total

---

### 5. **DocumentStatusChart.php**

**Lokasi:** `app/Filament/Widgets/DocumentStatusChart.php`

**Masalah:**

- Melakukan banyak `clone` query yang redundan
- Setiap status count dilakukan dengan query terpisah

**Solusi:**
Mengelompokkan query berdasarkan role untuk mengurangi jumlah clone:

```php
// Sebelum: 5-6 query terpisah
// Sesudah: 5 query (lebih terorganisir)
if ($user->hasRole('HSSE')) {
    $pendingCount = (clone $query)->where('hsse_status', 'pending')->count();
    $reviewingCount = (clone $query)->where('hsse_status', 'reviewing')->count();
    $approvedCount = (clone $query)->where('hsse_status', 'approved')->count();
    $rejectedCount = (clone $query)->where('hsse_status', 'rejected')->count();
}
// ... similar for other roles
```

**Dampak:**

- Kode lebih terorganisir dan mudah dibaca
- Mengurangi redundansi dalam query cloning

---

## Ringkasan Peningkatan Performa

### Sebelum Optimasi:

- **UserResource**: 1 + N query (untuk N users)
- **DocumentResource**: 1 + (N × 5) query (untuk N documents)
- **ViewDocument**: 1 + 5 + (M × 2) query (untuk M comments)
- **EditDocument**: 1 + 5 + (M × 2) query (untuk M comments)

### Setelah Optimasi:

- **UserResource**: 2 query (fixed)
- **DocumentResource**: 6 query (fixed)
- **ViewDocument**: 6 query (fixed)
- **EditDocument**: 6 query (fixed)

### Contoh Perhitungan:

Jika ada 100 dokumen dengan rata-rata 5 comments per dokumen:

**Sebelum:**

- DocumentResource: 1 + (100 × 5) = **501 query**
- ViewDocument: 1 + 5 + (5 × 2) = **16 query per view**

**Sesudah:**

- DocumentResource: **6 query** (pengurangan 99%)
- ViewDocument: **6 query per view** (pengurangan 63%)

## Best Practices untuk Mencegah N+1 Query

### 1. Gunakan Eager Loading

```php
// ❌ Bad - N+1 query
$documents = Document::all();
foreach ($documents as $doc) {
    echo $doc->mitra->name;
}

// ✅ Good - Eager loading
$documents = Document::with('mitra')->all();
foreach ($documents as $doc) {
    echo $doc->mitra->name;
}
```

### 2. Nested Eager Loading

```php
// Load relasi bersarang
Document::with(['hsseComments.user', 'sndComments.user'])->get();
```

### 3. Conditional Eager Loading

```php
// Load relasi hanya jika diperlukan
Document::when($needsComments, function ($query) {
    $query->with(['hsseComments', 'sndComments']);
})->get();
```

### 4. Lazy Eager Loading

```php
// Jika sudah terlanjur query tanpa eager loading
$document = Document::find(1);
$document->load(['mitra', 'hsse', 'snd']);
```

### 5. Gunakan withCount untuk Counting

```php
// ❌ Bad
$documents = Document::all();
foreach ($documents as $doc) {
    echo $doc->hsseComments()->count(); // N query
}

// ✅ Good
$documents = Document::withCount('hsseComments')->all();
foreach ($documents as $doc) {
    echo $doc->hsse_comments_count; // No additional query
}
```

## Monitoring Query Performance

### 1. Laravel Debugbar

Install Laravel Debugbar untuk memonitor query:

```bash
composer require barryvdh/laravel-debugbar --dev
```

### 2. Query Logging

Enable query logging di development:

```php
DB::enableQueryLog();
// ... your code
dd(DB::getQueryLog());
```

### 3. Laravel Telescope

Untuk monitoring lebih advanced:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

## Catatan Penting

1. **Jangan Over-eager Load**: Hanya load relasi yang benar-benar dibutuhkan
2. **Perhatikan Memory**: Eager loading banyak data bisa menghabiskan memory
3. **Gunakan Pagination**: Untuk dataset besar, gunakan pagination
4. **Test Performance**: Selalu test performa sebelum dan sesudah optimasi

## Rekomendasi Selanjutnya

1. **Database Indexing**: Pastikan foreign keys memiliki index
2. **Query Caching**: Implementasi cache untuk query yang sering diakses
3. **Database Connection Pooling**: Untuk aplikasi dengan traffic tinggi
4. **Load Testing**: Test aplikasi dengan data yang lebih besar

## Kesimpulan

Optimasi N+1 query yang telah dilakukan akan **signifikan meningkatkan performa aplikasi**, terutama ketika:

- Jumlah data bertambah banyak
- Banyak user mengakses aplikasi secara bersamaan
- Menampilkan list dengan banyak relasi

Dengan eager loading yang tepat, aplikasi akan:

- ✅ Lebih cepat dalam loading data
- ✅ Mengurangi beban database server
- ✅ Memberikan user experience yang lebih baik
- ✅ Lebih scalable untuk pertumbuhan data di masa depan
