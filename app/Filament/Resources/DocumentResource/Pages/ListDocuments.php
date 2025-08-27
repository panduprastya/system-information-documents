<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\DocumentResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn() => auth()->user()->hasRole('Mitra')),
        ];
    }

    public function getTabs(): array //menampilkan filter berdasarkan status
    {
        $user = auth()->user();
        $isMitra = $user && $user->hasRole('Mitra');
        
        $tabs = [
            'all' => Tab::make(),
            'pending' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('hsse_status', 'pending')),
            'reviewing' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'reviewing')),
            'revisi' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('hsse_status', 'revisi')),
            'approved' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            'rejected' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
        ];
        
        // Jika user adalah Mitra, tambahkan filter untuk dokumen mereka sendiri
        if ($isMitra) {
            foreach ($tabs as $tab) {
                $tab->modifyQueryUsing(function (Builder $query) use ($user) {
                    return $query->where('id_mitra', $user->id);
                });
            }
        }
        
        return $tabs;
    }
}
