<?php

namespace App\Http\Controllers;

use App\Models\document;
use App\Models\CrmComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CrmCommentController extends Controller
{
    use AuthorizesRequests;
    /**
     * Store a new CRM comment for a document
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\document  $document
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, document $document)
    {
        $request->validate([
            'komentar' => 'required|string|max:5000',
            'status_after' => 'nullable|string|in:pending,review,approved,rejected,revisi',
        ]);

        // Pastikan user yang login adalah CRM
        $user = Auth::user();

        // Cek apakah user memiliki role CRM menggunakan Spatie Permission
        if (!$user->hasRole('CRM')) {
            return response()->json([
                'message' => 'Unauthorized. Only CRM users can add CRM comments.'
            ], 403);
        }

        // Buat komentar baru
        $comment = CrmComment::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'komentar' => $request->komentar,
            'status_after' => 'revisi', // Force status menjadi revisi untuk CRM
        ]);

        // Update status dokumen menjadi revisi untuk CRM
        $document->update(['crm_status' => 'revisi']);

        return response()->json([
            'message' => 'CRM comment added successfully',
            'comment' => $comment->load('user'),
            'user_name' => $user->name, // Tambahkan nama user yang login
            'new_status' => 'revisi',
            'hsse_status_unchanged' => $document->hsse_status // Konfirmasi HSSE status tidak berubah
        ], 201);
    }

    /**
     * Get all CRM comments for a document
     *
     * @param  \App\Models\document  $document
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(document $document)
    {
        $comments = $document->crmComments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'comments' => $comments,
            'total' => $comments->count(),
        ]);
    }

    /**
     * Delete a CRM comment
     *
     * @param  \App\Models\CrmComment  $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(CrmComment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'CRM comment deleted successfully'
        ]);
    }
}
