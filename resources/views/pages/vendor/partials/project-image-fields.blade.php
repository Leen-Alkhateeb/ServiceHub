<fieldset class="field project-image-fields" data-project-image-fields @if ($requiresImage) data-requires-image @endif>
    <legend>صور المشروع</legend>
    <small>حتى 8 صور بصيغة JPG أو PNG أو WebP، بحجم 5 ميغابايت لكل صورة.</small>
    @foreach ($projectImages as $photo)
        <div class="item-row project-existing-image">
            @if ($photoUrl = $photo->displayUrl())
                <img src="{{ $photoUrl }}" alt="" width="72" height="54" loading="lazy">
            @endif
            <label>
                <input type="checkbox" name="remove_images[]" value="{{ $photo->id }}" @checked(in_array((string) $photo->id, (array) old('remove_images', []), true))>
                حذف الصورة {{ $loop->iteration }} عند الحفظ
            </label>
        </div>
    @endforeach
    <div data-image-rows>
        <div class="project-image-row" data-image-row @if ($projectImages->count() >= 8) hidden @endif>
            <label class="project-image-picker">
                <span data-image-label>صورة جديدة 1</span>
                <x-file-picker><input name="images[]" type="file" accept="image/jpeg,image/png,image/webp" @required($requiresImage) @disabled($projectImages->count() >= 8)></x-file-picker>
            </label>
            <img data-image-preview hidden alt="" width="96" height="72">
            <button type="button" class="btn btn-outline btn-sm" data-remove-image-field hidden>إزالة الحقل</button>
        </div>
    </div>
    <button type="button" class="btn btn-outline btn-sm" data-add-image-field hidden>+ إضافة حقل صورة</button>
    <small data-image-limit role="status"></small>
</fieldset>
