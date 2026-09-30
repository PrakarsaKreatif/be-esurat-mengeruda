<!DOCTYPE html>
<html>
<head>
    <title>Permintaan Revisi Surat</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
        <h2 style="color: #b45309;">Permintaan Revisi Surat</h2>
        <p>Halo Admin,</p>
        <p>Warga atas nama <strong>{{ $letterRequest->user->name }}</strong> telah meminta revisi untuk surat <strong>{{ $letterRequest->template->name }}</strong> yang baru saja diterbitkan.</p>
        
        <div style="background-color: #fffbeb; padding: 15px; border-radius: 6px; border: 1px solid #fde68a; margin: 20px 0;">
            <p style="margin: 0 0 10px 0; color: #92400e;"><strong>Catatan Revisi dari Warga:</strong></p>
            <p style="margin: 0; color: #92400e;"><em>"{{ $letterRequest->rejection_reason }}"</em></p>
        </div>

        <p>Silakan masuk ke Dashboard Admin E-Surat untuk memperbaiki data surat tersebut dan menerbitkannya kembali.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ env('FRONTEND_URL', 'http://localhost:5173') }}/admin/surat" style="background-color: #1e3a8a; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Periksa Permintaan Revisi</a>
        </div>

        <p style="margin-top: 30px; font-size: 14px; color: #64748b;">
            Terima kasih,<br>
            Sistem E-Surat Mengeruda
        </p>
    </div>
</body>
</html>
