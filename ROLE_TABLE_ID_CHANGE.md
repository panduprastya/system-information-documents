# Perubahan Kolom ID di Tabel Roles

## Perubahan yang Dilakukan

Kolom **ID** di tabel Roles telah diubah dari menampilkan **ID database** menjadi **urutan angka biasa** (1, 2, 3, 4...).

---

## Sebelum vs Sesudah

### Sebelum:

```
ID  | Name
----|------
2   | HSSE
3   | Admin
4   | S&D
5   | Mitra
```

_ID mengikuti database ID (bisa loncat-loncat)_

### Sesudah:

```
ID  | Name
----|------
1   | HSSE
2   | Admin
3   | S&D
4   | Mitra
```

_ID berurutan 1, 2, 3, 4..._

---

## Implementasi

File: `app/Filament/Resources/RoleResource.php`

```php
public static function table(Table $table): Table
{
    return parent::table($table)
        ->columns([
            // Override ID column to show row numbers
            Tables\Columns\TextColumn::make('row_number')
                ->label('ID')
                ->state(
                    static function (Tables\Contracts\HasTable $livewire, $rowLoop): string {
                        return (string) (
                            $rowLoop->iteration +
                            ($livewire->getTableRecordsPerPage() * (
                                $livewire->getTablePage() - 1
                            ))
                        );
                    }
                )
                ->sortable(false)
                ->searchable(false),

            // ... kolom lainnya
        ]);
}
```

---

## Cara Kerja

### 1. Row Number Calculation

```php
$rowLoop->iteration + ($livewire->getTableRecordsPerPage() * ($livewire->getTablePage() - 1))
```

**Penjelasan:**

- `$rowLoop->iteration` = Nomor baris di halaman saat ini (1, 2, 3...)
- `$livewire->getTableRecordsPerPage()` = Jumlah record per halaman (misal: 10)
- `$livewire->getTablePage()` = Halaman saat ini (1, 2, 3...)

**Contoh Perhitungan:**

**Halaman 1** (10 items per page):

- Row 1: `1 + (10 * (1-1))` = `1 + 0` = **1**
- Row 2: `2 + (10 * (1-1))` = `2 + 0` = **2**
- Row 10: `10 + (10 * (1-1))` = `10 + 0` = **10**

**Halaman 2** (10 items per page):

- Row 1: `1 + (10 * (2-1))` = `1 + 10` = **11**
- Row 2: `2 + (10 * (2-1))` = `2 + 10` = **12**
- Row 10: `10 + (10 * (2-1))` = `10 + 10` = **20**

**Halaman 3** (10 items per page):

- Row 1: `1 + (10 * (3-1))` = `1 + 20` = **21**
- Row 2: `2 + (10 * (3-1))` = `2 + 20` = **22**

### 2. Disable Sorting & Searching

```php
->sortable(false)
->searchable(false)
```

Karena ini bukan kolom database asli, sorting dan searching dinonaktifkan.

---

## Kolom yang Ditampilkan

| Kolom          | Deskripsi                       | Sortable | Searchable |
| -------------- | ------------------------------- | -------- | ---------- |
| **ID**         | Urutan angka (1, 2, 3...)       | ❌       | ❌         |
| **Name**       | Nama role                       | ✅       | ✅         |
| **Guard Name** | Guard (badge hijau)             | ❌       | ❌         |
| **Created At** | Tanggal dibuat (hidden default) | ✅       | ❌         |
| **Updated At** | Tanggal update (hidden default) | ✅       | ❌         |

---

## Keuntungan

✅ **Lebih mudah dibaca** - Urutan 1, 2, 3, 4 lebih intuitif  
✅ **Konsisten** - Tidak ada angka yang loncat-loncat  
✅ **User-friendly** - Lebih mudah untuk referensi  
✅ **Pagination aware** - Nomor tetap berurutan di semua halaman

---

## Catatan Penting

### ⚠️ ID Bukan Database ID

Kolom ID yang ditampilkan **bukan ID dari database**, melainkan **nomor urut tampilan**.

**Implikasi:**

- Jika data difilter, nomor akan berubah
- Jika data disort, nomor akan berubah
- Jika pindah halaman, nomor akan lanjut dari halaman sebelumnya

**Contoh:**

```
Halaman 1: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10
Halaman 2: 11, 12, 13, 14, 15, 16, 17, 18, 19, 20
```

### 💡 Jika Perlu Database ID

Jika di masa depan Anda perlu menampilkan database ID asli, cukup tambahkan kolom baru:

```php
Tables\Columns\TextColumn::make('id')
    ->label('Database ID')
    ->sortable()
    ->toggleable(isToggledHiddenByDefault: true),
```

---

## Kesimpulan

Kolom ID di tabel Roles sekarang menampilkan **urutan angka biasa** (1, 2, 3, 4...) yang lebih mudah dibaca dan user-friendly! 🎉

**Tampilan:**

```
ID  | Name       | Guard Name
----|------------|------------
1   | HSSE       | web
2   | Admin      | web
3   | S&D        | web
4   | Mitra      | web
```
