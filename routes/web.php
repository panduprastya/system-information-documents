<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\SndCommentController;
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
    // SND Comments Routes
    Route::post('/documents/{document}/snd-comments', [SndCommentController::class, 'store'])->name('snd-comments.store');
    Route::get('/documents/{document}/snd-comments', [SndCommentController::class, 'index'])->name('snd-comments.index');
    Route::put('/snd-comments/{comment}', [SndCommentController::class, 'update'])->name('snd-comments.update');
    Route::put('/snd-comments/{comment}/resolve', [SndCommentController::class, 'resolve'])->name('snd-comments.resolve');
    Route::delete('/snd-comments/{comment}', [SndCommentController::class, 'destroy'])->name('snd-comments.destroy');

    // HSSE Comments Routes
    Route::post('/documents/{document}/hsse-comments', [HsseCommentController::class, 'store'])->name('hsse-comments.store');
    Route::get('/documents/{document}/hsse-comments', [HsseCommentController::class, 'index'])->name('hsse-comments.index');
    Route::put('/hsse-comments/{comment}', [HsseCommentController::class, 'update'])->name('hsse-comments.update');
    Route::put('/hsse-comments/{comment}/resolve', [HsseCommentController::class, 'resolve'])->name('hsse-comments.resolve');
    Route::delete('/hsse-comments/{comment}', [HsseCommentController::class, 'destroy'])->name('hsse-comments.destroy');

    // Document download with approval cover page (only if hsse_status & snd_status are approved)
    Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'download'])->name('documents.download');


    // Document Approval Route
    Route::get('/documents/{document}/approve', [\App\Http\Controllers\DocumentApprovalController::class, 'approve'])->name('documents.approve');
    Route::post('/documents/{document}/reject', [\App\Http\Controllers\DocumentApprovalController::class, 'reject'])->name('documents.reject');
});
