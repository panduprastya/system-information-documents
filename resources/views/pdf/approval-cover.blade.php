<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Pengesahan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; }
        .container { width: 100%; padding: 32px; }
        .header { text-align: center; margin-bottom: 32px; }
        .title { font-size: 24px; font-weight: bold; margin-bottom: 8px; }
        .subtitle { font-size: 14px; color: #6b7280; }
        .brand { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 12px; }
        .brand img { height: 48px; }
        .section { margin-top: 24px; }
        .label { font-size: 12px; color: #6b7280; }
        .value { font-size: 14px; font-weight: 600; margin-top: 4px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
        .signatures { margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .sig-box { text-align: center; border: 1px dashed #d1d5db; padding: 16px; border-radius: 8px; height: 160px; display: flex; flex-direction: column; justify-content: space-between; position: relative; }
        .sig-role { font-size: 12px; color: #6b7280; }
        .signature-image { margin: 8px 0; }
        .sig-name { font-size: 14px; font-weight: 700; }
        .sig-date { font-size: 10px; color: #6b7280; margin-top: 4px; }
        .footer { margin-top: 48px; text-align: center; font-size: 12px; color: #6b7280; }
        .approved-badge { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-12deg); display: inline-block; padding: 6px 14px; border: 3px solid #059669; color: #059669; font-size: 30px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; border-radius: 8px; background: transparent; opacity: 0.9; }
    </style>
    <script>
        // empty script tag prevents DomPDF from stripping head; keep minimal
    </script>
    </head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand">
                @if(!empty($logoDataUri))
                    <img src="{{ $logoDataUri }}" alt="Logo Pertamina Patra Niaga" />
                @endif
                <div class="title">HALAMAN PENGESAHAN DOKUMEN</div>
            </div>
            <div class="subtitle">Dokumen telah disetujui oleh HSSE dan S&amp;D</div>
        </div>

        <div class="grid">
            <div class="box">
                <div class="label">Judul Dokumen</div>
                <div class="value">{{ $title }}</div>
            </div>
            <div class="box">
                <div class="label">Nama Mitra</div>
                <div class="value">{{ $mitraName }}</div>
            </div>
            <div class="box">
                <div class="label">Tanggal Upload</div>
                <div class="value">{{ optional($uploadedAt)->format('d/m/Y H:i') }}</div>
            </div>
            <div class="box">
                <div class="label">Tanggal Disetujui</div>
                <div class="value">{{ optional($approvedAt)->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <div class="signatures">
            <div class="sig-box">
                <div class="sig-role">Disetujui oleh HSSE</div>
                <div style="flex:1; display:flex; align-items:center; justify-content:center;">
                    @if(!empty($hsseApproved) && $hsseApproved)
                        <span class="approved-badge">Approved</span>
                    @endif
                </div>
                <div class="sig-name">{{ $hsseName }}</div>
            </div>
            <div class="sig-box">
                <div class="sig-role">Disetujui oleh S&amp;D</div>
                <div style="flex:1; display:flex; align-items:center; justify-content:center;">
                    @if(!empty($sndApproved) && $sndApproved)
                        <span class="approved-badge">Approved</span>
                    @endif
                </div>
                <div class="sig-name">{{ $sndName }}</div>
            </div>
        </div>

        <div class="footer">
            Dokumen ini dihasilkan otomatis dan sah tanpa tanda tangan basah.
        </div>
    </div>
</body>
</html>


