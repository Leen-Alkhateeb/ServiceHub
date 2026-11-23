
<div class="field @error('name') has-error @enderror">
    <label for="name">اسم القسم</label>
    <input type="text" id="name" name="name" value="{{ old('name', $category?->name) }}" maxlength="255" required>
    @error('name')
        <span class="field-error">{{ $message }}</span>
    @enderror
</div>

<div class="field @error('description') has-error @enderror">
    <label for="description">وصف القسم</label>
    <textarea id="description" name="description" rows="4" maxlength="1000" required>{{ old('description', $category?->description) }}</textarea>
    <span class="field-hint">وصف قصير يظهر للعملاء في صفحة الأقسام.</span>
    @error('description')
        <span class="field-error">{{ $message }}</span>
    @enderror
</div>

<div class="field @error('image') has-error @enderror">
    <label for="image">{{ $category ? 'تغيير صورة القسم (اختياري)' : 'صورة القسم' }}</label>

    @if ($category)
        <div class="current-image">
            <x-directory-photo :picture="$category->image" :trade="$category->name" kind="category" />
            <span class="field-hint">الصورة الحالية</span>
        </div>
    @endif

    <x-file-picker><input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" @unless ($category) required @endunless></x-file-picker>
    <span class="field-hint">JPG أو PNG أو WEBP، بحجم أقصى 2 ميغابايت.</span>
    @error('image')
        <span class="field-error">{{ $message }}</span>
    @enderror
</div>
