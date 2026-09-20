<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdAttributeValue extends Model
{
    protected $fillable = ['ad_id', 'category_attribute_id', 'value'];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(CategoryAttribute::class, 'category_attribute_id');
    }
}
