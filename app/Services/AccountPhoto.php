<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class AccountPhoto
{
    public static function url(int $accountId, ?string $image): ?string
    {
        if ($image && filter_var($image, FILTER_VALIDATE_URL) && in_array(parse_url($image, PHP_URL_SCHEME), ['https', 'http'], true)) {
            return $image;
        }

        $path = self::normalize($image);
        if (str_starts_with($path, 'assets/') && ! str_contains($path, '..') && is_file(public_path($path))) {
            return asset($path);
        }

        return self::storedFile($accountId, $image) ? route('account.picture.show', $accountId) : null;
    }

     
    public static function storedFile(int $accountId, ?string $image): ?array
    {
        $path = self::normalize($image);
        if (str_contains($path, '..') || ! preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $path)) {
            return null;
        }

        $disk = 'public';
        if (str_starts_with($path, 'profile-photos/')) {
            if (! self::isManaged($accountId, $path)) {
                return null;
            }
            $disk = 'local';
        }

        return Storage::disk($disk)->exists($path) ? [$disk, $path] : null;
    }

    public static function isManaged(int $accountId, ?string $path): bool
    {
        return is_string($path) && dirname($path) === 'profile-photos/'.$accountId
            && preg_match('/\A[a-zA-Z0-9]+\.(jpe?g|png|webp)\z/i', basename($path)) === 1;
    }

    private static function normalize(?string $image): string
    {
        $path = ltrim(str_replace('\\', '/', $image ?? ''), '/');

        return str_starts_with($path, 'storage/') ? substr($path, 8) : $path;
    }
}
