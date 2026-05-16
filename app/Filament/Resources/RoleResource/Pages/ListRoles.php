<?php

namespace App\Filament\Resources\RoleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = \App\Filament\Resources\RoleResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Role'),
        ];
    }
}
