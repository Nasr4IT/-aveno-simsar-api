<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ad extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'category_id', 'governorate_id', 'city_id',
        'title', 'slug', 'description', 'price', 'currency',
        'latitude', 'longitude', 'status', 'rejection_reason',
        'reviewed_by', 'reviewed_at', 'published_at',
        'is_featured', 'ad_package_id', 'featured_until',
        'is_negotiable', 'address', 'allow_chat', 'allow_calls', 'hide_phone',
    ];

    // The publish-form options. CONTACT_OPTIONS only change how buyers reach
    // the seller, not what the ad says, so editing just those doesn't send
    // the ad back for review.
    public const OPTION_FIELDS = ['is_negotiable', 'address', 'allow_chat', 'allow_calls', 'hide_phone'];

    public const CONTACT_OPTIONS = ['allow_chat', 'allow_calls', 'hide_phone'];

    // Same defaults as the migration, so a freshly created model reports
    // them correctly before it is ever re-read from the database.
    protected $attributes = [
        'is_negotiable' => false,
        'allow_chat' => true,
        'allow_calls' => true,
        'hide_phone' => false,
    ];

    public static function optionRules(bool $partial = false): array
    {
        $prefix = $partial ? ['sometimes'] : [];

        return [
            'is_negotiable' => [...$prefix, 'boolean'],
            'address' => [...$prefix, 'nullable', 'string', 'max:255'],
            'allow_chat' => [...$prefix, 'boolean'],
            'allow_calls' => [...$prefix, 'boolean'],
            'hide_phone' => [...$prefix, 'boolean'],
        ];
    }

    // The seller's phone as shown on the ad: only when they allow calls and
    // haven't hidden their number.
    public function sharesPhone(): bool
    {
        return $this->allow_calls && ! $this->hide_phone;
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_featured' => 'boolean',
            'is_negotiable' => 'boolean',
            'allow_chat' => 'boolean',
            'allow_calls' => 'boolean',
            'hide_phone' => 'boolean',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'featured_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function adPackage(): BelongsTo
    {
        return $this->belongsTo(AdPackage::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(AdImage::class)->orderBy('sort_order');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(AdAttributeValue::class);
    }

    public function favoritedBy(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('featured_until', '>', now());
    }
}
