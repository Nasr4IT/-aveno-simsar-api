<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryAttribute extends Model
{
    // Query params GET /api/ads already uses for its own filters/sorting
    // (AdController@index). An attribute keyed with one of these could
    // never be filtered on, since applyAttributeFilters() strips them first.
    public const RESERVED_FILTER_KEYS = [
        'category_id', 'governorate_id', 'city_id', 'min_price', 'max_price', 'q', 'page', 'sort', 'lat', 'lng',
    ];

    protected $fillable = [
        'category_id', 'key', 'label_ar', 'label_en', 'type',
        'options', 'is_required', 'is_filterable', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
