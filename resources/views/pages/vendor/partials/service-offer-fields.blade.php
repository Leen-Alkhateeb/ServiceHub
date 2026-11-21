<div class="offer-fields">
    <div class="field"><label for="{{ $prefix }}-price">السعر (د.أ)</label><input id="{{ $prefix }}-price" name="price" type="number" min="0.01" max="99999999.99" step="0.01" value="{{ $errors->$prefix->any() && is_scalar(old('price')) ? old('price') : '' }}" required></div>
    <div class="field"><label for="{{ $prefix }}-duration">المدة التقريبية (دقيقة)</label><input id="{{ $prefix }}-duration" name="duration_minutes" type="number" min="5" max="1440" value="{{ $errors->$prefix->any() && is_scalar(old('duration_minutes')) ? old('duration_minutes') : '' }}"></div>
</div>
<div class="field"><label for="{{ $prefix }}-description">وصف العرض وما يشمله السعر</label><textarea id="{{ $prefix }}-description" name="description" rows="4" maxlength="3000" required>{{ $errors->$prefix->any() && is_string(old('description')) ? old('description') : '' }}</textarea></div>
@include('pages.vendor.partials.offer-image-field', ['imageFieldId' => $prefix.'-image'])
