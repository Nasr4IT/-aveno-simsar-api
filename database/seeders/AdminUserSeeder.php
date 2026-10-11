<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public const PHONE = '0999999999';

    // What earlier versions of this seeder set. It's public in the repo's
    // history, so an admin still on it gets moved off it below.
    private const OLD_DEFAULT_PASSWORD = 'change-me-now';

    // Runs on every boot (Dockerfile), so it must be safe to repeat: the
    // password is only rewritten when it differs from ADMIN_PASSWORD (or is
    // the old public default), never re-hashed for nothing.
    public function run(): void
    {
        $configured = config('admin.password');
        $admin = User::withTrashed()->where('phone', self::PHONE)->first();

        // Someone deleted the seeded admin on purpose — respect that, and
        // don't crash every boot trying to re-create the phone number.
        if ($admin?->trashed()) {
            $this->command?->warn('The seeded admin account ('.self::PHONE.') was deleted — leaving it deleted.');

            return;
        }

        if ($configured) {
            if (! $admin) {
                $admin = $this->createAdmin($configured);
            } elseif (! Hash::check($configured, $admin->password)) {
                $admin->update(['password' => $configured]);
            }
        } else {
            // No hard-coded fallback: with no ADMIN_PASSWORD there is no
            // password anyone knows — the account exists but can't be
            // logged into until one is set.
            if (! $admin) {
                $admin = $this->createAdmin(Str::random(40));
                $this->command?->warn('Admin account created without a usable password — set ADMIN_PASSWORD and re-run the seeders to log in as '.self::PHONE.'.');
            } elseif (Hash::check(self::OLD_DEFAULT_PASSWORD, $admin->password)) {
                $admin->update(['password' => Str::random(40)]);
                $this->command?->warn('The admin account was still on the old public default password, which has been disabled — set ADMIN_PASSWORD and re-run the seeders to log in as '.self::PHONE.'.');
            }
        }

        $admin->assignRole('admin');
    }

    private function createAdmin(string $password): User
    {
        return User::create([
            'phone' => self::PHONE,
            'name' => 'Aveno Admin',
            'email' => 'admin@avenocode-marketplace.com',
            'password' => $password, // hashed by the User model's 'hashed' cast
        ]);
    }
}
