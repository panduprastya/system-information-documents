<?php

namespace App\Filament\Resources\RoleResource\Pages;

use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\CreateRole as BaseCreateRole;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends BaseCreateRole
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
}
