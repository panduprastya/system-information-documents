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

        $type = strtolower(trim($document->tipe_dokumen));
        $isHsse = $type === 'hsse';
        $isCrm = $type === 'crm';

        if ($isHsse && $document->hsse_status !== 'approved') {
            abort(403, 'Dokumen belum disetujui oleh HSSE.');
        }

        if ($isCrm && $document->crm_status !== 'approved') {
            abort(403, 'Dokumen belum disetujui oleh CRM.');
        }

        if (!$isHsse && !$isCrm) {
            // Fallback for older documents or undefined types
            if (!($document->hsse_status === 'approved' && $document->crm_status === 'approved')) {
                abort(403, 'Dokumen belum disetujui.');
            }
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
        $uploadedDate = $document->tanggal_upload ?? $document->created_at;

        // Ensure dates are Carbon instances
        $approvalDate = $approvalDate ? \Illuminate\Support\Carbon::parse($approvalDate) : now();
        $uploadedDate = $uploadedDate ? \Illuminate\Support\Carbon::parse($uploadedDate) : now();

        $hsseName = optional($document->hsse)->name ?? '-';
        $crmName = optional($document->crm)->name ?? '-';
        $mitraName = optional($document->mitra)->name ?? '-';
        $logoDataUri = $this->resolveLogoDataUri();



        $coverHtml = view('pdf.approval-cover', [
            'title' => $document->judul_dokumen,
            'mitraName' => $mitraName,
            'hsseName' => $hsseName,
            'crmName' => $crmName,
            'uploadedAt' => $uploadedDate,
            'approvedAt' => $approvalDate,
            'logoDataUri' => $logoDataUri,
            'hsseApproved' => $document->hsse_status === 'approved',
            'crmApproved' => $document->crm_status === 'approved',
            'isHsse' => $isHsse,
            'isCrm' => $isCrm,

        ])->render();

        $coverPdf = Pdf::loadHTML($coverHtml)->setPaper('a4');
        $coverBinary = $coverPdf->output();

        // Use temporary file for cover to ensure merger works correctly
        $tempCoverPath = tempnam(sys_get_temp_dir(), 'cover_') . '.pdf';
        file_put_contents($tempCoverPath, $coverBinary);

        try {
            $merger = new Merger();
            $merger->addFile($tempCoverPath);

            // Ensure original file is valid for merging (simple check)
            $merger->addFile($storagePublicPath);

            $combined = $merger->merge();

            // Clean up temp file
            if (file_exists($tempCoverPath)) {
                unlink($tempCoverPath);
            }

            $downloadName = Str::slug(($document->judul_dokumen ?: 'dokumen') . '-approved') . '.pdf';

            return response($combined, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
            ]);

        } catch (\Throwable $e) {
            // Attempt 2: Try using Ghostscript if available (Robust for PDF 1.5+ compat)
            $gsMergedPath = tempnam(sys_get_temp_dir(), 'gs_merged_') . '.pdf';

            // Check if gs is available
            $gsAvailable = false;
            try {
                // Windows might need full path or 'gswin64c'
                // This is a basic check.
                // Assuming 'gswin64c' is in PATH for Windows or 'gs' for Linux
                // For this environment (Windows), let's try 'gswin64c' first then 'gs'
                $gsCommand = 'gswin64c';
                exec($gsCommand . ' --version', $output, $returnVar);
                if ($returnVar !== 0) {
                    $gsCommand = 'gs';
                    exec($gsCommand . ' --version', $output, $returnVar);
                }

                if ($returnVar === 0) {
                    $gsAvailable = true;
                }
            } catch (\Exception $ex) {
                $gsAvailable = false;
            }

            if ($gsAvailable) {
                // Construct command
                // quote paths for safety
                $cmd = sprintf(
                    '%s -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile="%s" "%s" "%s"',
                    $gsCommand,
                    $gsMergedPath,
                    $tempCoverPath,
                    $storagePublicPath
                );

                exec($cmd, $output, $returnVar);

                if ($returnVar === 0 && file_exists($gsMergedPath) && filesize($gsMergedPath) > 0) {
                    // Success!
                    $downloadName = Str::slug(($document->judul_dokumen ?: 'dokumen') . '-approved') . '.pdf';

                    // Clean up temp cover
                    if (file_exists($tempCoverPath)) {
                        unlink($tempCoverPath);
                    }

                    return response()->download($gsMergedPath, $downloadName)->deleteFileAfterSend(true);
                }
            }

            // Fallback 3: If Ghostscript fails or is unavailable, return ZIP

            $zipPath = tempnam(sys_get_temp_dir(), 'doc_bundle_') . '.zip';
            $zip = new \ZipArchive();

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                // Add Cover
                $zip->addFile($tempCoverPath, 'Lembar_Pengesahan.pdf');

                // Add Original Document
                // Make name nicer
                $niceOriginalName = Str::slug($document->judul_dokumen ?: 'Dokumen') . '.pdf';
                $zip->addFile($storagePublicPath, $niceOriginalName);

                $zip->close();
            }

            // Clean up temp cover
            if (file_exists($tempCoverPath)) {
                unlink($tempCoverPath);
            }

            // Clean up failed GS file if exists
            if (isset($gsMergedPath) && file_exists($gsMergedPath)) {
                unlink($gsMergedPath);
            }

            $downloadName = Str::slug(($document->judul_dokumen ?: 'dokumen') . '-approved') . '.zip';

            return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
        }
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



