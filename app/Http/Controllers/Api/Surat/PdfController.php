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
        $validationUrl = "http://e-surat.mengeruda.id/validasi?token=" . $letterRequest->token;

        // Generate QR Code format SVG base64 agar kompatibel dengan DOMPDF
        $qrSvg = QrCode::format('svg')->size(110)->generate($validationUrl);
        $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

        $content = $letterRequest->template->content ?? '<p>Konten surat belum diisi oleh Admin.</p>';

        $nomorSurat = '140 / ES / MGR / ' . $letterRequest->created_at->format('m') . ' / ' . $letterRequest->created_at->format('Y');
        $tanggalSurat = \Carbon\Carbon::parse($letterRequest->updated_at)->locale('id')->translatedFormat('d F Y');
        
        $requesterData = is_string($letterRequest->requester_data) ? json_decode($letterRequest->requester_data, true) : $letterRequest->requester_data;

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

        $settings = \App\Models\Setting::all()->pluck('value', 'key')->toArray();

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

        if ($letterRequest->status !== 'approved') {
            return response()->json(['message' => 'Surat belum disetujui.'], 404);
        }

        if (!$letterRequest->pdf_path || !Storage::disk('local')->exists($letterRequest->pdf_path)) {
            // Jika file hilang atau belum di-generate, generate ulang
            $pdfPath = self::generateLetterPdf($letterRequest);
            $letterRequest->pdf_path = $pdfPath;
            $letterRequest->save();
        }

        return Storage::disk('local')->download(
            $letterRequest->pdf_path, 
            'Surat_' . str_replace(' ', '_', $letterRequest->template->name ?? 'Resmi') . '_' . $letterRequest->id . '.pdf'
        );
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
