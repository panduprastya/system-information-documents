<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Widgets\ChartWidget;

class DocumentStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Statistik Status Dokumen';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = auth()->user();

        // Base query berdasarkan role
        $query = Document::query();

        if ($user->hasRole('Mitra')) {
            $query->where('id_mitra', $user->id);
        } elseif ($user->hasRole('HSSE')) {
            $query->where(function ($q) use ($user) {
                $q->where('id_hsse', $user->id)
                    ->orWhere('hsse_status', 'pending');
            });
        } elseif ($user->hasAnyRole(['S&D', 'SND'])) {
            $query->where(function ($q) use ($user) {
                $q->where('id_snd', $user->id)
                    ->orWhere('snd_status', 'pending');
            });
        }

        // Pending: logika berbeda berdasarkan role
        if ($user->hasRole('HSSE')) {
            $pendingCount = (clone $query)->where('hsse_status', 'pending')->count();
            $reviewingCount = (clone $query)->where('hsse_status', 'reviewing')->count();
            $approvedCount = (clone $query)->where('hsse_status', 'approved')->count();
            $rejectedCount = (clone $query)->where('hsse_status', 'rejected')->count();
        } elseif ($user->hasAnyRole(['S&D', 'SND'])) {
            $pendingCount = (clone $query)->where('snd_status', 'pending')->count();
            $reviewingCount = (clone $query)->where('snd_status', 'reviewing')->count();
            $approvedCount = (clone $query)->where('snd_status', 'approved')->count();
            $rejectedCount = (clone $query)->where('snd_status', 'rejected')->count();
        } else {
            $pendingCount = (clone $query)->where('hsse_status', 'pending')
                ->where('snd_status', 'pending')
                ->count();
            $reviewingCount = (clone $query)->where(function ($q) {
                $q->where('hsse_status', 'reviewing')
                    ->orWhere('snd_status', 'reviewing');
            })->count();
            $approvedCount = (clone $query)->where('hsse_status', 'approved')
                ->where('snd_status', 'approved')
                ->count();
            $rejectedCount = (clone $query)->where(function ($q) {
                $q->where('hsse_status', 'rejected')
                    ->orWhere('snd_status', 'rejected');
            })->count();
        }

        $revisiCount = (clone $query)->where(function ($q) {
            $q->where('hsse_status', 'revisi')
                ->orWhere('snd_status', 'revisi');
        })->count();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Dokumen',
                    'data' => [$pendingCount, $reviewingCount, $revisiCount, $approvedCount, $rejectedCount],
                    'backgroundColor' => [
                        'rgba(156, 163, 175, 0.8)', // Gray - Pending
                        'rgba(251, 191, 36, 0.8)',  // Amber - Reviewing
                        'rgba(59, 130, 246, 0.8)',  // Blue - Revisi
                        'rgba(34, 197, 94, 0.8)',   // Green - Approved
                        'rgba(239, 68, 68, 0.8)',   // Red - Rejected
                    ],
                    'borderColor' => [
                        'rgb(156, 163, 175)',
                        'rgb(251, 191, 36)',
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 2,
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
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
