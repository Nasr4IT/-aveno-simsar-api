<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Rewrites phone numbers stored before normalisation existed (e.g.
// "+963999111222") into the canonical 09XXXXXXXX form, so those users can log
// in with any spelling. If the canonical form is already taken by another
// account (the same person signed up twice), the row is left as-is and
// logged for an admin to merge by hand — never silently overwritten.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->select('id', 'phone')->orderBy('id')->chunkById(500, function ($users) {
            foreach ($users as $user) {
                $normalized = PhoneNumber::normalize($user->phone);

                if ($normalized === $user->phone) {
                    continue;
                }

                if (DB::table('users')->where('phone', $normalized)->exists()) {
                    Log::warning("Phone normalisation skipped for user {$user->id}: {$normalized} already belongs to another account.");

                    continue;
                }

                DB::table('users')->where('id', $user->id)->update(['phone' => $normalized]);
            }
        });
    }

    public function down(): void
    {
        // Irreversible on purpose: the original spelling isn't kept.
    }
};
