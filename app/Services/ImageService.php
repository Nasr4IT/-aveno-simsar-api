<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Exceptions\RuntimeException as InterventionRuntimeException;
use Intervention\Image\ImageManager;

class ImageService
{
    private const MAX_DIMENSION = 1600;

    private const JPEG_QUALITY = 80;

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = ImageManager::gd();
    }

    // Resizes (only if larger than MAX_DIMENSION, aspect ratio preserved),
    // re-encodes as JPEG at JPEG_QUALITY, and stores on the given disk.
    // Raw phone-camera uploads are several MB each otherwise (see
    // AdController@storeImages).
    public function storeResized(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        // Laravel's 'image' validation rule accepts formats GD can't decode
        // (e.g. SVG) — without this, such an upload crashes with an
        // unhandled 500 instead of a normal validation error.
        try {
            $image = $this->manager->read($file->getRealPath());
        } catch (InterventionRuntimeException $e) {
            throw ValidationException::withMessages([
                'images' => ['تعذّرت معالجة أحد الملفات المرفوعة — الرجاء رفع صورة بصيغة JPG أو PNG.'],
            ]);
        }

        $image->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);

        $path = trim($directory, '/').'/'.Str::random(40).'.jpg';

        Storage::disk($disk)->put($path, (string) $image->toJpeg(self::JPEG_QUALITY));

        return $path;
    }
}
