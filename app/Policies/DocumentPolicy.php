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
        // Admin, HSSE, CRM dapat melihat semua dokumen
        if ($user->hasRole(['Admin', 'HSSE', 'CRM'])) {
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

        // HSSE dan CRM dapat mengedit dokumen
        if ($user->hasRole(['HSSE', 'CRM'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, document $document): bool
    {
        // Admin dapat menghapus semua dokumen
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Mitra dapat menghapus dokumen mereka sendiri jika status masih pending
        if ($user->hasRole('Mitra')) {
            // Pastikan dokumen milik Mitra yang login
            if ($document->id_mitra !== $user->id) {
                return false;
            }

            // Untuk dokumen HSSE, cek hsse_status
            if ($document->document_type === 'hsse') {
                return $document->hsse_status === 'pending';
            }

            // Untuk dokumen CRM, cek crm_status
            if ($document->document_type === 'crm') {
                return $document->crm_status === 'pending';
            }

            return false;
        }

        // HSSE dan CRM tidak dapat menghapus dokumen
        return false;
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
        // Admin dapat force delete semua dokumen
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Mitra dapat force delete dokumen mereka sendiri jika status masih pending
        if ($user->hasRole('Mitra')) {
            // Pastikan dokumen milik Mitra yang login
            if ($document->id_mitra !== $user->id) {
                return false;
            }

            // Untuk dokumen HSSE, cek hsse_status
            if ($document->document_type === 'hsse') {
                return $document->hsse_status === 'pending';
            }

            // Untuk dokumen CRM, cek crm_status
            if ($document->document_type === 'crm') {
                return $document->crm_status === 'pending';
            }

            return false;
        }

        // HSSE dan CRM tidak dapat force delete dokumen
        return false;
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
        if (!in_array($signatureType, ['hsse', 'crm'])) {
            return false;
        }

        // HSSE can only sign if they are assigned as HSSE reviewer
        if ($signatureType === 'hsse') {
            return $user->hasRole('HSSE') &&
                $document->id_hsse === $user->id &&
                $document->hsse_status === 'approved';
        }

        // CRM can only sign if they are assigned as CRM reviewer
        if ($signatureType === 'crm') {
            return $user->hasRole('CRM') &&
                $document->id_crm === $user->id &&
                $document->crm_status === 'approved';
        }

        return false;
    }
}
