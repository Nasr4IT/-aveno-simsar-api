<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdPackage extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'duration_days', 'price', 'currency', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
