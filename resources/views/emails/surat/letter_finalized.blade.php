<!DOCTYPE html>
<html>
<head>
    <title>Surat Diterima & Final</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #059669;">Surat Telah Diterima oleh Warga</h2>
        <p>Halo Admin,</p>
        <p>Warga atas nama <strong>{{ $letterRequest->user->name }}</strong> telah meninjau dan Menerima (Finalisasi) surat <strong>{{ $letterRequest->template->name }}</strong>.</p>
        
        <div style="background-color: #f8fafc; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>Nama Pemohon:</strong> {{ $letterRequest->user->name }}</p>
            <p style="margin: 0 0 10px 0;"><strong>Jenis Surat:</strong> {{ $letterRequest->template->name }}</p>
            <p style="margin: 0;"><strong>Status Terkini:</strong> Disetujui (Selesai)</p>
        </div>

        <p>Tidak ada tindakan lebih lanjut yang diperlukan untuk surat ini. Arsip PDF surat ini dapat Anda temukan di Dashboard Admin E-Surat.</p>

        <p style="margin-top: 30px; font-size: 14px; color: #64748b;">
            Terima kasih,<br>
            Sistem E-Surat Mengeruda
        </p>
    </div>
</body>
</html>
