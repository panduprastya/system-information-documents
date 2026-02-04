<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        // Base query berdasarkan role
        $query = Document::query();

        if ($user->hasRole('Mitra')) {
            // Mitra hanya melihat dokumen mereka sendiri
            $query->where('id_mitra', $user->id);
        } elseif ($user->hasRole('HSSE')) {
            // HSSE melihat dokumen yang assigned ke mereka atau pending HSSE review
            $query->where(function ($q) use ($user) {
                $q->where('id_hsse', $user->id)
                    ->orWhere('hsse_status', 'pending');
            });
        } elseif ($user->hasAnyRole(['S&D', 'SND'])) {
            // S&D melihat dokumen yang assigned ke mereka atau pending S&D review
            $query->where(function ($q) use ($user) {
                $q->where('id_snd', $user->id)
                    ->orWhere('snd_status', 'pending');
            });
        }
        // Admin melihat semua dokumen (tidak ada filter tambahan)

        // Hitung dokumen berdasarkan status
        $totalDocuments = (clone $query)->count();

        // Pending: logika berbeda berdasarkan role
        if ($user->hasRole('HSSE')) {
            // HSSE melihat dokumen dengan hsse_status = pending
            $pendingCount = (clone $query)->where('hsse_status', 'pending')->count();
        } elseif ($user->hasAnyRole(['S&D', 'SND'])) {
            // S&D melihat dokumen dengan snd_status = pending
            $pendingCount = (clone $query)->where('snd_status', 'pending')->count();
        } else {
            // Admin dan Mitra melihat dokumen dengan kedua status pending
            $pendingCount = (clone $query)->where('hsse_status', 'pending')
                ->where('snd_status', 'pending')
                ->count();
        }

        // Reviewing: logika berbeda berdasarkan role
        if ($user->hasRole('HSSE')) {
            // HSSE melihat dokumen yang sedang mereka review
            $reviewingCount = (clone $query)->where('hsse_status', 'reviewing')->count();
        } elseif ($user->hasAnyRole(['S&D', 'SND'])) {
            // S&D melihat dokumen yang sedang mereka review
            $reviewingCount = (clone $query)->where('snd_status', 'reviewing')->count();
        } else {
            // Admin dan Mitra melihat dokumen yang sedang di-review (salah satu atau kedua)
            $reviewingCount = (clone $query)->where(function ($q) {
                $q->where('hsse_status', 'reviewing')
                    ->orWhere('snd_status', 'reviewing');
            })->count();
        }

        // Revisi: salah satu atau kedua status revisi
        $revisiCount = (clone $query)->where(function ($q) {
            $q->where('hsse_status', 'revisi')
                ->orWhere('snd_status', 'revisi');
        })->count();


        // Approved: logika berbeda berdasarkan role
        if ($user->hasRole('HSSE')) {
            // HSSE melihat dokumen yang mereka sudah approve
            $approvedCount = (clone $query)->where('hsse_status', 'approved')->count();
        } elseif ($user->hasAnyRole(['S&D', 'SND'])) {
            // S&D melihat dokumen yang mereka sudah approve
            $approvedCount = (clone $query)->where('snd_status', 'approved')->count();
        } else {
            // Admin dan Mitra melihat dokumen yang fully approved (kedua status approved)
            $approvedCount = (clone $query)->where('hsse_status', 'approved')
                ->where('snd_status', 'approved')
                ->count();
        }

        // Rejected: logika berbeda berdasarkan role
        if ($user->hasRole('HSSE')) {
            // HSSE melihat dokumen yang mereka reject
            $rejectedCount = (clone $query)->where('hsse_status', 'rejected')->count();
        } elseif ($user->hasAnyRole(['S&D', 'SND'])) {
            // S&D melihat dokumen yang mereka reject
            $rejectedCount = (clone $query)->where('snd_status', 'rejected')->count();
        } else {
            // Admin dan Mitra melihat dokumen yang di-reject (salah satu atau kedua)
            $rejectedCount = (clone $query)->where(function ($q) {
                $q->where('hsse_status', 'rejected')
                    ->orWhere('snd_status', 'rejected');
            })->count();
        }


        return [
            Stat::make('Total Dokumen', $totalDocuments)
                ->description('Total semua dokumen')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Pending', $pendingCount)
                ->description($user->hasRole('HSSE') ? 'Pending HSSE' : ($user->hasAnyRole(['S&D', 'SND']) ? 'Pending S&D' : 'Menunggu review'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray')
                ->chart(array_fill(0, 7, $pendingCount)),

            Stat::make('Dalam Review', $reviewingCount)
                ->description($user->hasRole('HSSE') ? 'Reviewing HSSE' : ($user->hasAnyRole(['S&D', 'SND']) ? 'Reviewing S&D' : 'Sedang direview'))
                ->descriptionIcon('heroicon-m-eye')
                ->color('warning')
                ->chart(array_fill(0, 7, $reviewingCount)),

            Stat::make('Revisi', $revisiCount)
                ->description('Perlu perbaikan')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info')
                ->chart(array_fill(0, 7, $revisiCount)),

            Stat::make('Approved', $approvedCount)
                ->description($user->hasRole('HSSE') ? 'Disetujui HSSE' : ($user->hasAnyRole(['S&D', 'SND']) ? 'Disetujui S&D' : 'Fully Approved'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart(array_fill(0, 7, $approvedCount)),

            Stat::make('Rejected', $rejectedCount)
                ->description($user->hasRole('HSSE') ? 'Ditolak HSSE' : ($user->hasAnyRole(['S&D', 'SND']) ? 'Ditolak S&D' : 'Ditolak'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart(array_fill(0, 7, $rejectedCount)),
        ];
    }
}
