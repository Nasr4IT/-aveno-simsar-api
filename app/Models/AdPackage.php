<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;

class AdPackage extends Model
{
    use HasLocalizedFields;

    protected $fillable = ['name_ar', 'name_en', 'duration_days', 'price', 'currency', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
