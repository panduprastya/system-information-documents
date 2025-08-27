<?php

namespace App\Observers;

use App\Models\document;
use Illuminate\Support\Facades\Auth;

class DocumentObserver
{
    /**
     * Handle the document "creating" event.
     */
    public function creating(document $document): void
    {
        $document->id_mitra = Auth()->id();
        $document->tanggal_upload = now();
        //$document->hsse_id = auth()->id();
    }

    /**
     * Handle the document "updating" event.
     */
    public function updating(document $document): void
    {
        // Tidak perlu memperbarui id_hsse dan id_snd secara otomatis di sini
        // karena sudah ditangani di controller sesuai dengan peran pengguna
    }

    /**
     * Handle the Document "created" event.
     */
    public function created(Document $document): void
    {
        //
    }

    /**
     * Handle the Document "updated" event.
     */
    public function updated(Document $document): void
    {
        //
    }

    /**
     * Handle the Document "deleted" event.
     */
    public function deleted(Document $document): void
    {
        //
    }

    /**
     * Handle the Document "restored" event.
     */
    public function restored(Document $document): void
    {
        //
    }

    /**
     * Handle the Document "force deleted" event.
     */
    public function forceDeleted(Document $document): void
    {
        //
    }
}
