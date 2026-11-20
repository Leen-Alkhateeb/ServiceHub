@props(['picture' => null, 'trade' => '', 'kind', 'accountId' => null])
@php
    $topic = match (true) {
        preg_match('/سباك|تسريب|مياه|ريّ|plumb/iu', $trade) === 1 => 'plumbing',
        preg_match('/كهرب|إنار|شمس|شبك|electr/iu', $trade) === 1 => 'electrical',
        $kind === 'category' && preg_match('/نجار|خشب|أقفال|carpent/iu', $trade) === 1 => 'carpentry',
        $kind === 'category' && preg_match('/حدائق|زراع|garden/iu', $trade) === 1 => 'garden',
        $kind === 'category' && preg_match('/تنظيف|clean/iu', $trade) === 1 => 'cleaning',
        $kind === 'category' && preg_match('/مكيف|تدفئ|تبريد|hvac/iu', $trade) !== 1 => 'tools',
        default => 'maintenance',
    };
    $fallback = asset("assets/demo/directory/$kind-$topic.jpg");
    $path = is_string($picture) ? ltrim($picture, '/') : '';
    $source = $fallback;

    if ($accountId && str_starts_with($path, 'profile-photos/')) {
        $source = route('account.picture.show', $accountId);
    } elseif (filter_var($picture, FILTER_VALIDATE_URL) && str_starts_with($picture, 'https://')) {
        $source = $picture;
    } elseif ($path && ! str_contains($path, '..')) {
        $file = is_file(public_path($path)) ? $path : 'storage/'.(str_starts_with($path, 'storage/') ? substr($path, 8) : $path);
        if (is_file(public_path($file))) {
            $source = asset($file);
        }
    }
@endphp
<img class="directory-photo" data-photo-topic="{{ $topic }}" src="{{ $source }}" data-directory-fallback="{{ $fallback }}" alt="" width="480" height="300" loading="lazy">
