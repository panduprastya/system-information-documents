<?php

namespace App\Policies;

use App\Models\User;
use App\Models\document;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Semua user yang terautentikasi dapat melihat daftar dokumen
        // Filter akan diterapkan di query level
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, document $document): bool
    {
        // Admin, HSSE, S&D dapat melihat semua dokumen
        if ($user->hasRole(['Admin', 'HSSE', 'S&D'])) {
            return true;
        }
        
        // Mitra hanya dapat melihat dokumen mereka sendiri
        if ($user->hasRole('Mitra')) {
            return $document->id_mitra === $user->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Hanya Mitra yang dapat membuat dokumen
        return $user->hasRole('Mitra');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, document $document): bool
    {
        // Admin tidak dapat mengedit dokumen
        if ($user->hasRole('Admin')) {
            return false;
        }
        
        // Mitra hanya dapat mengedit dokumen mereka sendiri
        if ($user->hasRole('Mitra')) {
            return $document->id_mitra === $user->id;
        }
        
        // HSSE dan S&D dapat mengedit dokumen
        if ($user->hasRole(['HSSE', 'S&D'])) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, document $document): bool
    {
        // Hanya Admin yang dapat menghapus dokumen
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, document $document): bool
    {
        // Hanya Admin yang dapat restore dokumen
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, document $document): bool
    {
        // Hanya Admin yang dapat force delete dokumen
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can sign the document.
     */
    public function signDocument(User $user, Document $document, string $signatureType): bool
    {
        // Check if user is authenticated
        if (!$user) {
            return false;
        }

        // Check if document exists
        if (!$document) {
            return false;
        }

        // Check signature type
        if (!in_array($signatureType, ['hsse', 'snd'])) {
            return false;
        }

        // HSSE can only sign if they are assigned as HSSE reviewer
        if ($signatureType === 'hsse') {
            return $user->hasRole('HSSE') && 
                   $document->id_hsse === $user->id &&
                   $document->hsse_status === 'approved';
        }

        // SND can only sign if they are assigned as SND reviewer
        if ($signatureType === 'snd') {
            return $user->hasRole('SND') && 
                   $document->id_snd === $user->id &&
                   $document->snd_status === 'approved';
        }

        return false;
    }
}
