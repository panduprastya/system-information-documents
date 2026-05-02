# Pop-up Konfirmasi - Menggunakan Pola DeleteAction

## Perubahan yang Dilakukan

Saya telah mengimplementasikan pop-up konfirmasi menggunakan **pola yang sama persis** dengan DeleteAction yang sudah berfungsi.

## Pola DeleteAction (yang sudah berfungsi)

```php
Tables\Actions\DeleteAction::make()
    ->requiresConfirmation()
    ->modalHeading('Hapus Dokumen')
    ->modalDescription('Apakah Anda yakin ingin menghapus dokumen ini?')
    ->modalSubmitActionLabel('Ya, Hapus')
    ->modalCancelActionLabel('Batal')
    ->action(function (Document $record) {
        // Logic untuk delete
        $record->forceDelete();
    })
```

## Implementasi untuk Create Document

```php
\Filament\Actions\Action::make('create')
    ->label('Kirim Dokumen')
    ->requiresConfirmation()
    ->modalHeading('⚠️ Konfirmasi Pengiriman Dokumen')
    ->modalDescription('...')
    ->modalSubmitActionLabel('Ya, Kirim Dokumen')
    ->modalCancelActionLabel('Batal')
    ->modalIcon('heroicon-o-paper-airplane')
    ->modalIconColor('success')
    ->color('success')
    ->action(function () {
        // Validate form
        $data = $this->form->getState();

        // Mutate data
        $data = $this->mutateFormDataBeforeCreate($data);

        // Create record
        $this->record = $this->handleRecordCreation($data);

        // Notification
        \Filament\Notifications\Notification::make()
            ->success()
            ->title('Dokumen Berhasil Dikirim')
            ->send();

        // Redirect
        $this->redirect($this->getRedirectUrl());
    })
```

## Implementasi untuk Edit Document

```php
\Filament\Actions\Action::make('save')
    ->label('Simpan Perubahan')
    ->requiresConfirmation()
    ->modalHeading('⚠️ Konfirmasi Perubahan Dokumen')
    ->modalDescription('...')
    ->modalSubmitActionLabel('Ya, Simpan Perubahan')
    ->modalCancelActionLabel('Batal')
    ->modalIcon('heroicon-o-arrow-path')
    ->modalIconColor('warning')
    ->color('warning')
    ->action(function () {
        // Validate form
        $data = $this->form->getState();

        // Update record
        $this->record = $this->handleRecordUpdate($this->record, $data);

        // Redirect
        $this->redirect($this->getRedirectUrl());
    })
```

## Cara Kerja

1. User mengisi form
2. User klik tombol "Kirim Dokumen" atau "Simpan Perubahan"
3. **Pop-up konfirmasi muncul** (sama seperti pop-up delete)
4. Jika user klik "Ya", action() dijalankan:
    - Form divalidasi
    - Data diproses
    - Record dibuat/diupdate
    - Notifikasi ditampilkan
    - Redirect ke halaman list
5. Jika user klik "Batal", pop-up ditutup

## Testing

1. **Hard refresh browser**: Ctrl + Shift + R
2. **Login sebagai Mitra**
3. **Test Create**: Klik "Create" → Isi form → Klik "Kirim Dokumen" → **Pop-up harus muncul!**
4. **Test Edit**: Klik "Edit" → Ubah data → Klik "Simpan Perubahan" → **Pop-up harus muncul!**

## File yang Diubah

- ✅ `CreateDocument.php` - Menggunakan `->action()` bukan `->submit()`
- ✅ `EditDocument.php` - Menggunakan `->action()` bukan `->submit()`

Silakan test sekarang! 🚀
