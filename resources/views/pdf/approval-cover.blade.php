<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Pengesahan</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
        }

        .container {
            width: 100%;
            padding: 32px;
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
            display: inline-block;
            vertical-align: middle;
        }

        .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-top: 8px;
        }

        .info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 16px;
            margin: -16px;
        }

        .box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            background-color: #fff;
        }

        .label {
            font-size: 12px;
            color: #6b7280;
        }

        .value {
            font-size: 14px;
            font-weight: 600;
            margin-top: 4px;
        }

        .signatures-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: separate;
            border-spacing: 24px;
            margin-left: -24px;
            margin-right: -24px;
        }

        .sig-box {
            text-align: center;
            border: 1px dashed #d1d5db;
            padding: 16px;
            border-radius: 8px;
            height: 160px;
            vertical-align: top;
        }

        .sig-role {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 10px;
            display: block;
        }

        .sig-name {
            font-size: 14px;
            font-weight: 700;
            margin-top: 10px;
            display: block;
        }

        .footer {
            margin-top: 48px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }

        .approved-badge {
            display: inline-block;
            padding: 6px 14px;
            border: 3px solid #059669;
            color: #059669;
            font-size: 24px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-radius: 8px;
            background: transparent;
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                @if(!empty($logoDataUri))
                    <img src="{{ $logoDataUri }}"
                        style="height: 48px; vertical-align: middle; margin-right: 12px; display: inline-block;"
                        alt="Logo" />
                @endif
                <div class="title">HALAMAN PENGESAHAN DOKUMEN</div>
            </div>

            @php
                // Logic robustness: check both set and value
                $isHsseDoc = isset($isHsse) && $isHsse;
                $isCrmDoc = isset($isCrm) && $isCrm;

                // Fallback: if neither is set/true, assume generic/both (e.g. older docs)
                if (!$isHsseDoc && !$isCrmDoc) {
                    $isGeneric = true;
                } else {
                    $isGeneric = false;
                }
            @endphp

            <div class="subtitle">
                @if($isHsseDoc)
                    Dokumen telah disetujui oleh HSSE
                @elseif($isCrmDoc)
                    Dokumen telah disetujui oleh CRM
                @else
                    Dokumen telah disetujui oleh HSSE dan S&D
                @endif
            </div>
        </div>

        <!-- Info Grid (Table) -->
        <table class="info-table">
            <tr>
                <td width="50%">
                    <div class="box">
                        <div class="label">Judul Dokumen</div>
                        <div class="value">{{ $title }}</div>
                    </div>
                </td>
                <td width="50%">
                    <div class="box">
                        <div class="label">Nama Mitra</div>
                        <div class="value">{{ $mitraName }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="box">
                        <div class="label">Tanggal Upload</div>
                        <div class="value">{{ optional($uploadedAt)->format('d/m/Y H:i') }}</div>
                    </div>
                </td>
                <td>
                    <div class="box">
                        <div class="label">Tanggal Disetujui</div>
                        <div class="value">{{ optional($approvedAt)->format('d/m/Y H:i') }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Signatures (Table) -->
        <!-- Signatures (Table) -->
        <table class="signatures-table" width="100%">
            <tr>
                @if($isGeneric)
                    <!-- Case: Both/Generic - Two Columns -->
                    <td width="50%" valign="top">
                        <div class="sig-box">
                            <div class="sig-role">Disetujui oleh HSSE</div>
                            <div style="height: 80px; display: table; width: 100%;">
                                <div style="display: table-cell; vertical-align: middle;">
                                    @if(!empty($hsseApproved) && $hsseApproved)
                                        <span class="approved-badge" style="transform: rotate(-12deg);">Approved</span>
                                    @endif
                                </div>
                            </div>
                            <div class="sig-name">{{ $hsseName }}</div>
                        </div>
                    </td>
                    <td width="50%" valign="top">
                        <div class="sig-box">
                            <div class="sig-role">Disetujui oleh CRM</div>
                            <div style="height: 80px; display: table; width: 100%;">
                                <div style="display: table-cell; vertical-align: middle;">
                                    @if(!empty($crmApproved) && $crmApproved)
                                        <span class="approved-badge" style="transform: rotate(-12deg);">Approved</span>
                                    @endif
                                </div>
                            </div>
                            <div class="sig-name">{{ $crmName }}</div>
                        </div>
                    </td>
                @elseif($isHsseDoc)
                    <!-- Case: HSSE Only - Centered -->
                    <!-- To center in table, use one cell with align center and strict width? 
                                      Or 3 cells: 25% empty, 50% box, 25% empty. This is safer for width control. -->
                    <td width="25%"></td>
                    <td width="50%" valign="top">
                        <div class="sig-box">
                            <div class="sig-role">Disetujui oleh HSSE</div>
                            <div style="height: 80px; display: table; width: 100%;">
                                <div style="display: table-cell; vertical-align: middle;">
                                    @if(!empty($hsseApproved) && $hsseApproved)
                                        <span class="approved-badge" style="transform: rotate(-12deg);">Approved</span>
                                    @endif
                                </div>
                            </div>
                            <div class="sig-name">{{ $hsseName }}</div>
                        </div>
                    </td>
                    <td width="25%"></td>
                @elseif($isCrmDoc)
                    <!-- Case: CRM Only - Centered -->
                    <td width="25%"></td>
                    <td width="50%" valign="top">
                        <div class="sig-box">
                            <div class="sig-role">Disetujui oleh CRM</div>
                            <div style="height: 80px; display: table; width: 100%;">
                                <div style="display: table-cell; vertical-align: middle;">
                                    @if(!empty($crmApproved) && $crmApproved)
                                        <span class="approved-badge" style="transform: rotate(-12deg);">Approved</span>
                                    @endif
                                </div>
                            </div>
                            <div class="sig-name">{{ $crmName }}</div>
                        </div>
                    </td>
                    <td width="25%"></td>
                @endif
            </tr>
        </table>


        <div class="footer">
            Dokumen ini dihasilkan otomatis dan sah tanpa tanda tangan basah.
        </div>
    </div>
</body>

</html>