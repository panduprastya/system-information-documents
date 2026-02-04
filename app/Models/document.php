<?php

namespace App\Models;

use App\Observers\DocumentObserver;
use App\Scopes\MitraDocumentScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(DocumentObserver::class)]
class document extends Model
{
    use SoftDeletes;

    protected static function booted()
    {
        static::addGlobalScope(new MitraDocumentScope);
    }

    protected $fillable = [
        'judul_dokumen',
        'id_mitra',
        'file',
        'status',
        'hsse_status',
        'snd_status',
        'tanggal_upload',
        'tanggal_acc',
        'hsse_review_started_at',
        'snd_review_started_at',
        'id_hsse',
        'id_snd',
        'notes',
        'keterangan',
        'edited_by',
        'last_edited_at',
    ];

    /**
     * Get the mitra (partner) that owns the document
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mitra(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_mitra', 'id');
    }

    public function hsse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_hsse', 'id');
    }

    public function snd(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_snd', 'id');
    }

    /**
     * Alias for mitra relationship for backward compatibility
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->mitra();
    }

    public function hsseComments(): HasMany
    {
        return $this->hasMany(HsseComment::class, 'document_id');
    }

    public function sndComments(): HasMany
    {
        return $this->hasMany(SndComment::class, 'document_id');
    }



    /**
     * Get all comments for the document
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function comments(): HasMany
    // {
    //     return $this->hasMany(HsseComment::class, 'document_id');
    // }

    /**
     * Get HSSE comments for the document
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function hsseComments(): HasMany
    // {
    //     return $this->hasMany(HsseComment::class, 'document_id')
    //         ->where('reviewer_type', 'hsse');
    // }

    /**
     * Get S&D comments for the document
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    //public function sndComments(): HasMany
    // {
    //     return $this->hasMany(HsseComment::class, 'document_id')
    //         ->where('reviewer_type', 'snd');
    // }

    /**
     * Check if document has pending HSSE review
     *
     * @return bool
     */
    // public function needsHsseReview(): bool
    // {
    //     return $this->hsse_status === 'pending' || $this->hsse_status === 'reviewing';
    // }

    /**
     * Check if document has pending S&D review
     *
     * @return bool
     */
    // public function needsSndReview(): bool
    // {
    //     return $this->snd_status === 'pending' || $this->snd_status === 'reviewing';
    // }

    /**
     * Check if all reviews are complete
     *
     * @return bool
     */
    // public function allReviewsComplete(): bool
    // {
    //     return $this->hsse_status !== 'pending' && $this->hsse_status !== 'reviewing' &&
    //            $this->snd_status !== 'pending' && $this->snd_status !== 'reviewing';
    // }

    /**
     * Get comments related to notes field
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function notesComments(): HasMany
    // {
    //     return $this->hasMany(HsseComment::class, 'document_id')
    //         ->whereNotNull('notes_reference');
    // }

    /**
     * Check if document can be edited by mitra
     *
     * @return bool
     */
    public function canBeEditedByMitra(): bool
    {
        return $this->hsse_status === 'revisi' || $this->snd_status === 'revisi';
    }

    /**
     * Check if document needs revision
     *
     * @return bool
     */
    public function needsRevision(): bool
    {
        return $this->hsse_status === 'revisi' || $this->snd_status === 'revisi';
    }

    /**
     * Get revision reasons from comments
     *
     * @return array
     */
    public function getRevisionReasons(): array
    {
        $reasons = [];

        if ($this->hsse_status === 'revisi') {
            $latestHsseComment = $this->hsseComments()->latest()->first();
            if ($latestHsseComment) {
                $reasons[] = [
                    'type' => 'HSSE',
                    'comment' => $latestHsseComment->komentar,
                    'reviewer' => $latestHsseComment->user->name ?? 'Unknown',
                    'date' => $latestHsseComment->created_at
                ];
            }
        }

        if ($this->snd_status === 'revisi') {
            $latestSndComment = $this->sndComments()->latest()->first();
            if ($latestSndComment) {
                $reasons[] = [
                    'type' => 'S&D',
                    'comment' => $latestSndComment->komentar ?? 'No comment',
                    'reviewer' => $latestSndComment->user->name ?? 'Unknown',
                    'date' => $latestSndComment->created_at
                ];
            }
        }

        return $reasons;
    }
}
