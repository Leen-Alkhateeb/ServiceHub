<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class VendorOfferImage
{
    /** @return array{0?: string, 1?: string|null} */
    public static function resolve(int $offerId, ?string ...$images): array
    {
        foreach (array_unique($images) as $image) {
            if (! $image) {
                continue;
            }
            if (filter_var($image, FILTER_VALIDATE_URL) && in_array(parse_url($image, PHP_URL_SCHEME), ['https', 'http'], true)) {
                return [$image, null];
            }

            $path = ltrim(str_replace('\\', '/', $image), '/');
            if (str_contains($path, '..') || ! preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $path)) {
                continue;
            }
            if (str_starts_with($path, 'assets/') && is_file(public_path($path))) {
                return [asset($path), null];
            }

            $path = str_starts_with($path, 'storage/') ? substr($path, 8) : $path;
            if (Storage::disk('public')->exists($path)) {
                return [route('vendor.service.image', $offerId), $path];
            }
        }

        return [];
    }

    public static function fallback(string $category = ''): string
    {
        $filename = match ($category) {
            'السباكة' => 'category-plumbing.jpg',
            'الكهرباء', 'الطاقة الشمسية', 'الإنارة' => 'category-electrical.jpg',
            'النجارة' => 'category-carpentry.jpg',
            'صيانة الحدائق' => 'category-garden.jpg',
            'التنظيف المتخصص' => 'category-cleaning.jpg',
            'الأقفال', 'الأرضيات', 'الشبكات المنزلية' => 'category-tools.jpg',
            default => 'category-maintenance.jpg',
        };

        return asset('assets/demo/directory/'.$filename);
    }
}
