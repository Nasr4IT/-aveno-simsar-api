<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;
use App\Models\City;
use App\Models\User;
use App\Services\AdAttributeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Test data for the Flutter team on a staging/live server. NOT part of
 * DatabaseSeeder — run it on purpose, once:
 *
 *   php artisan db:seed --class=DemoDataSeeder --force
 *
 * Creates two normal accounts (password "demo-pass-123") and six approved
 * ads with generated photos, so lists, detail, favorites, chat and ratings
 * can be tested end to end. Safe to re-run: accounts are reused and their
 * demo ads are recreated, nothing else is touched. Requires the regular
 * seeders (geo, categories) to have run first.
 */
class DemoDataSeeder extends Seeder
{
    public const PASSWORD = 'demo-pass-123';

    public function run(AdAttributeService $attributes): void
    {
        $city = City::with('governorate')->orderBy('id')->firstOrFail();

        $seller = $this->account('0933000001', 'بائع تجريبي (Demo Seller)', $city);
        $buyer = $this->account('0933000002', 'مشتري تجريبي (Demo Buyer)', $city);

        Ad::where('user_id', $seller->id)->get()->each->forceDelete();

        $ads = [
            ['cars', 'كيا سبورتاج 2019 — Kia Sportage', 15500, ['condition' => 'مستعملة', 'fuel_type' => 'بنزين', 'year' => '2019', 'mileage_km' => '82000'], [12, 112, 128]],
            ['cars', 'تويوتا كورولا 2016 — Toyota Corolla', 9800, ['condition' => 'مستعملة', 'fuel_type' => 'بنزين', 'year' => '2016'], [181, 52, 52]],
            ['real-estate', 'شقة للإيجار في المزة — Apartment for rent', 350, ['listing_type' => 'إيجار', 'property_type' => 'شقة', 'area_m2' => '120', 'rooms' => '3'], [3, 44, 58]],
            ['real-estate', 'محل تجاري للبيع — Shop for sale', 42000, ['listing_type' => 'بيع', 'property_type' => 'محل تجاري', 'area_m2' => '45'], [120, 90, 40]],
            ['motorcycles', 'دراجة نارية 150cc — Motorcycle', 1200, ['condition' => 'مستعملة', 'engine_cc' => '150', 'year' => '2021'], [60, 60, 70]],
            ['motorcycles', 'دراجة جديدة 125cc — New motorcycle', 1500, ['condition' => 'جديدة', 'engine_cc' => '125'], [12, 167, 181]],
        ];

        foreach ($ads as $i => [$slug, $title, $price, $values, $rgb]) {
            $category = Category::with('attributes_')->where('slug', $slug)->first();
            if (! $category) {
                continue;
            }

            $ad = Ad::create([
                'user_id' => $seller->id,
                'category_id' => $category->id,
                'governorate_id' => $city->governorate_id,
                'city_id' => $city->id,
                'title' => $title,
                'slug' => Str::slug(Str::after($title, '— ')).'-demo-'.Str::lower(Str::random(5)),
                'description' => "إعلان تجريبي لاختبار التطبيق.\nDemo ad for testing the app.",
                'price' => $price,
                'currency' => 'USD',
                'is_negotiable' => $i % 2 === 0,
                'status' => 'approved',
                'published_at' => now()->subHours($i),
                'is_featured' => $i < 2,
            ]);

            $attributes->sync($ad, $category, $values);
            $this->photo($ad, $rgb);
        }

        $this->command?->info('Demo accounts: 0933000001 (seller) and 0933000002 (buyer), password: '.self::PASSWORD);
        $this->command?->info('Approved demo ads created: '.Ad::where('user_id', $seller->id)->count());
    }

    private function account(string $phone, string $name, City $city): User
    {
        return User::updateOrCreate(['phone' => $phone], [
            'name' => $name,
            'password' => Hash::make(self::PASSWORD),
            'governorate_id' => $city->governorate_id,
            'city_id' => $city->id,
            'is_banned' => false,
        ]);
    }

    // A plain coloured 800×600 JPEG, so the ad has a real image URL.
    private function photo(Ad $ad, array $rgb): void
    {
        $img = imagecreatetruecolor(800, 600);
        imagefill($img, 0, 0, imagecolorallocate($img, ...$rgb));
        ob_start();
        imagejpeg($img, null, 80);
        $jpeg = ob_get_clean();
        imagedestroy($img);

        $path = "ads/{$ad->id}/demo-".Str::random(10).'.jpg';
        Storage::disk(config('filesystems.default'))->put($path, $jpeg);

        AdImage::create(['ad_id' => $ad->id, 'path' => $path, 'is_cover' => true, 'sort_order' => 0]);
    }
}
