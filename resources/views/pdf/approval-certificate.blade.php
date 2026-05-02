<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Pengesahan - {{ $document->judul_dokumen }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            padding: 40px;
            position: relative;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: bold;
            color: rgba(34, 197, 94, 0.1);
            z-index: -1;
            white-space: nowrap;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #22c55e;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .header h2 {
            font-size: 18px;
            color: #6b7280;
            font-weight: normal;
        }

        .stamp-container {
            text-align: center;
            margin: 30px 0;
        }

        .stamp {
            display: inline-block;
            border: 4px solid #22c55e;
            border-radius: 50%;
            padding: 30px;
            position: relative;
        }

        .stamp-text {
            font-size: 36px;
            font-weight: bold;
            color: #22c55e;
            text-transform: uppercase;
        }

        .document-info {
            margin: 30px 0;
            background: #f9fafb;
            padding: 20px;
            border-left: 4px solid #22c55e;
        }

        .info-row {
            display: flex;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .info-label {
            width: 180px;
            font-weight: bold;
            color: #374151;
        }

        .info-value {
            flex: 1;
            color: #1f2937;
        }

        .approval-section {
            margin: 40px 0;
        }

        .approval-title {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .approver-box {
            background: white;
            border: 2px solid #22c55e;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .approver-role {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .approver-name {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .approver-date {
            font-size: 14px;
            color: #4b5563;
        }

        .approval-stamp-small {
            display: inline-block;
            background: #22c55e;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }

        .qr-section {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e5e7eb;
        }

        .qr-title {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .qr-code img {
            width: 120px;
            height: 120px;
        }

        .footer {
            position: fixed;
            bottom: 30px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .generated-date {
            margin-top: 30px;
            text-align: right;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <!-- Watermark -->
    <div class="watermark">APPROVED</div>

    <!-- Header -->
    <div class="header">
        <h1>LEMBAR PENGESAHAN DOKUMEN</h1>
        <h2>Sistem Informasi Dokumen</h2>
    </div>

    <!-- Stamp -->
    <div class="stamp-container">
        <div class="stamp">
            <div class="stamp-text">APPROVED</div>
        </div>
    </div>

    <!-- Document Information -->
    <div class="document-info">
        <div class="info-row">
            <div class="info-label">Judul Dokumen:</div>
            <div class="info-value">{{ $document->judul_dokumen }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Tipe Dokumen:</div>
            <div class="info-value">{{ strtoupper($document->document_type) }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Mitra:</div>
            <div class="info-value">{{ $document->mitra?->name ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Tanggal Upload:</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($document->tanggal_upload)->format('d F Y') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Tanggal Disetujui:</div>
            <div class="info-value">
                {{ $document->tanggal_acc ? \Carbon\Carbon::parse($document->tanggal_acc)->format('d F Y') : 'N/A' }}
            </div>
        </div>
    </div>

    <!-- Approval Section -->
    <div class="approval-section">
        <div class="approval-title">Disetujui Oleh:</div>

        @if(isset($approvalData['hsse']))
            <div class="approver-box">
                <div class="approver-role">HSSE Reviewer <span class="approval-stamp-small">✓ APPROVED</span></div>
                <div class="approver-name">{{ $approvalData['hsse']['reviewer_name'] }}</div>
                <div class="approver-date">Disetujui pada: {{ $approvalData['hsse']['approved_at'] }}</div>
            </div>
        @endif

        @if(isset($approvalData['crm']))
            <div class="approver-box">
                <div class="approver-role">S&D Reviewer <span class="approval-stamp-small">✓ APPROVED</span></div>
                <div class="approver-name">{{ $approvalData['crm']['reviewer_name'] }}</div>
                <div class="approver-date">Disetujui pada: {{ $approvalData['crm']['approved_at'] }}</div>
            </div>
        @endif
    </div>

    <!-- QR Code for Verification -->
    <div class="qr-section">
        <div class="qr-title">Scan untuk verifikasi keaslian dokumen</div>
        <div class="qr-code">
            <img src="{{ $qrCode }}" alt="QR Code Verification">
        </div>
    </div>

    <!-- Generated Date -->
    <div class="generated-date">
        Dokumen ini digenerate secara otomatis pada: {{ now()->format('d F Y, H:i') }} WIB
    </div>

    <!-- Footer -->
    <div class="footer">
        Lembar pengesahan ini adalah dokumen resmi yang digenerate oleh Sistem Informasi Dokumen.
        <br>
        Untuk verifikasi, scan QR code atau hubungi administrator sistem.
    </div>
</body>

</html>