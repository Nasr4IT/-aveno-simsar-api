<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Database\Seeder;

// Syria's 14 governorates + a couple of seed cities each, matching the
// Sham Cash market. Expand the city lists freely — nothing else depends
// on the exact count.
class GeoSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'دمشق' => ['دمشق'],
            'ريف دمشق' => ['دوما', 'التل', 'الزبداني'],
            'حلب' => ['حلب', 'اعزاز', 'منبج'],
            'حمص' => ['حمص', 'تدمر', 'الرستن'],
            'حماة' => ['حماة', 'مصياف', 'السلمية'],
            'اللاذقية' => ['اللاذقية', 'جبلة'],
            'طرطوس' => ['طرطوس', 'بانياس'],
            'إدلب' => ['إدلب', 'معرة النعمان'],
            'الرقة' => ['الرقة'],
            'دير الزور' => ['دير الزور', 'الميادين'],
            'الحسكة' => ['الحسكة', 'القامشلي'],
            'درعا' => ['درعا', 'إزرع'],
            'السويداء' => ['السويداء'],
            'القنيطرة' => ['القنيطرة'],
        ];

        foreach ($data as $governorateName => $cities) {
            $governorate = Governorate::firstOrCreate(['name_ar' => $governorateName]);

            foreach ($cities as $cityName) {
                City::firstOrCreate(['governorate_id' => $governorate->id, 'name_ar' => $cityName]);
            }
        }
    }
}
