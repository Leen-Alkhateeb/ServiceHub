@if ($errors->hasAny(['q', 'from', 'to', 'status']))
    <p class="notice" role="alert">تحقق من قيمة البحث والتواريخ المختارة. يجب أن يكون تاريخ النهاية مساويًا لتاريخ البداية أو بعده.</p>
@endif
<form method="GET" action="{{ $action }}" class="panel hub-filters">
    <div class="field"><label for="booking-search">بحث</label><input id="booking-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="{{ $placeholder ?? 'اسم الخدمة' }}"></div>
    <div class="field"><label for="booking-from">من تاريخ الحجز</label><input id="booking-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
    <div class="field"><label for="booking-to">إلى تاريخ الحجز</label><input id="booking-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
    @isset($labels)
        <div class="field">
            <label for="booking-status">حالة المبلغ</label>
            <select id="booking-status" name="status">
                <option value="">جميع الحالات</option>
                @foreach ($labels as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endisset
    <div class="filter-actions">
        <button class="btn btn-yellow" type="submit">تصفية</button>
        <a class="btn btn-outline" href="{{ $action }}">مسح</a>
    </div>
</form>
