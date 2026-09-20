<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    // Local/dev only — change the password immediately on any real environment.
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['phone' => '0999999999'],
            ['name' => 'Aveno Admin', 'email' => 'admin@avenocode-marketplace.com', 'password' => Hash::make('change-me-now')]
        );

        $admin->assignRole('admin');
    }
}
