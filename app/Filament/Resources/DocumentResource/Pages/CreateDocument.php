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
}
