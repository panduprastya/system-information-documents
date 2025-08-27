<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\ViewAction::make(),
            // Actions\Action::make('back')
            // ->label('Back')
            // ->url(route('filament.admin.resources.users.index')) //Untuk kembali ke halaman users
            // ->icon('heroicon-o-arrow-left')
            // ->extraAttributes([
            //     'style' => 'background-color: #1235A2; color: #fff; border-color: #1235A2;',
            // ]),
            // Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
        ];
    }
}
