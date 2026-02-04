<?php

namespace App\Filament\Resources\RoleResource\Pages;

use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource\Pages\ListRoles as BaseListRoles;
use Filament\Actions;

class ListRoles extends BaseListRoles
{
    protected static string $resource = \App\Filament\Resources\RoleResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
