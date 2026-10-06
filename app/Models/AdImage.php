<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AdImage extends Model
{
    protected $fillable = ['ad_id', 'path', 'is_cover', 'sort_order'];

    protected function casts(): array
    {
        return ['is_cover' => 'boolean'];
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    // Resolved against filesystems.default rather than hardcoded to the
    // local public disk's /storage path, so this still points at the
    // right place if that's switched to an S3-compatible disk (e.g.
    // Cloudflare R2 — see docs/HOW_IT_WORKS.md § Persistent Image Storage).
    public function getUrlAttribute(): string
    {
        return Storage::disk(config('filesystems.default'))->url($this->path);
    }
}
