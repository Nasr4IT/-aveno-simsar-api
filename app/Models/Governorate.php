<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar', 'name_en', 'latitude', 'longitude', 'is_active'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
