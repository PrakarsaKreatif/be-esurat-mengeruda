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
        .header h3, .header h2, .header h1, .header p {
            margin: 0;
            padding: 0;
        }

        h1 { font-size: 16pt; margin: 5px 0; }
        h2 { font-size: 14pt; margin: 5px 0; }
        h3 { font-size: 12pt; margin: 5px 0; }
        
        /* Quill Alignment Classes */
        .ql-align-center { text-align: center; }
        .ql-align-right { text-align: right; }
        .ql-align-justify { text-align: justify; }
        
        /* Quill Size Classes */
        .ql-size-small { font-size: 10pt; }
        .ql-size-large { font-size: 14pt; }
        .ql-size-huge { font-size: 18pt; }
        
        /* Quill Indent Classes */
        .ql-indent-1 { padding-left: 3em; }
        .ql-indent-2 { padding-left: 6em; }
        .ql-indent-3 { padding-left: 9em; }
        .ql-indent-4 { padding-left: 12em; }
        .ql-indent-5 { padding-left: 15em; }
        .ql-indent-6 { padding-left: 18em; }
        .ql-indent-7 { padding-left: 21em; }
        .ql-indent-8 { padding-left: 24em; }
        .ql-tab {
            display: inline-block;
            width: 30px;
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
        .content p {
            white-space: pre-wrap;
            -moz-tab-size: 4;
            tab-size: 4;
            margin: 0;
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
            width: 300px;
            text-align: center;
        }
        .qr-code {
            margin: 5px auto;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($settings['kop_logo']) && $settings['kop_logo'] != '')
            <!-- Note: using absolute URL or base64 is recommended for DOMPDF logos -->
            <img src="{{ $settings['kop_logo'] }}" alt="Logo" style="width: 80px; position: absolute; top: 10px; left: 10px;" />
        @endif
        <div style="margin-bottom: 5px;">
            {!! $settings['kop_pemda'] ?? '<h3>PEMERINTAH DAERAH</h3>' !!}
        </div>
        <div style="margin-bottom: 5px;">
            {!! $settings['kop_desa'] ?? '<h1>DESA</h1>' !!}
        </div>
        <div>
            {!! $settings['kop_alamat'] ?? '' !!}
        </div>
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
            <p>Mengeruda, {{ \Carbon\Carbon::parse($letterRequest->updated_at)->locale('id')->translatedFormat('d F Y') }}<br />Kepala Desa Mengeruda</p>
            
            <div class="qr-code">
                @if(isset($qrCodeSvg))
                    {!! $qrCodeSvg !!}
                @elseif(isset($qrCodeBase64))
                    <img src="{{ $qrCodeBase64 }}" alt="QR Code Signature" style="width: 100px; height: 100px;" />
                @endif
            </div>

            <div style="margin-bottom: 2px;">
                {!! $settings['kepala_desa_name'] ?? '<p style="font-weight: bold; text-decoration: underline;">KEPALA DESA MENGERUDA</p>' !!}
            </div>
            <p style="font-size: 9pt; color: #555; margin-top: 2px;">Dokumen ini ditandatangani secara elektronik.</br>Verifikasi keaslian melalui pemindaian QR Code di atas.</p>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>
</html>
