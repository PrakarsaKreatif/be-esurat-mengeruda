<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'kop_pemda', 'value' => 'PEMERINTAH KABUPATEN NAGEKEO<br />KECAMATAN SOA', 'group' => 'kop'],
            ['key' => 'kop_desa', 'value' => 'DESA MENGERUDA', 'group' => 'kop'],
            ['key' => 'kop_alamat', 'value' => 'Alamat: Jl. Raya Mengeruda, Soa, Kabupaten Nagekeo, Nusa Tenggara Timur', 'group' => 'kop'],
            ['key' => 'kop_logo', 'value' => '', 'group' => 'kop'],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'group' => $setting['group']]
            );
        }
    }
}
