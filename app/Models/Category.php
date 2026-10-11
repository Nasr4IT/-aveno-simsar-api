<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id', 'name_ar', 'name_en', 'slug', 'icon_path', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // Random suffix: the slug is derived from the name, and two categories
    // (e.g. "Other" under two different parents) can share a name.
    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            $category->slug ??= Str::slug($category->name_en ?? $category->name_ar).'-'.Str::random(4);
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function attributes_(): HasMany
    {
        // Named attributes_ to avoid clashing with Eloquent's own $attributes property.
        return $this->hasMany(CategoryAttribute::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
}
