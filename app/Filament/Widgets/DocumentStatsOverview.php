<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    // Poll setiap 60 detik (bukan real-time agar tidak boros query)
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $user   = auth()->user();
        $userId = $user->id;

        // Cache key unik per user agar data tidak tercampur antar role
        $cacheKey = "doc_stats_overview_{$userId}";

        $counts = Cache::remember($cacheKey, 120, function () use ($user, $userId) {
            // ===== BASE QUERY sesuai role =====
            $query = Document::query();

            if ($user->hasRole('Mitra')) {
                $query->where('id_mitra', $userId);
            } elseif ($user->hasRole('HSSE')) {
                $query->where('document_type', 'hsse')
                      ->where(function ($q) use ($userId) {
                          $q->where('id_hsse', $userId)
                            ->orWhere('hsse_status', 'pending');
                      });
            } elseif ($user->hasRole('CRM')) {
                $query->where('document_type', 'crm')
                      ->where(function ($q) use ($userId) {
                          $q->where('id_crm', $userId)
                            ->orWhere('crm_status', 'pending');
                      });
            }
            // Admin: tidak ada filter, lihat semua

            // ===== Gunakan 1 GROUP BY query — DB yang hitung, bukan PHP =====
            $statusField = match (true) {
                $user->hasRole('HSSE') => 'hsse_status',
                $user->hasRole('CRM')  => 'crm_status',
                default                => "IF(document_type = 'hsse', hsse_status, crm_status)",
            };

            $rows = (clone $query)
                ->selectRaw("({$statusField}) as status_val, COUNT(*) as cnt")
                ->groupByRaw("({$statusField})")
                ->pluck('cnt', 'status_val');

            $total     = $rows->sum();
            $pending   = (int) ($rows['pending']   ?? 0);
            $reviewing = (int) ($rows['reviewing'] ?? 0);
            $revisi    = (int) ($rows['revisi']    ?? 0);
            $approved  = (int) ($rows['approved']  ?? 0);
            $rejected  = (int) ($rows['rejected']  ?? 0);

            return compact('total', 'pending', 'reviewing', 'revisi', 'approved', 'rejected');
        });

        return [
            Stat::make('Total Dokumen', $counts['total'])
                ->description('Total semua dokumen')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Pending', $counts['pending'])
                ->description($user->hasRole('HSSE') ? 'Pending HSSE' : ($user->hasRole('CRM') ? 'Pending CRM' : 'Menunggu review'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray')
                ->chart(array_fill(0, 7, max(1, $counts['pending']))),

            Stat::make('Dalam Review', $counts['reviewing'])
                ->description($user->hasRole('HSSE') ? 'Reviewing HSSE' : ($user->hasRole('CRM') ? 'Reviewing CRM' : 'Sedang direview'))
                ->descriptionIcon('heroicon-m-eye')
                ->color('warning')
                ->chart(array_fill(0, 7, max(1, $counts['reviewing']))),

            Stat::make('Revisi', $counts['revisi'])
                ->description('Perlu perbaikan')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info')
                ->chart(array_fill(0, 7, max(1, $counts['revisi']))),

            Stat::make('Approved', $counts['approved'])
                ->description($user->hasRole('HSSE') ? 'Disetujui HSSE' : ($user->hasRole('CRM') ? 'Disetujui CRM' : 'Fully Approved'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart(array_fill(0, 7, max(1, $counts['approved']))),

            Stat::make('Rejected', $counts['rejected'])
                ->description($user->hasRole('HSSE') ? 'Ditolak HSSE' : ($user->hasRole('CRM') ? 'Ditolak CRM' : 'Ditolak'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart(array_fill(0, 7, max(1, $counts['rejected']))),
        ];
    }
}
