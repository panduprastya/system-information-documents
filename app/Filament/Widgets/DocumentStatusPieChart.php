<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Widgets\ChartWidget;

class DocumentStatusPieChart extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Status Dokumen';

    protected static ?int $sort = 3;

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
        } elseif ($user->hasAnyRole(['CRM', 'CRM'])) {
            $query->where(function ($q) use ($user) {
                $q->where('id_crm', $user->id)
                    ->orWhere('crm_status', 'pending');
            });
        }

        // Pending: logika berbeda berdasarkan role
        if ($user->hasRole('HSSE')) {
            $pendingCount = (clone $query)->where('hsse_status', 'pending')->count();
        } elseif ($user->hasAnyRole(['CRM', 'CRM'])) {
            $pendingCount = (clone $query)->where('crm_status', 'pending')->count();
        } else {
            $pendingCount = (clone $query)->where('hsse_status', 'pending')
                ->where('crm_status', 'pending')
                ->count();
        }

        // Reviewing: logika berbeda berdasarkan role
        if ($user->hasRole('HSSE')) {
            $reviewingCount = (clone $query)->where('hsse_status', 'reviewing')->count();
        } elseif ($user->hasAnyRole(['CRM', 'CRM'])) {
            $reviewingCount = (clone $query)->where('crm_status', 'reviewing')->count();
        } else {
            $reviewingCount = (clone $query)->where(function ($q) {
                $q->where('hsse_status', 'reviewing')
                    ->orWhere('crm_status', 'reviewing');
            })->count();
        }

        $revisiCount = (clone $query)->where(function ($q) {
            $q->where('hsse_status', 'revisi')
                ->orWhere('crm_status', 'revisi');
        })->count();

        // Approved: logika berbeda berdasarkan role
        if ($user->hasRole('HSSE')) {
            $approvedCount = (clone $query)->where('hsse_status', 'approved')->count();
        } elseif ($user->hasAnyRole(['CRM', 'CRM'])) {
            $approvedCount = (clone $query)->where('crm_status', 'approved')->count();
        } else {
            $approvedCount = (clone $query)->where('hsse_status', 'approved')
                ->where('crm_status', 'approved')
                ->count();
        }

        // Rejected: logika berbeda berdasarkan role
        if ($user->hasRole('HSSE')) {
            $rejectedCount = (clone $query)->where('hsse_status', 'rejected')->count();
        } elseif ($user->hasAnyRole(['CRM', 'CRM'])) {
            $rejectedCount = (clone $query)->where('crm_status', 'rejected')->count();
        } else {
            $rejectedCount = (clone $query)->where(function ($q) {
                $q->where('hsse_status', 'rejected')
                    ->orWhere('crm_status', 'rejected');
            })->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Status Dokumen',
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
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
