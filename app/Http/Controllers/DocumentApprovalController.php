<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Filament\Notifications\Notification;

class DocumentApprovalController extends Controller
{
    public function approve(Request $request, Document $document)
    {
        $user = auth()->user();

        if ($user->hasRole('HSSE')) {
            if (empty($document->id_hsse)) {
                $document->id_hsse = $user->id;
            }

            // Optional: validation if needed
            // if ((int) $document->id_hsse !== (int) $user->id) { ... }

            $document->hsse_status = 'approved';
            $document->id_hsse = $user->id;
            $document->save();

            Notification::make()
                ->title('Document HSSE Status Approved')
                ->success()
                ->send();

        } elseif ($user->hasRole('CRM')) {
            if (empty($document->id_crm)) {
                $document->id_crm = $user->id;
            }

            $document->crm_status = 'approved';
            $document->id_crm = $user->id;
            $document->save();

            Notification::make()
                ->title('Document CRM Status Approved')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('You are not authorized to approve documents.')
                ->danger()
                ->send();

            return redirect()->back();
        }

        return redirect()->to('/admin/documents');
    }

    public function showRejectForm(Document $document)
    {
        $user = auth()->user();

        // Check if user has permission to reject
        if (!$user->hasRole('HSSE') && !$user->hasRole('CRM')) {
            Notification::make()
                ->title('You are not authorized to reject documents.')
                ->danger()
                ->send();

            return redirect()->back();
        }

        // Check if document status allows rejection
        if ($user->hasRole('HSSE') && !in_array($document->hsse_status, ['pending', 'reviewing'])) {
            Notification::make()
                ->title('This document cannot be rejected.')
                ->warning()
                ->send();

            return redirect()->back();
        }

        if ($user->hasRole('CRM') && !in_array($document->crm_status, ['pending', 'reviewing'])) {
            Notification::make()
                ->title('This document cannot be rejected.')
                ->warning()
                ->send();

            return redirect()->back();
        }

        return view('documents.reject', compact('document'));
    }

    public function reject(Request $request, Document $document)
    {
        $user = auth()->user();

        // Validate the rejection reason
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $reason = $validated['rejection_reason'];

        if ($user->hasRole('HSSE')) {
            if (empty($document->id_hsse)) {
                $document->id_hsse = $user->id;
            }

            $document->hsse_status = 'rejected';
            $document->save();

            // Create comment with rejection reason
            $document->hsseComments()->create([
                'user_id' => $user->id,
                'komentar' => $reason,
                'status_after' => 'rejected',
            ]);

            Notification::make()
                ->title('Document HSSE Status Rejected')
                ->body('Dokumen berhasil ditolak.')
                ->success()
                ->send();

        } elseif ($user->hasRole('CRM')) {
            if (empty($document->id_crm)) {
                $document->id_crm = $user->id;
            }

            $document->crm_status = 'rejected';
            $document->save();

            // Create comment with rejection reason
            $document->crmComments()->create([
                'user_id' => $user->id,
                'komentar' => $reason,
                'status_after' => 'rejected',
            ]);

            Notification::make()
                ->title('Document CRM Status Rejected')
                ->body('Dokumen berhasil ditolak.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('You are not authorized to reject documents.')
                ->danger()
                ->send();

            return redirect()->back();
        }

        return redirect()->to('/admin/documents');
    }
}
