<?php

namespace App\Filament\Resources\RoleResource\Pages;

use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\EditRole as BaseEditRole;
use Filament\Actions;

class EditRole extends BaseEditRole
{
    protected static string $resource = \App\Filament\Resources\RoleResource::class;

    public function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
