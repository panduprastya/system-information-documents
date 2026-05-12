<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\ChartWidget;

class DocumentStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Statistik Status Dokumen';

    protected static ?int $sort = 2;

    // Poll setiap 60 detik
    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $user   = auth()->user();
        $userId = $user->id;

        $cacheKey = "doc_chart_bar_{$userId}";

        $counts = Cache::remember($cacheKey, 120, function () use ($user, $userId) {
            $query = Document::query();

            if ($user->hasRole('Mitra')) {
                $query->where('id_mitra', $userId);
            } elseif ($user->hasRole('HSSE')) {
                $query->where('document_type', 'hsse')
                      ->where(function ($q) use ($userId) {
                          $q->where('id_hsse', $userId)->orWhere('hsse_status', 'pending');
                      });
            } elseif ($user->hasRole('CRM')) {
                $query->where('document_type', 'crm')
                      ->where(function ($q) use ($userId) {
                          $q->where('id_crm', $userId)->orWhere('crm_status', 'pending');
                      });
            }

            // Satu GROUP BY query — database yang menghitung, bukan PHP loop
            $statusField = match (true) {
                $user->hasRole('HSSE') => 'hsse_status',
                $user->hasRole('CRM')  => 'crm_status',
                default                => "IF(document_type = 'hsse', hsse_status, crm_status)",
            };

            $rows = $query
                ->selectRaw("({$statusField}) as status_val, COUNT(*) as cnt")
                ->groupByRaw("({$statusField})")
                ->pluck('cnt', 'status_val');

            return [
                'pending'   => (int) ($rows['pending']   ?? 0),
                'reviewing' => (int) ($rows['reviewing'] ?? 0),
                'revisi'    => (int) ($rows['revisi']    ?? 0),
                'approved'  => (int) ($rows['approved']  ?? 0),
                'rejected'  => (int) ($rows['rejected']  ?? 0),
            ];
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Jumlah Dokumen',
                    'data'            => [
                        $counts['pending'],
                        $counts['reviewing'],
                        $counts['revisi'],
                        $counts['approved'],
                        $counts['rejected'],
                    ],
                    'backgroundColor' => [
                        'rgba(156, 163, 175, 0.8)', // Gray - Pending
                        'rgba(251, 191, 36, 0.8)',  // Amber - Reviewing
                        'rgba(59, 130, 246, 0.8)',  // Blue - Revisi
                        'rgba(34, 197, 94, 0.8)',   // Green - Approved
                        'rgba(239, 68, 68, 0.8)',   // Red - Rejected
                    ],
                    'borderColor'     => [
                        'rgb(156, 163, 175)',
                        'rgb(251, 191, 36)',
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => ['Pending', 'Dalam Review', 'Revisi', 'Approved', 'Rejected'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'scales'  => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => ['stepSize' => 1],
                ],
            ],
        ];
    }
}
