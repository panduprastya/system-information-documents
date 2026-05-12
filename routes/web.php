<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\DocumentDownloadController;


Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/livewire/update', function () {
    // Redirect to admin panel if accessed via GET
    return redirect('/admin');
});

Route::get('/pdf/viewer/{file}', function ($file) {
    // Decode filename
    $filename = urldecode($file);

    // Cari file di storage public
    $path = storage_path('app/public/' . $filename);

    // Jika file tidak ditemukan, coba dengan basename
    if (!file_exists($path)) {
        $path = storage_path('app/public/' . basename($filename));
    }

    if (!file_exists($path)) {
        abort(404, 'File not found');
    }

    // Return file dengan headers yang benar
    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . basename($filename) . '"',
        'X-Frame-Options' => 'SAMEORIGIN',
    ]);
})->name('pdf.viewer');

// Protected routes for authenticated users
Route::middleware(['auth'])->group(function () {
    // Document download with approval cover page (only if hsse_status & crm_status are approved)
    Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'download'])->name('documents.download');
});

// Public route for document verification via QR code
Route::get('/documents/verify/{id}', function ($id) {
    $document = \App\Models\Document::withoutGlobalScopes()->findOrFail($id);

    return view('document-verification', [
        'document' => $document,
        'isApproved' => $document->document_type === 'hsse'
            ? $document->hsse_status === 'approved'
            : $document->crm_status === 'approved',
    ]);
})->name('document.verify');

