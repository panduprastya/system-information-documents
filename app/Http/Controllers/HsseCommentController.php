<?php

namespace App\Http\Controllers;

use App\Models\document;
use App\Models\HsseComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class HsseCommentController extends Controller
{
    use AuthorizesRequests;
    /**
     * Store a new HSSE comment for a document
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
            'status_before' => 'nullable|string|in:pending,review,approved,rejected',
            'status_after' => 'nullable|string|in:pending,review,approved,rejected',
        ]);

        // Pastikan user yang login adalah HSSE
        if (Auth::user()->role !== 'HSSE') {
            return response()->json([
                'message' => 'Unauthorized. Only HSSE users can add HSSE comments.'
            ], 403);
        }

        $comment = HsseComment::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'komentar' => $request->komentar,
            'notes_reference' => $request->notes_reference,
            'notes_line_number' => $request->notes_line_number,
            'notes_excerpt' => $request->notes_excerpt,
            'status_before' => $request->status_before,
            'status_after' => $request->status_after,
        ]);

        // Update status dokumen jika diperlukan
        if ($request->status_after) {
            $document->update(['hsse_status' => $request->status_after]);
        }

        return response()->json([
            'message' => 'HSSE comment added successfully',
            'comment' => $comment->load('user'),
        ], 201);
    }

    /**
     * Get all HSSE comments for a document
     *
     * @param  \App\Models\document  $document
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(document $document)
    {
        $comments = $document->hsseComments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'comments' => $comments,
            'total' => $comments->count(),
        ]);
    }

    /**
     * Update an existing HSSE comment
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\HsseComment  $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, HsseComment $comment)
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
            'message' => 'HSSE comment updated successfully',
            'comment' => $comment->load('user'),
        ]);
    }

    /**
     * Mark comment as resolved
     *
     * @param  \App\Models\HsseComment  $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function resolve(HsseComment $comment)
    {
        $this->authorize('update', $comment);

        $comment->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'HSSE comment marked as resolved',
            'comment' => $comment->load('user'),
        ]);
    }

    /**
     * Delete an HSSE comment
     *
     * @param  \App\Models\HsseComment  $comment
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(HsseComment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'HSSE comment deleted successfully'
        ]);
    }
}
