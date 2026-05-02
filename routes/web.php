<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\CrmCommentController;
use App\Http\Controllers\HsseCommentController;
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
    // CRM Comments Routes
    Route::post('/documents/{document}/crm-comments', [CrmCommentController::class, 'store'])->name('crm-comments.store');
    Route::get('/documents/{document}/crm-comments', [CrmCommentController::class, 'index'])->name('crm-comments.index');
    Route::put('/crm-comments/{comment}', [CrmCommentController::class, 'update'])->name('crm-comments.update');
    Route::put('/crm-comments/{comment}/resolve', [CrmCommentController::class, 'resolve'])->name('crm-comments.resolve');
    Route::delete('/crm-comments/{comment}', [CrmCommentController::class, 'destroy'])->name('crm-comments.destroy');

    // HSSE Comments Routes
    Route::post('/documents/{document}/hsse-comments', [HsseCommentController::class, 'store'])->name('hsse-comments.store');
    Route::get('/documents/{document}/hsse-comments', [HsseCommentController::class, 'index'])->name('hsse-comments.index');
    Route::put('/hsse-comments/{comment}', [HsseCommentController::class, 'update'])->name('hsse-comments.update');
    Route::put('/hsse-comments/{comment}/resolve', [HsseCommentController::class, 'resolve'])->name('hsse-comments.resolve');
    Route::delete('/hsse-comments/{comment}', [HsseCommentController::class, 'destroy'])->name('hsse-comments.destroy');

    // Document download with approval cover page (only if hsse_status & crm_status are approved)
    Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'download'])->name('documents.download');


    // Document Approval Route
    Route::get('/documents/{document}/approve', [\App\Http\Controllers\DocumentApprovalController::class, 'approve'])->name('documents.approve');

    // Document Rejection Routes
    Route::get('/documents/{document}/reject', [\App\Http\Controllers\DocumentApprovalController::class, 'showRejectForm'])->name('documents.reject.form');
    Route::post('/documents/{document}/reject', [\App\Http\Controllers\DocumentApprovalController::class, 'reject'])->name('documents.reject');
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

