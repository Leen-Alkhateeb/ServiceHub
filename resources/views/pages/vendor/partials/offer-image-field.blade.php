<div class="field offer-image-field" data-offer-image-field>
    <label for="{{ $imageFieldId }}">{{ $imageFieldLabel ?? 'صورة الخدمة (اختياري)' }}</label>
    <x-file-picker>
        <input id="{{ $imageFieldId }}" name="image" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="{{ $imageFieldId }}-help">
    </x-file-picker>
    <small class="muted" id="{{ $imageFieldId }}-help">صورة واحدة بصيغة JPG أو PNG أو WebP، حتى 5 ميغابايت. المقاس المقترح 960 × 540 بكسل.</small>
    <figure class="offer-image-preview" data-offer-image-preview @if (empty($currentImageUrl)) hidden @endif>
        <img class="offer-image" width="480" height="270" alt="" @if (!empty($currentImageUrl)) src="{{ $currentImageUrl }}" @endif>
        <figcaption>الصورة الحالية</figcaption>
    </figure>
</div>
