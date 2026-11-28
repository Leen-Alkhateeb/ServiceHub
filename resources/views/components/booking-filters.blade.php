@if ($errors->hasAny(['q', 'from', 'to', 'status', ...array_keys($extraFilters ?? [])]))
    <p class="notice" role="alert">تحقق من قيمة البحث والتواريخ المختارة. يجب أن يكون تاريخ النهاية مساويًا لتاريخ البداية أو بعده.</p>
@endif
<form method="GET" action="{{ $action }}" class="panel hub-filters">
    @if (!empty($filters['archived']))
        <input type="hidden" name="archived" value="1">
    @endif
    <div class="field"><label for="booking-search">بحث</label><input id="booking-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="{{ $placeholder ?? 'اسم الخدمة' }}"></div>
    <div class="field"><label for="booking-from">من تاريخ الحجز</label><input id="booking-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
    <div class="field"><label for="booking-to">إلى تاريخ الحجز</label><input id="booking-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
    @isset($labels)
        <div class="field">
            <label for="booking-status">{{ $statusLabel ?? 'حالة المبلغ' }}</label>
            <select id="booking-status" name="status">
                <option value="">جميع الحالات</option>
                @foreach ($labels as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endisset
    @foreach($extraFilters ?? [] as $key => [$title, $choices])
        <div class="field"><label for="booking-{{ $key }}">{{ $title }}</label>
            <select id="booking-{{ $key }}" name="{{ $key }}"><option value="">الكل</option>
                @foreach($choices as $value => $label)<option value="{{ $value }}" @selected(($filters[$key] ?? '') === $value)>{{ $label }}</option>@endforeach
            </select>
        </div>
    @endforeach
    <div class="filter-actions">
        <button class="btn btn-yellow" type="submit">تصفية</button>
        <a class="btn btn-outline" href="{{ $action }}">مسح</a>
    </div>
</form>
