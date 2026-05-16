<?php

namespace App\Filament\Resources\RoleResource\Pages;

use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = \App\Filament\Resources\RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Hilangkan tombol "Create & create another"
    public function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Save');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'role berhasil disimpan';
    }
}
