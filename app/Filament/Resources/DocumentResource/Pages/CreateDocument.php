<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Jika user adalah Mitra, otomatis isi id_mitra
        if ($user && $user->hasRole('Mitra')) {
            $data['id_mitra'] = $user->id;
        }

        return $data;
    }

    protected function getFormActions(): array
    {
        $user = auth()->user();

        // Jika user adalah Mitra, hanya tampilkan Create dan Cancel
        if ($user && $user->hasRole('Mitra')) {
            return [
                $this->getCreateFormAction(),
                $this->getCancelFormAction(),
            ];
        }

        // Untuk role lain, tampilkan semua actions default
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
