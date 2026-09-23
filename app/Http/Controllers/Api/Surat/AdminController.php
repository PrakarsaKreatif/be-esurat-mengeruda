<?php

namespace App\Http\Controllers\Api\Surat;

use App\Http\Controllers\Controller;
use App\Mail\Surat\AccountApprovedMail;
use App\Mail\Surat\LetterReadyMail;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Create Template
     */
    public function createTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'required_fields' => 'required|array'
        ]);

        $template = LetterTemplate::create($validated);
        return response()->json(['status' => 'success', 'data' => $template], 201);
    }

    /**
     * Update Template
     */
    public function updateTemplate(Request $request, $id)
    {
        $template = LetterTemplate::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'required_fields' => 'sometimes|required|array'
        ]);

        $template->update($validated);
        return response()->json(['status' => 'success', 'data' => $template]);
    }

    /**
     * Delete Template
     */
    public function deleteTemplate($id)
    {
        $template = LetterTemplate::findOrFail($id);
        $template->delete();
        return response()->json(['status' => 'success', 'message' => 'Template deleted']);
    }
    /**
     * Daftar warga yang menunggu verifikasi (is_approved = false) dari SSO
     */
    public function getPendingUsers(Request $request)
    {
        $ssoApiUrl = env('SSO_API_URL', 'http://127.0.0.1:8002/api');
        
        try {
            $response = Http::withToken($request->bearerToken())
                            ->get($ssoApiUrl . '/admin/users/pending');
            
            \Illuminate\Support\Facades\Log::info('SSO getPendingUsers Response: ' . $response->body());

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch pending users from SSO',
                'sso_status' => $response->status(),
                'sso_response' => $response->body()
            ], $response->status());

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SSO getPendingUsers Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error communicating with SSO: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Daftar semua warga
     */
    public function getAllUsers(Request $request)
    {
        $ssoApiUrl = env('SSO_API_URL', 'http://127.0.0.1:8002/api');
        
        try {
            $response = Http::withToken($request->bearerToken())
                            ->get($ssoApiUrl . '/admin/users/all');
            
            \Illuminate\Support\Facades\Log::info('SSO getAllUsers Response: ' . $response->body());

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch all users from SSO'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error communicating with SSO: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Setujui akun warga melalui SSO
     */
    public function approveUser(Request $request, $id)
    {
        $ssoApiUrl = env('SSO_API_URL', 'http://127.0.0.1:8002/api');
        
        try {
            $response = Http::withToken($request->bearerToken())
                            ->post($ssoApiUrl . '/admin/users/' . $id . '/approve');
            
            if ($response->successful()) {
                // Berhasil di SSO, coba update di lokal jika user sudah ada (pernah login/disync)
                $user = User::find($id);
                if ($user) {
                    $user->is_approved = true;
                    $user->save();

                    // Kirim email notifikasi ke warga
                    try {
                        Mail::to($user->email)->send(new AccountApprovedMail($user));
                    } catch (\Exception $e) {
                        report($e);
                    }
                }
                
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve user in SSO'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error communicating with SSO: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approveKk(Request $request, $id)
    {
        $ssoApiUrl = env('SSO_API_URL', 'http://127.0.0.1:8002/api');
        
        try {
            $response = Http::withToken($request->bearerToken())
                            ->post($ssoApiUrl . '/admin/users/' . $id . '/approve-kk');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve KK in SSO'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error communicating with SSO: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rejectKk(Request $request, $id)
    {
        $ssoApiUrl = env('SSO_API_URL', 'http://127.0.0.1:8002/api');
        
        try {
            $response = Http::withToken($request->bearerToken())
                            ->post($ssoApiUrl . '/admin/users/' . $id . '/reject-kk');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject KK in SSO'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error communicating with SSO: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Streaming aman berkas KTP warga dari direktori privat
     */
    public function viewUserKtp($id)
    {
        $user = User::findOrFail($id);
        if (!$user->ktp_path || !Storage::disk('private')->exists($user->ktp_path)) {
            return response()->json(['message' => 'Dokumen KTP tidak ditemukan.'], 404);
        }

        return Storage::disk('private')->response($user->ktp_path);
    }

    /**
     * Daftar permohonan surat (opsional filter status: pending, approved, rejected)
     */
    public function getLetterRequests(Request $request)
    {
        $query = LetterRequest::with(['user', 'template'])->orderBy('created_at', 'desc');
        
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get()
        ]);
    }

    /**
     * Setujui permohonan surat & generate PDF ber-QR Code
     */
    public function approveLetterRequest($id)
    {
        $letterRequest = LetterRequest::with(['user', 'template'])->findOrFail($id);

        if ($letterRequest->status === 'approved' && $letterRequest->pdf_path) {
            return response()->json([
                'status' => 'success',
                'message' => 'Surat sudah disetujui sebelumnya.',
                'data' => $letterRequest
            ]);
        }

        // Generate unique token untuk validasi QR
        if (!$letterRequest->token) {
            $letterRequest->token = Str::uuid()->toString();
        }

        // Generate PDF sisi server via PdfController Helper
        $pdfPath = PdfController::generateLetterPdf($letterRequest);

        $letterRequest->status = 'approved';
        $letterRequest->pdf_path = $pdfPath;
        $letterRequest->save();

        // Kirim email notifikasi ke warga berisikan tautan download
        try {
            Mail::to($letterRequest->user->email)->send(new LetterReadyMail($letterRequest));
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permohonan surat berhasil disetujui dan PDF telah diterbitkan.',
            'data' => $letterRequest
        ]);
    }

    /**
     * Tolak permohonan surat
     */
    public function rejectLetterRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $letterRequest = LetterRequest::findOrFail($id);
        $letterRequest->status = 'rejected';
        $letterRequest->rejection_reason = $validated['rejection_reason'];
        $letterRequest->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Permohonan surat ditolak.',
            'data' => $letterRequest
        ]);
    }
}
