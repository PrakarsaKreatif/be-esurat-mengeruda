<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat Role Super Admin
        $superAdminRole = \App\Models\Role::firstOrCreate(
            ['name' => 'Super Admin'],
            ['description' => 'Administrator Utama dengan akses penuh']
        );

        // 2. (Opsional) Buat Aplikasi default
        $ssoApp = \App\Models\Application::firstOrCreate(
            ['name' => 'SSO Portal'],
            ['url' => 'http://localhost:5174', 'description' => 'Portal SSO Login']
        );
        
        // Kaitkan role dengan aplikasi
        $superAdminRole->applications()->syncWithoutDetaching([$ssoApp->id]);

        // 3. Buat User Super Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@mengeruda.id'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password123'), // Default password
            ]
        );

        // 4. Assign Role ke User
        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $this->call([
            RbacSeeder::class,
            ESuratRoleSeeder::class,
            LetterTemplateSeeder::class,
            // TourismSeeder::class,
        ]);
    }
}
