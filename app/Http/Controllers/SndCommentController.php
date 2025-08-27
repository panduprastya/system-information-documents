<?php

namespace App\Http\Controllers;

use App\Models\document;
use App\Models\SndComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SndCommentController extends Controller
{
    use AuthorizesRequests;
    /**
     * Store a new S&D comment for a document
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\document  $document
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, document $document)
    {
        $request->validate([
            'komentar' => 'required|string|max:5000',
            'notes_reference' => 'nullable|string|max:255',
            'notes_line_number' => 'nullable|integer|min:1',
            'notes_excerpt' => 'nullable|string|max:1000',
            'status_before' => 'nullable|string|in:pending,review,approved,rejected,revisi',
            'status_after' => 'nullable|string|in:pending,review,approved,rejected,revisi',
        ]);

        // Pastikan user yang login adalah S&D
        $user = Auth::user();
        
        // Cek apakah user memiliki role S&D menggunakan Spatie Permission
        if (!$user->hasRole('S&D')) {
            return response()->json([
                'message' => 'Unauthorized. Only S&D users can add S&D comments.'
            ], 403);
        }

        // Simpan status sebelumnya
        $statusBefore = $document->snd_status;

        // Buat komentar baru
        $comment = SndComment::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'komentar' => $request->komentar,
            'notes_reference' => $request->notes_reference,
            'notes_line_number' => $request->notes_line_number,
            'notes_excerpt' => $request->notes_excerpt,
            'status_before' => $statusBefore,
            'status_after' => 'revisi', // Force status menjadi revisi untuk S&D
        ]);

        // Update status dokumen menjadi revisi untuk S&D
        $document->update(['snd_status' => 'revisi']);

        return response()->json([
            'message' => 'S&D comment added successfully',
            'comment' => $comment->load('user'),
            'user_name' => $user->name, // Tambahkan nama user yang login
            'new_status' => 'revisi',
            'hsse_status_unchanged' => $document->hsse_status // Konfirmasi HSSE status tidak berubah
        ], 201);
    }

    /**
     * Get all S&D comments for a document
     *
     * @param  \App\Models\document  $document
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(document $document)
    {
        $comments = $document->sndComments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'comments' => $comments,
            'total' => $comments->count(),
        ]);
    }

    /**
     * Update an existing S&D comment
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SndComment  $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, SndComment $comment)
    {
        $this->authorize('update', $comment);

        $request->validate([
            'komentar' => 'required|string|max:5000',
            'notes_reference' => 'nullable|string|max:255',
            'notes_line_number' => 'nullable|integer|min:1',
            'notes_excerpt' => 'nullable|string|max:1000',
            'status_before' => 'nullable|string|in:pending,review,approved,rejected',
            'status_after' => 'nullable|string|in:pending,review,approved,rejected',
            'is_resolved' => 'boolean',
        ]);

        $comment->update($request->all());

        if ($request->is_resolved) {
            $comment->update([
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);
        }

        return response()->json([
            'message' => 'S&D comment updated successfully',
            'comment' => $comment->load('user'),
        ]);
    }

    /**
     * Mark comment as resolved
     *
     * @param  \App\Models\SndComment  $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function resolve(SndComment $comment)
    {
        $this->authorize('update', $comment);

        $comment->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'S&D comment marked as resolved',
            'comment' => $comment->load('user'),
        ]);
    }

    /**
     * Delete an S&D comment
     *
     * @param  \App\Models\SndComment  $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(SndComment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'S&D comment deleted successfully'
        ]);
    }
}
