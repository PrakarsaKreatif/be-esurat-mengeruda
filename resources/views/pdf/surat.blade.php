<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $letterRequest->template->name ?? 'Surat Resmi Desa' }}</title>
    <style>
        @page {
            margin: 2.5cm 2cm 2cm 2cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000000;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #000000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h3 {
            margin: 0;
            font-size: 14pt;
            font-weight: normal;
            text-transform: uppercase;
        }
        .header h1 {
            margin: 0;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0 0 0;
            font-size: 10pt;
            font-style: italic;
        }
        .title {
            text-align: center;
            margin-bottom: 20px;
        }
        .title h2 {
            margin: 0;
            font-size: 14pt;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .title p {
            margin: 5px 0 0 0;
            font-size: 11pt;
        }
        .content {
            margin-bottom: 20px;
            text-align: justify;
        }
        .table-data {
            width: 100%;
            margin: 15px 0 15px 20px;
            border-collapse: collapse;
        }
        .table-data td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .table-data td.label {
            width: 180px;
        }
        .signature-box {
            width: 100%;
            margin-top: 40px;
        }
        .signature-right {
            float: right;
            width: 250px;
            text-align: center;
        }
        .qr-code {
            margin: 10px auto;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($settings['kop_logo']) && $settings['kop_logo'] != '')
            <!-- Note: using absolute URL or base64 is recommended for DOMPDF logos -->
            <img src="{{ $settings['kop_logo'] }}" alt="Logo" style="width: 80px; position: absolute; top: 10px; left: 10px;" />
        @endif
        <h3>{!! $settings['kop_pemda'] ?? 'PEMERINTAH DAERAH' !!}</h3>
        <h1>{{ $settings['kop_desa'] ?? 'DESA' }}</h1>
        <p>{{ $settings['kop_alamat'] ?? '' }}</p>
    </div>

    <div class="title">
        <h2>{{ $letterRequest->template->name }}</h2>
        <p>Nomor: {{ $nomorSurat }}</p>
    </div>

    <div class="content">
        {!! $parsedContent !!}
    </div>

    <div class="signature-box">
        <div class="signature-right">
            <p>Mengeruda, {{ \Carbon\Carbon::parse($letterRequest->updated_at)->locale('id')->translatedFormat('d F Y') }}<br />An. Kepala Desa Mengeruda</p>
            
            <div class="qr-code">
                @if(isset($qrCodeSvg))
                    {!! $qrCodeSvg !!}
                @elseif(isset($qrCodeBase64))
                    <img src="{{ $qrCodeBase64 }}" alt="QR Code Signature" style="width: 100px; height: 100px;" />
                @endif
            </div>

            <p style="font-size: 9pt; color: #555;">Dokumen ini ditandatangani secara elektronik. Verifikasi keaslian melalui pemindaian QR Code di atas.</p>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>
