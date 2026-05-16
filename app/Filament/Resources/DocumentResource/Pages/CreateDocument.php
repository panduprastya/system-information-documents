<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Jika user adalah Mitra, otomatis isi id_user
        if ($user && $user->hasRole('Mitra')) {
            $data['id_user'] = $user->id_user;
        }

        return $data;
    }

    protected function getFormActions(): array
    {
        $user = auth()->user();

        // Jika user adalah Mitra, tambahkan konfirmasi
        if ($user && $user->hasRole('Mitra')) {
            return [
                \Filament\Actions\Action::make('create')
                    ->label('Kirim Dokumen')
                    ->requiresConfirmation()
                    ->modalHeading('⚠️ Konfirmasi Pengiriman Dokumen')
                    ->modalDescription('Pastikan semua informasi yang Anda masukkan sudah benar sebelum mengirim dokumen ini untuk direview. Setelah dokumen dikirim, status akan menjadi "Pending" dan akan diproses oleh reviewer. Apakah Anda yakin ingin mengirim dokumen ini?')
                    ->modalSubmitActionLabel('Ya, Kirim Dokumen')
                    ->modalCancelActionLabel('Batal')
                    ->modalIcon('heroicon-o-paper-airplane')
                    ->modalIconColor('success')
                    ->color('success')
                    ->action(function () {
                        // Validate and get form data
                        $data = $this->form->getState();

                        // Mutate data before create
                        $data = $this->mutateFormDataBeforeCreate($data);

                        // Create the record
                        $this->record = $this->handleRecordCreation($data);

                        // Show success notification
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Dokumen Berhasil Dikirim')
                            ->body('Dokumen Anda telah berhasil dikirim dan akan segera direview.')
                            ->send();

                        // Redirect to index
                        $this->redirect($this->getRedirectUrl());
                    }),
                \Filament\Actions\Action::make('cancel')
                    ->label('Batal')
                    ->url($this->getResource()::getUrl('index'))
                    ->color('gray'),
            ];
        }

        return parent::getFormActions();
    }

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman list dokumen setelah create
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationActions(): array
    {
        // Menghilangkan button "View" pada notifikasi sukses
        // Hanya menampilkan button kembali ke list
        return [];
    }
}
