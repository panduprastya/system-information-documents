<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen - {{ $document->judul_dokumen }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .status-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .status-icon.approved {
            background: #22c55e;
            color: white;
        }

        .status-icon.not-approved {
            background: #ef4444;
            color: white;
        }

        h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .status-text {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .status-text.approved {
            color: #22c55e;
        }

        .status-text.not-approved {
            color: #ef4444;
        }

        .document-info {
            background: #f9fafb;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }

        .info-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #6b7280;
            width: 150px;
            flex-shrink: 0;
        }

        .info-value {
            color: #1f2937;
            flex: 1;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge.approved {
            background: #dcfce7;
            color: #166534;
        }

        .badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.reviewing {
            background: #dbeafe;
            color: #1e40af;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }

        .verified-stamp {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #22c55e;
            color: white;
            border-radius: 8px;
            font-weight: bold;
            transform: rotate(-5deg);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="status-icon {{ $isApproved ? 'approved' : 'not-approved' }}">
                {{ $isApproved ? '✓' : '✗' }}
            </div>
            <h1>Verifikasi Dokumen</h1>
            <p class="status-text {{ $isApproved ? 'approved' : 'not-approved' }}">
                {{ $isApproved ? 'Dokumen Terverifikasi & Disetujui' : 'Dokumen Belum Disetujui' }}
            </p>
            @if($isApproved)
                <div class="verified-stamp">VERIFIED ✓</div>
            @endif
        </div>

        <div class="document-info">
            <div class="info-row">
                <div class="info-label">Judul Dokumen:</div>
                <div class="info-value">{{ $document->judul_dokumen }}</div>
            </div>

            <div class="info-row">
                <div class="info-label">Tipe Dokumen:</div>
                <div class="info-value">
                    <span class="badge">{{ strtoupper($document->document_type) }}</span>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">Mitra:</div>
                <div class="info-value">{{ $document->mitra?->name ?? 'N/A' }}</div>
            </div>

            @if($document->document_type === 'hsse')
                <div class="info-row">
                    <div class="info-label">Status HSSE:</div>
                    <div class="info-value">
                        <span class="badge {{ $document->hsse_status }}">{{ $document->hsse_status }}</span>
                    </div>
                </div>
            @endif

            @if($document->document_type === 'crm')
                <div class="info-row">
                    <div class="info-label">Status S&D:</div>
                    <div class="info-value">
                        <span class="badge {{ $document->crm_status }}">{{ $document->crm_status }}</span>
                    </div>
                </div>
            @endif

            <div class="info-row">
                <div class="info-label">Tanggal Upload:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($document->tanggal_upload)->format('d F Y') }}</div>
            </div>

            @if($document->tanggal_acc && $isApproved)
                <div class="info-row">
                    <div class="info-label">Tanggal Disetujui:</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($document->tanggal_acc)->format('d F Y, H:i') }} WIB
                    </div>
                </div>
            @endif

            @if($isApproved)
                @if($document->document_type === 'hsse' && $document->hsse)
                    <div class="info-row">
                        <div class="info-label">Disetujui oleh:</div>
                        <div class="info-value">{{ $document->hsse->name }} (HSSE)</div>
                    </div>
                @endif

                @if($document->document_type === 'crm' && $document->crm)
                    <div class="info-row">
                        <div class="info-label">Disetujui oleh:</div>
                        <div class="info-value">{{ $document->crm->name }} (S&D)</div>
                    </div>
                @endif
            @endif
        </div>

        <div class="footer">
            <p>Halaman verifikasi ini diakses melalui QR Code pada lembar pengesahan.</p>
            <p style="margin-top: 5px; font-size: 12px;">© {{ date('Y') }} Sistem Informasi Dokumen</p>
        </div>
    </div>
</body>

</html>