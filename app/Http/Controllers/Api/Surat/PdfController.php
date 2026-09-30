<?php

namespace App\Http\Controllers\Api\Surat;

use App\Http\Controllers\Controller;
use App\Models\LetterRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PdfController extends Controller
{
    /**
     * Helper Static Method untuk merender dan menyimpan file PDF ber-QR Code
     */
    public static function generateLetterPdf(LetterRequest $letterRequest): string
    {
        $letterRequest->load(['user', 'template']);

        // URL validasi tanda tangan elektronik
        $frontendUrl = env('FRONTEND_URL', 'http://e-surat.mengeruda.id');
        $validationUrl = rtrim($frontendUrl, '/') . "/validasi?token=" . $letterRequest->token;

        // Generate QR Code format SVG base64 agar kompatibel dengan DOMPDF
        $qrSvg = QrCode::format('svg')->size(110)->generate($validationUrl);
        $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

        $content = $letterRequest->template->content ?? '<p>Konten surat belum diisi oleh Admin.</p>';

        $settings = \App\Models\Setting::all()->pluck('value', 'key')->toArray();

        // Konversi bulan menjadi Romawi
        $romanMonths = ['01'=>'I', '02'=>'II', '03'=>'III', '04'=>'IV', '05'=>'V', '06'=>'VI', '07'=>'VII', '08'=>'VIII', '09'=>'IX', '10'=>'X', '11'=>'XI', '12'=>'XII'];
        $monthRoman = $romanMonths[$letterRequest->created_at->format('m')];
        
        // Buat nomor urut dari ID request surat (bisa diubah logicnya nanti jika ada tabel khusus nomor)
        $nomorUrut = str_pad($letterRequest->id, 3, '0', STR_PAD_LEFT);
        $tahun = $letterRequest->created_at->format('Y');
        
        // Format dinamis nomor surat, default sesuai permintaan terbaru
        $formatNomorSurat = $settings['format_nomor_surat'] ?? '140/Pem-Mgr/09/[NOMOR_URUT]/[BULAN_ROMAWI]/[TAHUN]';
        
        $nomorSurat = str_replace(
            ['[NOMOR_URUT]', '[BULAN_ROMAWI]', '[TAHUN]'],
            [$nomorUrut, $monthRoman, $tahun],
            $formatNomorSurat
        );

        $tanggalSurat = \Carbon\Carbon::parse($letterRequest->updated_at)->locale('id')->translatedFormat('d F Y');
        
        $requesterData = is_string($letterRequest->requester_data) ? json_decode($letterRequest->requester_data, true) : $letterRequest->requester_data;

        if (!$requesterData && $letterRequest->user) {
            $requesterData = [
                'name' => $letterRequest->user->name,
                'nik' => $letterRequest->user->nik,
                'phone' => $letterRequest->user->phone,
            ];
            
            // Coba ekstrak dari form_data jika ini untuk anggota keluarga (backward compatibility)
            if (is_array($letterRequest->form_data) && isset($letterRequest->form_data['family_member_name'])) {
                $requesterData['name'] = $letterRequest->form_data['family_member_name'];
                $requesterData['nik'] = $letterRequest->form_data['family_member_nik'] ?? null;
            }
        }

        $replacements = [
            '[NAMA]' => strtoupper($requesterData['name'] ?? ''),
            '[NIK]' => $requesterData['nik'] ?? '',
            '[PHONE]' => $requesterData['phone'] ?? '',
            '[NOMOR_SURAT]' => $nomorSurat,
            '[TANGGAL_SURAT]' => $tanggalSurat,
        ];

        foreach ($replacements as $key => $val) {
            $content = str_replace($key, $val ?? '', $content);
        }

        if (is_array($letterRequest->form_data)) {
            foreach ($letterRequest->form_data as $key => $val) {
                $content = str_replace('[FORM:' . $key . ']', $val, $content);
            }
        }

        // Trik khusus: Konversi baris yang menggunakan Tab untuk meluruskan titik dua (:) menjadi HTML Table
        // agar lurus sempurna di PDF meskipun menggunakan font proporsional.
        $content = preg_replace_callback(
            '/((?:<p[^>]*>|<br\s*\/?>|^))((?:(?!<p|<br).)*?)(?:\t)+(?:&nbsp;|\s)*:(?:&nbsp;|\s|\t)*((?:(?!<\/p>|<br).)*?)(?=(?:<\/p>|<br\s*\/?>|$))/i',
            function($matches) {
                $prefix = $matches[1];
                $label = $matches[2];
                $value = $matches[3];
                
                // Hilangkan <br> sebelum table karena table secara otomatis membuat baris baru
                if (preg_match('/<br/i', $prefix)) {
                    $prefix = ''; 
                }
                
                return $prefix . '<table style="width:100%; border:none; margin:0; padding:0; border-collapse:collapse;"><tr><td style="width:180px; vertical-align:top; padding:0;">' . $label . '</td><td style="width:15px; vertical-align:top; padding:0;">:</td><td style="vertical-align:top; padding:0;">' . $value . '</td></tr></table>';
            },
            $content
        );

        // DOMPDF tidak mendukung karakter \t dengan baik, ubah sisa \t menjadi span berukuran tetap
        $content = str_replace("\t", '<span class="ql-tab"></span>', $content);

        // Convert kop_logo URL to base64 for DOMPDF to avoid localhost HTTP timeouts
        if (!empty($settings['kop_logo'])) {
            $logoUrl = $settings['kop_logo'];
            if (str_contains($logoUrl, '/storage/')) {
                $parts = explode('/storage/', $logoUrl);
                $relativePath = end($parts);
                $localPath = storage_path('app/public/' . $relativePath);
                
                if (file_exists($localPath)) {
                    $mime = mime_content_type($localPath);
                    $base64 = base64_encode(file_get_contents($localPath));
                    $settings['kop_logo'] = 'data:' . $mime . ';base64,' . $base64;
                }
            }
        }

        // Render PDF dari pandangan (view) Blade
        $pdf = Pdf::loadView('pdf.surat', [
            'letterRequest' => $letterRequest,
            'qrCodeBase64' => $qrCodeBase64,
            'parsedContent' => $content,
            'nomorSurat' => $nomorSurat,
            'settings' => $settings,
        ])->setPaper('a4', 'portrait');

        $fileName = 'surat_' . $letterRequest->id . '_' . time() . '.pdf';
        $filePath = 'surat/' . $fileName;

        // Simpan ke storage (disk public atau private)
        Storage::disk('local')->put($filePath, $pdf->output());

        return $filePath;
    }

    /**
     * Download Berkas PDF Surat Resmi (Hanya Admin atau Pemohon)
     */
    public function downloadPdf(Request $request, $id)
    {
        $letterRequest = LetterRequest::with(['user', 'template'])->findOrFail($id);
        $user = $request->user();

        // Cek hak akses: hanya admin_surat atau pemohon yang sah
        $isAdmin = $user->isAdminSurat();
        $isOwner = ($letterRequest->user_id === $user->id);

        if (!$isAdmin && !$isOwner) {
            return response()->json(['message' => 'Anda tidak berhak mengakses dokumen ini.'], 403);
        }

        if (!in_array($letterRequest->status, ['approved', 'review'])) {
            return response()->json(['message' => 'Surat belum disetujui atau siap diunduh.'], 404);
        }

        if (!$letterRequest->pdf_path || !Storage::disk('local')->exists($letterRequest->pdf_path)) {
            // Jika file hilang atau belum di-generate, generate ulang
            $pdfPath = self::generateLetterPdf($letterRequest);
            $letterRequest->pdf_path = $pdfPath;
            $letterRequest->save();
        }

        $absolutePath = Storage::disk('local')->path($letterRequest->pdf_path);
        $filename = 'Surat_' . str_replace(' ', '_', $letterRequest->template->name ?? 'Resmi') . '_' . $letterRequest->id . '.pdf';

        if ($request->query('preview') === 'true' || $letterRequest->status === 'review') {
            return response()->file($absolutePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);
        }

        return response()->download($absolutePath, $filename);
    }

    /**
     * Publik endpoint untuk validasi keaslian surat berdasarkan QR Token
     */
    public function validateToken(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $letterRequest = LetterRequest::with(['user', 'template'])
            ->where('token', $request->token)
            ->first();

        if (!$letterRequest || $letterRequest->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'valid' => false,
                'message' => 'Dokumen tidak ditemukan atau belum disetujui secara resmi.'
            ], 404);
        }

        $requesterData = is_string($letterRequest->requester_data) ? json_decode($letterRequest->requester_data, true) : $letterRequest->requester_data;
        
        if (!$requesterData && $letterRequest->user) {
            $requesterData = [
                'name' => $letterRequest->user->name,
                'nik' => $letterRequest->user->nik,
                'phone' => $letterRequest->user->phone,
            ];
            if (is_array($letterRequest->form_data) && isset($letterRequest->form_data['family_member_name'])) {
                $requesterData['name'] = $letterRequest->form_data['family_member_name'];
                $requesterData['nik'] = $letterRequest->form_data['family_member_nik'] ?? null;
            }
        }

        $nik = $requesterData['nik'] ?? '';

        return response()->json([
            'status' => 'success',
            'valid' => true,
            'message' => 'Dokumen sah dan terverifikasi secara resmi oleh Pemerintah Desa Mengeruda.',
            'data' => [
                'nomor_surat' => '140 / ES / MGR / ' . $letterRequest->created_at->format('m') . ' / ' . $letterRequest->created_at->format('Y'),
                'jenis_surat' => $letterRequest->template->name,
                'nama_pemohon' => $requesterData['name'] ?? 'Tidak diketahui',
                'nik_pemohon' => strlen($nik) >= 10 ? substr($nik, 0, 10) . '******' : $nik, // Masqued untuk privasi
                'tanggal_terbit' => $letterRequest->updated_at->format('d F Y H:i:s'),
                'status' => 'TERVERIFIKASI / SAH',
            ]
        ]);
    }
}
