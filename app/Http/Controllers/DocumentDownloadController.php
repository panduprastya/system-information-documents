<?php

namespace App\Http\Controllers;

use App\Models\document as Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use iio\libmergepdf\Merger;

class DocumentDownloadController extends Controller
{
    public function download(Document $document)
    {
        if (!Auth::check()) {
            abort(401);
        }

        // Only Mitra can download
        if (!Auth::user()->hasRole('Mitra')) {
            abort(403, 'Hanya Mitra yang dapat mengunduh dokumen.');
        }

        if (!($document->hsse_status === 'approved' && $document->snd_status === 'approved')) {
            abort(403, 'Dokumen belum disetujui oleh HSSE dan S&D.');
        }

        $fileField = (string) $document->file;
        $relativePath = ltrim($fileField, '/');
        $storagePublicPath = storage_path('app/public/' . $relativePath);

        if (!file_exists($storagePublicPath)) {
            $storagePublicPath = storage_path('app/public/' . basename($relativePath));
        }

        if (!file_exists($storagePublicPath)) {
            abort(404, 'File dokumen asli tidak ditemukan.');
        }

        $approvalDate = $document->tanggal_acc ?? $document->updated_at;
        $hsseName = optional($document->hsse)->name ?? '-';
        $sndName = optional($document->snd)->name ?? '-';
        $mitraName = optional($document->mitra)->name ?? '-';
        $logoDataUri = $this->resolveLogoDataUri();
        


        $coverHtml = view('pdf.approval-cover', [
            'title' => $document->judul_dokumen,
            'mitraName' => $mitraName,
            'hsseName' => $hsseName,
            'sndName' => $sndName,
            'uploadedAt' => $document->tanggal_upload ?? $document->created_at,
            'approvedAt' => $approvalDate,
            'logoDataUri' => $logoDataUri,
            'hsseApproved' => $document->hsse_status === 'approved',
            'sndApproved' => $document->snd_status === 'approved',

        ])->render();

        $coverPdf = Pdf::loadHTML($coverHtml)->setPaper('a4');
        $coverBinary = $coverPdf->output();

        $merger = new Merger();
        $merger->addRaw($coverBinary);
        $merger->addFile($storagePublicPath);
        $combined = $merger->merge();

        $downloadName = Str::slug(($document->judul_dokumen ?: 'dokumen') . '-approved') . '.pdf';

        return response($combined, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
        ]);
    }

    private function resolveLogoDataUri(): ?string
    {
        $candidates = [
            public_path('images/PT_Pertamina_Patra_Niaga.png'),
            public_path('images/PT_Pertamina_Patra_Niaga.jpg'),
            public_path('images/PT_Pertamina_Patra_Niaga.jpeg'),
            public_path('images/PT_Pertamina_Patra_Niaga.svg'),
            public_path('images/PT_Pertamina_Patra_Niaga.png'),
            public_path('images/PT_Pertamina_Patra_Niaga.jpg'),
            public_path('images/PT_Pertamina_Patra_Niaga.jpeg'),
            public_path('images/PT_Pertamina_Patra_Niaga.svg'),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    default => null,
                };
                if (!$mime) {
                    continue;
                }
                $data = @file_get_contents($path);
                if ($data === false) {
                    continue;
                }
                $base64 = base64_encode($data);
                return 'data:' . $mime . ';base64,' . $base64;
            }
        }

        return null;
    }
}


