<?php

namespace App\Http\Controllers\Api\Surat;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $settingsData = $request->input('settings');
        
        if (is_string($settingsData)) {
            $settingsData = json_decode($settingsData, true);
        }
        
        if (!is_array($settingsData)) {
            $settingsData = [];
        }

        if ($request->hasFile('kop_logo_file')) {
            $file = $request->file('kop_logo_file');
            $path = $file->store('settings', 'public');
            $settingsData['kop_logo'] = asset('storage/' . $path);
        }

        foreach ($settingsData as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengaturan berhasil disimpan.',
            'logo_url' => $settingsData['kop_logo'] ?? null
        ]);
    }
}
