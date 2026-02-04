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

        } elseif ($user->hasAnyRole(['S&D', 'SND'])) {
            if (empty($document->id_snd)) {
                $document->id_snd = $user->id;
            }

            $document->snd_status = 'approved';
            $document->id_snd = $user->id;
            $document->save();

            Notification::make()
                ->title('Document S&D Status Approved')
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

        } elseif ($user->hasAnyRole(['S&D', 'SND'])) {
            if (empty($document->id_snd)) {
                $document->id_snd = $user->id;
            }

            $document->snd_status = 'rejected';
            $document->save();

            // Create comment with rejection reason
            $document->sndComments()->create([
                'user_id' => $user->id,
                'komentar' => $reason,
                'status_after' => 'rejected',
            ]);

            Notification::make()
                ->title('Document S&D Status Rejected')
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
