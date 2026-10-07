<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'email', 'password',
        'avatar_path', 'governorate_id', 'city_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
            'rating_average' => 'decimal:2',
        ];
    }

    // The one place the ban rules live (the API and the /admin-panel both
    // call this). Admins can never be banned — returns false instead. A
    // ban also revokes every API token, so an already-logged-in user loses
    // access immediately rather than keeping it on their existing token,
    // and drops their push device tokens so they stop receiving pushes.
    // is_banned is deliberately not in $fillable, hence forceFill().
    public function ban(): bool
    {
        if ($this->hasRole('admin')) {
            return false;
        }

        $this->forceFill(['is_banned' => true])->save();
        $this->tokens()->delete();
        $this->deviceTokens()->delete();

        return true;
    }

    public function unban(): void
    {
        $this->forceFill(['is_banned' => false])->save();
    }

    // Who may use the admin API and the /admin-panel — checked on every
    // request (EnsureUserIsAdmin), not only at login, so revoking the role
    // or banning the account takes effect on an already-open session too.
    public function isActiveAdmin(): bool
    {
        return ! $this->is_banned && $this->hasRole('admin');
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class, 'rated_user_id');
    }

    public function ratingsGiven(): HasMany
    {
        return $this->hasMany(Rating::class, 'rater_user_id');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }
}
