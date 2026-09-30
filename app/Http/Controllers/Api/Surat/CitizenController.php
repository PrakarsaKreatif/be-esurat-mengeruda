<?php

namespace App\Http\Controllers\Api\Surat;

use App\Http\Controllers\Controller;
use App\Mail\Surat\NewLetterRequestMail;
use App\Mail\Surat\LetterRevisionRequestMail;
use App\Mail\Surat\LetterFinalizedMail;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class CitizenController extends Controller
{
    /**
     * Daftar semua template surat yang tersedia
     */
    public function getTemplates()
    {
        $templates = LetterTemplate::orderBy('name', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $templates
        ]);
    }

    /**
     * Detail satu template beserta skema required_fields
     */
    public function getTemplateById($id)
    {
        $template = LetterTemplate::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $template
        ]);
    }

    /**
     * Pengajuan surat baru oleh warga
     */
    public function submitRequest(Request $request)
    {
        $user = $request->user();

        // Pastikan akun warga sudah disetujui admin
        if (!$user->is_approved) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda sedang diverifikasi oleh Admin. Anda belum dapat mengajukan permohonan surat.'
            ], 403);
        }

        $validated = $request->validate([
            'template_id' => 'required|exists:letter_templates,id',
            'form_data' => 'required|array',
        ]);

        $formData = $validated['form_data'];
        
        $requesterData = [
            'name' => $user->name,
            'nik' => $user->nik,
            'phone' => $user->phone,
        ];

        if (isset($formData['family_member_name'])) {
            $requesterData['name'] = $formData['family_member_name'];
            $requesterData['nik'] = $formData['family_member_nik'] ?? null;
        }

        $letterRequest = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $validated['template_id'],
            'form_data' => $formData,
            'requester_data' => $requesterData,
            'status' => 'pending',
        ]);

        $letterRequest->load(['user', 'template']);

        // Kirim email notifikasi ke Admin
        try {
            $admins = \App\Models\User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'admin_surat', 'Admin']);
            })->get();

            foreach ($admins as $admin) {
                if ($admin->email) {
                    Mail::to($admin->email)->send(new NewLetterRequestMail($letterRequest));
                }
            }
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permohonan surat berhasil dikirim dan sedang menunggu tinjauan Admin.',
            'data' => $letterRequest
        ], 201);
    }

    /**
     * Riwayat pengajuan surat warga yang sedang login
     */
    public function getMyRequests(Request $request)
    {
        $requests = LetterRequest::with('template')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests
        ]);
    }

    public function acceptLetter(Request $request, $id)
    {
        $letterRequest = LetterRequest::where('user_id', $request->user()->id)->findOrFail($id);

        if ($letterRequest->status !== 'review') {
            return response()->json([
                'status' => 'error',
                'message' => 'Status surat tidak valid untuk diterima.'
            ], 400);
        }

        $letterRequest->status = 'approved';
        $letterRequest->save();
        $letterRequest->load(['user', 'template']);

        // Kirim email notifikasi ke admin
        try {
            $admins = \App\Models\User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'admin_surat', 'Admin']);
            })->get();

            foreach ($admins as $admin) {
                if ($admin->email) {
                    Mail::to($admin->email)->send(new LetterFinalizedMail($letterRequest));
                }
            }
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Surat berhasil diterima dan final.',
            'data' => $letterRequest
        ]);
    }

    public function requestRevision(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string'
        ]);

        $letterRequest = LetterRequest::where('user_id', $request->user()->id)->findOrFail($id);

        if ($letterRequest->status !== 'review') {
            return response()->json([
                'status' => 'error',
                'message' => 'Status surat tidak valid untuk direvisi.'
            ], 400);
        }

        $letterRequest->status = 'revision';
        $letterRequest->rejection_reason = $validated['reason'];
        $letterRequest->save();
        $letterRequest->load(['user', 'template']);

        // Kirim email notifikasi ke admin
        try {
            $admins = \App\Models\User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'admin_surat', 'Admin']);
            })->get();

            foreach ($admins as $admin) {
                if ($admin->email) {
                    Mail::to($admin->email)->send(new LetterRevisionRequestMail($letterRequest));
                }
            }
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permintaan revisi berhasil dikirim ke Admin.',
            'data' => $letterRequest
        ]);
    }
}
