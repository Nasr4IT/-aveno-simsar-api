<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Public URL of an uploaded file (ad photo, banner, avatar).
 *
 * On the default local "public" disk the URL is built from the host the
 * visitor actually used (asset()), not from APP_URL. With APP_URL-based URLs
 * a mismatch — e.g. APP_URL=http://localhost:8000 while browsing
 * http://127.0.0.1:8000, or http vs https behind the proxy — made every
 * uploaded photo fail to load. Cloud disks (S3/R2) keep their own URLs.
 */
class MediaUrl
{
    public static function for(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = config('filesystems.default');

        if (config("filesystems.disks.{$disk}.driver") === 'local') {
            return asset('storage/'.ltrim($path, '/'));
        }

        return Storage::disk($disk)->url($path);
    }
}
