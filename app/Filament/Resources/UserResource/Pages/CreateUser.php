<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Save');
    }

    protected function getValidationFailureNotification(): \Filament\Notifications\Notification
    {
        return \Filament\Notifications\Notification::make()
            ->title('Gagal Menyimpan')
            ->body('Ada kolom yang masih kosong atau tidak valid, silakan periksa kembali.')
            ->danger();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'akun pengguna berhasil disimpan';
    }
}
