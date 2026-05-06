<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\DocumentResource;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Dokumen')
                ->visible(fn() => auth()->user()->hasRole('Mitra')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'All' => \Filament\Resources\Components\Tab::make('All'),
            'Pending' => \Filament\Resources\Components\Tab::make('Pending')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where(function ($q) {
                    $q->where(fn($q2) => $q2->where('document_type', 'hsse')->where('hsse_status', 'pending'))
                      ->orWhere(fn($q2) => $q2->where('document_type', 'crm')->where('crm_status', 'pending'));
                })),
            'Reviewing' => \Filament\Resources\Components\Tab::make('Reviewing')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where(function ($q) {
                    $q->where(fn($q2) => $q2->where('document_type', 'hsse')->where('hsse_status', 'reviewing'))
                      ->orWhere(fn($q2) => $q2->where('document_type', 'crm')->where('crm_status', 'reviewing'));
                })),
            'Revisi' => \Filament\Resources\Components\Tab::make('Revisi')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where(function ($q) {
                    $q->where(fn($q2) => $q2->where('document_type', 'hsse')->where('hsse_status', 'revisi'))
                      ->orWhere(fn($q2) => $q2->where('document_type', 'crm')->where('crm_status', 'revisi'));
                })),
            'Approved' => \Filament\Resources\Components\Tab::make('Approved')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where(function ($q) {
                    $q->where(fn($q2) => $q2->where('document_type', 'hsse')->where('hsse_status', 'approved'))
                      ->orWhere(fn($q2) => $q2->where('document_type', 'crm')->where('crm_status', 'approved'));
                })),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        $user = auth()->user();
        if ($user && ($user->hasRole('HSSE') || $user->hasRole('CRM'))) {
            return 'Pending';
        }
        return 'All';
    }
}
