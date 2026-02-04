# Perbaikan Error Access Level

## Error yang Terjadi

```
Symfony\Component\ErrorHandler\Error\FatalError

Access level to App\Filament\Resources\RoleResource\Pages\EditRole::getHeaderActions()
must be public (as in class
Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\EditRole)
```

## Penyebab

Method `getHeaderActions()` di custom pages menggunakan visibility **`protected`**, sedangkan di parent class (dari plugin) menggunakan **`public`**.

Dalam PHP, ketika meng-override method dari parent class, **visibility tidak boleh lebih restrictive**:

- ✅ `public` → `public` (OK)
- ❌ `public` → `protected` (ERROR)
- ❌ `public` → `private` (ERROR)

## Solusi

Mengubah visibility method dari **`protected`** menjadi **`public`** di semua custom pages.

---

## File yang Diperbaiki

### 1. EditRole.php

**Sebelum:**

```php
protected function getHeaderActions(): array
{
    return [
        Actions\DeleteAction::make(),
    ];
}
```

**Sesudah:**

```php
public function getHeaderActions(): array
{
    return [
        Actions\DeleteAction::make(),
    ];
}
```

---

### 2. ListRoles.php

**Sebelum:**

```php
protected function getHeaderActions(): array
{
    return [
        Actions\CreateAction::make(),
    ];
}
```

**Sesudah:**

```php
public function getHeaderActions(): array
{
    return [
        Actions\CreateAction::make(),
    ];
}
```

---

### 3. CreateRole.php

**Sebelum:**

```php
protected function getFormActions(): array
{
    return [
        $this->getCreateFormAction(),
        $this->getCancelFormAction(),
    ];
}
```

**Sesudah:**

```php
public function getFormActions(): array
{
    return [
        $this->getCreateFormAction(),
        $this->getCancelFormAction(),
    ];
}
```

---

## Penjelasan Visibility di PHP

### Public

- Bisa diakses dari **mana saja**
- Dari dalam class, dari luar class, dari child class
- **Paling tidak restrictive**

### Protected

- Hanya bisa diakses dari **dalam class** dan **child class**
- Tidak bisa diakses dari luar class
- **Medium restrictive**

### Private

- Hanya bisa diakses dari **dalam class itu sendiri**
- Tidak bisa diakses dari child class atau luar class
- **Paling restrictive**

---

## Aturan Override Method

Ketika meng-override method dari parent class:

✅ **Boleh:**

- `public` → `public`
- `protected` → `protected`
- `protected` → `public` (lebih permissive)
- `private` → `public` (lebih permissive)

❌ **Tidak Boleh:**

- `public` → `protected` (lebih restrictive)
- `public` → `private` (lebih restrictive)
- `protected` → `private` (lebih restrictive)

---

## Kesimpulan

Error telah diperbaiki dengan mengubah visibility method dari `protected` menjadi `public` untuk match dengan parent class.

**File yang diubah:**

- ✅ `app/Filament/Resources/RoleResource/Pages/EditRole.php`
- ✅ `app/Filament/Resources/RoleResource/Pages/ListRoles.php`
- ✅ `app/Filament/Resources/RoleResource/Pages/CreateRole.php`

Sekarang aplikasi sudah bisa berjalan tanpa error! 🎉

---

## Catatan Penting

Jika Anda meng-override method dari parent class di masa depan, **selalu pastikan**:

1. Method signature sama (nama, parameter, return type)
2. Visibility sama atau **lebih permissive** (tidak lebih restrictive)
3. Jika parent class method adalah `public`, child class juga harus `public`
