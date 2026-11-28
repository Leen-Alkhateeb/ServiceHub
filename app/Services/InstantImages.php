<?php

namespace App\Services;

use App\Models\BookingEvidence;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InstantImages
{
    public static function rules(string $field): array
    {
        return [$field => ['nullable', 'array', 'max:5'], "$field.*" => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4000,max_height=4000']];
    }

    public function save(array $uploads, string $kind, string $parent, int $id, int $actor, array &$paths): void
    {
        if ($uploads && ! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages(['images' => 'رفع الصور يحتاج تفعيل معالجة الصور على السيرفر.']);
        }
        foreach ($uploads as $upload) {
            $image = @imagecreatefromstring($upload->get());
            if (! $image) {
                throw ValidationException::withMessages(['images' => 'إحدى الصور تالفة أو غير صالحة.']);
            }
            ob_start();
            try {
                $encoded = imagewebp($image, null, 90);
                $contents = ob_get_contents();
            } finally {
                ob_end_clean();
            }
            if (! $encoded || ! $contents) {
                throw ValidationException::withMessages(['images' => 'تعذّر معالجة الصورة المرفقة.']);
            }
            $path = 'instant-evidence/'.bin2hex(random_bytes(20)).'.webp';
            if (! Storage::disk('local')->put($path, $contents)) {
                throw ValidationException::withMessages(['images' => 'تعذّر حفظ الصورة المرفقة.']);
            }
            $paths[] = $path;
            $record = new BookingEvidence;
            $record->forceFill([$parent => $id, 'kind' => $kind, 'path' => $path, 'uploaded_by' => $actor, 'created_at' => now()])->save();
        }
    }
}
