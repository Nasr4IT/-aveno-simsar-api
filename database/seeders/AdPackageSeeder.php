<?php

namespace Database\Seeders;

use App\Models\AdPackage;
use Illuminate\Database\Seeder;

class AdPackageSeeder extends Seeder
{
    // Matches proposal §2/§3: featured-ad packages of 15/30/60 days.
    public function run(): void
    {
        collect([
            ['name_ar' => 'إبراز 15 يوم', 'duration_days' => 15, 'price' => 5],
            ['name_ar' => 'إبراز 30 يوم', 'duration_days' => 30, 'price' => 9],
            ['name_ar' => 'إبراز 60 يوم', 'duration_days' => 60, 'price' => 15],
        ])->each(fn ($p) => AdPackage::firstOrCreate(['duration_days' => $p['duration_days']], $p));
    }
}
