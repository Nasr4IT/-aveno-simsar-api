<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

// Seeds the three category families named in the proposal (§1):
// سيارات (cars), عقارات (real estate), دراجات نارية (motorcycles) — each with
// its own dynamic specs, demonstrating the category_attributes pattern.
// Add more categories/attributes from the admin panel without touching code.
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->category('سيارات', 'cars', [
            ['key' => 'condition', 'label_ar' => 'الحالة', 'type' => 'select', 'options' => ['جديدة', 'مستعملة', 'مصدومة'], 'is_required' => true],
            ['key' => 'fuel_type', 'label_ar' => 'نوع الوقود', 'type' => 'select', 'options' => ['بنزين', 'ديزل', 'هجين', 'كهربائي']],
            ['key' => 'year', 'label_ar' => 'سنة الصنع', 'type' => 'number', 'is_required' => true],
            ['key' => 'mileage_km', 'label_ar' => 'الممشى (كم)', 'type' => 'number'],
        ]);

        $this->category('عقارات', 'real-estate', [
            ['key' => 'listing_type', 'label_ar' => 'نوع العرض', 'type' => 'select', 'options' => ['بيع', 'إيجار'], 'is_required' => true],
            ['key' => 'property_type', 'label_ar' => 'نوع العقار', 'type' => 'select', 'options' => ['شقة', 'فيلا', 'محل تجاري', 'أرض'], 'is_required' => true],
            ['key' => 'area_m2', 'label_ar' => 'المساحة (م²)', 'type' => 'number'],
            ['key' => 'rooms', 'label_ar' => 'عدد الغرف', 'type' => 'number'],
        ]);

        $this->category('دراجات نارية', 'motorcycles', [
            ['key' => 'condition', 'label_ar' => 'الحالة', 'type' => 'select', 'options' => ['جديدة', 'مستعملة'], 'is_required' => true],
            ['key' => 'engine_cc', 'label_ar' => 'سعة المحرك (cc)', 'type' => 'number'],
            ['key' => 'year', 'label_ar' => 'سنة الصنع', 'type' => 'number'],
        ]);
    }

    private function category(string $nameAr, string $slug, array $attributes): void
    {
        $category = Category::firstOrCreate(['slug' => $slug], ['name_ar' => $nameAr]);

        foreach ($attributes as $i => $attr) {
            $category->attributes_()->firstOrCreate(
                ['key' => $attr['key']],
                [...$attr, 'options' => $attr['options'] ?? null, 'sort_order' => $i]
            );
        }
    }
}
