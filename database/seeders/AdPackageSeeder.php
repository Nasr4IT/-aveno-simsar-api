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
            ['name_ar' => 'إبراز 15 يوم', 'name_en' => 'Featured for 15 days', 'duration_days' => 15, 'price' => 5],
            ['name_ar' => 'إبراز 30 يوم', 'name_en' => 'Featured for 30 days', 'duration_days' => 30, 'price' => 9],
            ['name_ar' => 'إبراز 60 يوم', 'name_en' => 'Featured for 60 days', 'duration_days' => 60, 'price' => 15],
        ])->each(function ($p) {
            // Safe to re-run: never changes a price the admin may have set;
            // only fills a blank English name.
            $package = AdPackage::firstOrCreate(['duration_days' => $p['duration_days']], $p);
            if (blank($package->name_en)) {
                $package->update(['name_en' => $p['name_en']]);
            }
        });
    }
}
