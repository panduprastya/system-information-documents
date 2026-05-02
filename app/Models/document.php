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
        'document_type',
        'file',
        'status',
        'hsse_status',
        'crm_status',
        'tanggal_upload',
        'tanggal_acc',
        'hsse_review_started_at',
        'crm_review_started_at',
        'id_hsse',
        'id_crm',
        'notes',
        'keterangan',
        'edited_by',
        'last_edited_at',
    ];

    protected $casts = [
        'tanggal_upload' => 'datetime',
        'tanggal_acc' => 'datetime',
        'hsse_review_started_at' => 'datetime',
        'crm_review_started_at' => 'datetime',
        'last_edited_at' => 'datetime',
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

    public function crm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_crm', 'id');
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

    public function crmComments(): HasMany
    {
        return $this->hasMany(CrmComment::class, 'document_id');
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
     * Get CRM comments for the document
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    //public function crmComments(): HasMany
    // {
    //     return $this->hasMany(HsseComment::class, 'document_id')
    //         ->where('reviewer_type', 'crm');
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
     * Check if document has pending CRM review
     *
     * @return bool
     */
    // public function needsCrmReview(): bool
    // {
    //     return $this->crm_status === 'pending' || $this->crm_status === 'reviewing';
    // }

    /**
     * Check if all reviews are complete
     *
     * @return bool
     */
    // public function allReviewsComplete(): bool
    // {
    //     return $this->hsse_status !== 'pending' && $this->hsse_status !== 'reviewing' &&
    //            $this->crm_status !== 'pending' && $this->crm_status !== 'reviewing';
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
        return $this->hsse_status === 'revisi' || $this->crm_status === 'revisi';
    }

    /**
     * Check if document needs revision
     *
     * @return bool
     */
    public function needsRevision(): bool
    {
        return $this->hsse_status === 'revisi' || $this->crm_status === 'revisi';
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

        if ($this->crm_status === 'revisi') {
            $latestCrmComment = $this->crmComments()->latest()->first();
            if ($latestCrmComment) {
                $reasons[] = [
                    'type' => 'CRM',
                    'comment' => $latestCrmComment->komentar ?? 'No comment',
                    'reviewer' => $latestCrmComment->user->name ?? 'Unknown',
                    'date' => $latestCrmComment->created_at
                ];
            }
        }

        return $reasons;
    }

    /**
     * Scope untuk filter dokumen HSSE
     */
    public function scopeForHsse($query)
    {
        return $query->where('document_type', 'hsse');
    }

    /**
     * Scope untuk filter dokumen CRM
     */
    public function scopeForCrm($query)
    {
        return $query->where('document_type', 'crm');
    }

    /**
     * Check if document is for HSSE
     */
    public function isForHsse(): bool
    {
        return $this->document_type === 'hsse';
    }

    /**
     * Check if document is for CRM
     */
    public function isForCrm(): bool
    {
        return $this->document_type === 'crm';
    }
}
