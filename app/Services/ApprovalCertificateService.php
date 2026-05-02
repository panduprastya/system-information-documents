<?php

namespace App\Services;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ApprovalCertificateService
{
    /**
     * Generate approval certificate PDF for a document
     *
     * @param Document $document
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateCertificate(Document $document)
    {
        // Check if document is fully approved
        if (!$this->isFullyApproved($document)) {
            throw new \Exception('Document is not fully approved yet.');
        }

        // Get approval data
        $approvalData = $this->getApprovalData($document);

        // Generate QR Code for verification
        $qrCode = $this->generateQrCode($document);

        // Load view and generate PDF
        $pdf = Pdf::loadView('pdf.approval-certificate', [
            'document' => $document,
            'approvalData' => $approvalData,
            'qrCode' => $qrCode,
        ]);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    /**
     * Check if document is fully approved
     *
     * @param Document $document
     * @return bool
     */
    private function isFullyApproved(Document $document): bool
    {
        if ($document->isForHsse()) {
            return $document->hsse_status === 'approved';
        }

        if ($document->isForCrm()) {
            return $document->crm_status === 'approved';
        }

        return false;
    }

    /**
     * Get approval data (reviewers and dates)
     *
     * @param Document $document
     * @return array
     */
    private function getApprovalData(Document $document): array
    {
        $data = [];

        if ($document->isForHsse() && $document->hsse_status === 'approved') {
            $data['hsse'] = [
                'reviewer_name' => $document->hsse?->name ?? 'N/A',
                'approved_at' => $this->getApprovalDate($document, 'hsse'),
            ];
        }

        if ($document->isForCrm() && $document->crm_status === 'approved') {
            $data['crm'] = [
                'reviewer_name' => $document->crm?->name ?? 'N/A',
                'approved_at' => $this->getApprovalDate($document, 'crm'),
            ];
        }

        return $data;
    }

    /**
     * Get approval date from comments
     *
     * @param Document $document
     * @param string $type
     * @return string|null
     */
    private function getApprovalDate(Document $document, string $type): ?string
    {
        if ($type === 'hsse') {
            $comment = $document->hsseComments()
                ->where('status_after', 'approved')
                ->latest()
                ->first();
        } else {
            $comment = $document->crmComments()
                ->where('status_after', 'approved')
                ->latest()
                ->first();
        }

        return $comment?->created_at?->format('d F Y, H:i') ?? $document->tanggal_acc;
    }

    /**
     * Generate QR Code for document verification
     *
     * @param Document $document
     * @return string
     */
    private function generateQrCode(Document $document): string
    {
        $verificationUrl = route('document.verify', ['id' => $document->id]);

        // Generate QR code as base64 image
        $qrCode = QrCode::format('png')
            ->size(150)
            ->generate($verificationUrl);

        return 'data:image/png;base64,' . base64_encode($qrCode);
    }
}
