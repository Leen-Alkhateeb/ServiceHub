@extends('layouts.app')
@section('title', 'الجدول الأسبوعي | ServiceHub')
@section('page_id', 'vendor-availability')
@push('styles')
<style>
body[data-page="vendor-availability"] .availability-create-fields {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
body[data-page="vendor-availability"] .availability-create-form .field {
    min-width: 0;
    margin-bottom: 12px;
}
body[data-page="vendor-availability"] .availability-days {
    display: grid;
    gap: 20px;
}
body[data-page="vendor-availability"] .availability-day-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 0 0 9px;
}
body[data-page="vendor-availability"] .availability-day-heading h3 {
    margin: 0;
    font-size: 16px;
}
body[data-page="vendor-availability"] .availability-period-count {
    color: var(--muted);
    font-size: 12px;
}
body[data-page="vendor-availability"] .availability-periods {
    display: grid;
    gap: 9px;
}
body[data-page="vendor-availability"] .availability-card {
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 11px;
    background: #fff;
}
body[data-page="vendor-availability"] .availability-card[open] {
    border-color: #dfcf8b;
    box-shadow: 0 4px 14px #2021240a;
}
body[data-page="vendor-availability"] .availability-summary {
    display: grid;
    grid-template-columns: minmax(120px, 1fr) auto auto;
    align-items: center;
    gap: 16px;
    min-height: 62px;
    padding: 10px 15px;
    cursor: pointer;
    list-style: none;
}
body[data-page="vendor-availability"] .availability-summary::-webkit-details-marker {
    display: none;
}
body[data-page="vendor-availability"] .availability-time {
    font-size: 17px;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    direction: ltr;
    text-align: right;
    white-space: nowrap;
}
body[data-page="vendor-availability"] .availability-summary-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--muted);
    font-size: 12px;
    white-space: nowrap;
}
body[data-page="vendor-availability"] .availability-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #176b46;
    font-weight: 700;
}
body[data-page="vendor-availability"] .availability-status::before {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #2c9b65;
    content: "";
}
body[data-page="vendor-availability"] .availability-status.is-inactive {
    color: #687078;
}
body[data-page="vendor-availability"] .availability-status.is-inactive::before {
    background: #a6adb3;
}
body[data-page="vendor-availability"] .availability-edit-label {
    color: #755f08;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}
body[data-page="vendor-availability"] .availability-edit-label::after {
    display: inline-block;
    margin-inline-start: 7px;
    content: "⌄";
    transition: transform .15s ease;
}
body[data-page="vendor-availability"] .availability-card[open] .availability-edit-label::after {
    transform: rotate(180deg);
}
body[data-page="vendor-availability"] .availability-editor {
    padding: 14px 15px 15px;
    border-top: 1px solid var(--line);
    background: #fcfcfa;
}
body[data-page="vendor-availability"] .availability-form {
    display: grid;
    grid-template-columns: minmax(120px, 1.1fr) repeat(2, minmax(94px, .8fr)) auto;
    align-items: end;
    gap: 10px;
    min-width: 0;
}
body[data-page="vendor-availability"] .availability-form .form-row {
    display: contents;
}
body[data-page="vendor-availability"] .availability-form .field {
    min-width: 0;
    margin: 0;
}
body[data-page="vendor-availability"] .availability-form .field label {
    white-space: nowrap;
}
body[data-page="vendor-availability"] .availability-form .field input,
body[data-page="vendor-availability"] .availability-form .field select {
    min-height: 42px;
    padding: 8px 10px;
}
body[data-page="vendor-availability"] .availability-active {
    display: flex;
    align-items: center;
    gap: 7px;
    min-height: 42px;
    white-space: nowrap;
    font-size: 13px;
    font-weight: 700;
}
body[data-page="vendor-availability"] .availability-active input {
    width: 18px;
    height: 18px;
    margin: 0;
    accent-color: #b28d00;
}
body[data-page="vendor-availability"] .availability-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 12px;
}
body[data-page="vendor-availability"] .availability-disable-form {
    display: flex;
}
body[data-page="vendor-availability"] .availability-actions .btn {
    min-height: 40px;
}
@media (max-width: 900px) {
    body[data-page="vendor-availability"] .availability-summary {
        grid-template-columns: minmax(100px, 1fr) auto auto;
        gap: 10px;
    }
}
@media (max-width: 650px) {
    body[data-page="vendor-availability"] .availability-create-fields,
    body[data-page="vendor-availability"] .availability-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    body[data-page="vendor-availability"] .availability-active {
        grid-column: 1 / -1;
    }
    body[data-page="vendor-availability"] .availability-summary {
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 7px 10px;
        padding: 11px 12px;
    }
    body[data-page="vendor-availability"] .availability-summary-meta {
        grid-column: 1;
        grid-row: 2;
        flex-wrap: wrap;
    }
    body[data-page="vendor-availability"] .availability-edit-label {
        grid-column: 2;
        grid-row: 1 / 3;
    }
    body[data-page="vendor-availability"] .availability-actions {
        flex-wrap: wrap;
        justify-content: flex-start;
    }
}
</style>
@endpush
@section('content')
    @include('components.navbar')
    <div class="container layout">
        @include('components.sidebars.vendor')
        <main>
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الفني</span>
                    <h1>الجدول الأسبوعي</h1>
                    <p class="muted">الفترات الفعّالة تتكرر أسبوعيًا. أوقات العمل بتوقيت عمّان.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="notice" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <ul class="notice" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <section class="panel">
                <h2>إضافة فترة عمل</h2>
                <form method="POST" action="{{ route('vendor.availability.store') }}" class="availability-create-form">
                    @csrf
                    <div class="form-row availability-create-fields">
                        <div class="field">
                            <label for="new-day">اليوم</label>
                            <select class="input" id="new-day" name="day_of_week" required>
                                @foreach ($days as $day => $label)
                                    <option value="{{ $day }}" @selected((string) old('day_of_week') === (string) $day)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="new-start">بداية الفترة</label>
                            <input class="input" id="new-start" name="start_time" type="time" value="{{ old('start_time') }}" required>
                        </div>
                        <div class="field">
                            <label for="new-end">نهاية الفترة</label>
                            <input class="input" id="new-end" name="end_time" type="time" value="{{ old('end_time') }}" required>
                        </div>
                    </div>
                    <button class="btn btn-yellow" type="submit">إضافة فترة</button>
                </form>
            </section>
             <br>
            <section class="panel">
                <h2>فتراتك الأسبوعية</h2>
                <div class="availability-days">
                @forelse ($availabilities->groupBy('day_of_week') as $day => $periods)
                    <section class="availability-day">
                        <header class="availability-day-heading">
                            <h3>{{ $days[(int) $day] ?? 'يوم غير محدد' }}</h3>
                            <span class="availability-period-count">{{ $periods->count() }} فترة</span>
                        </header>
                        <div class="availability-periods">
                            @foreach ($periods as $availability)
                                <details class="availability-card">
                                    <summary class="availability-summary">
                                        <span class="availability-time">
                                            {{ substr($availability->start_time, 0, 5) }}–{{ substr($availability->end_time, 0, 5) }}
                                        </span>
                                        <span class="availability-summary-meta">
                                            <span class="availability-status {{ $availability->is_active ? '' : 'is-inactive' }}">
                                                {{ $availability->is_active ? 'فعّالة' : 'متوقفة' }}
                                            </span>
                                            <span>{{ $availability->bookings_count }} حجز مرتبط</span>
                                        </span>
                                        <span class="availability-edit-label">إدارة الفترة</span>
                                    </summary>
                                    <div class="availability-editor">
                                        <form id="availability-update-{{ $availability->id }}" method="POST" action="{{ route('vendor.availability.update', $availability) }}" class="availability-form">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-row">
                                                <div class="field">
                                                    <label for="day-{{ $availability->id }}">اليوم</label>
                                                    <select class="input" id="day-{{ $availability->id }}" name="day_of_week" required>
                                                        @foreach ($days as $optionDay => $label)
                                                            <option value="{{ $optionDay }}" @selected((int) $availability->day_of_week === $optionDay)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="field">
                                                    <label for="start-{{ $availability->id }}">من</label>
                                                    <input class="input" id="start-{{ $availability->id }}" name="start_time" type="time" value="{{ substr($availability->start_time, 0, 5) }}" required>
                                                </div>
                                                <div class="field">
                                                    <label for="end-{{ $availability->id }}">إلى</label>
                                                    <input class="input" id="end-{{ $availability->id }}" name="end_time" type="time" value="{{ substr($availability->end_time, 0, 5) }}" required>
                                                </div>
                                            </div>
                                            <label class="availability-active">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" value="1" @checked($availability->is_active)>
                                                الفترة فعّالة
                                            </label>
                                        </form>
                                        <div class="availability-actions">
                                            <button class="btn btn-outline btn-sm" type="submit" form="availability-update-{{ $availability->id }}">حفظ التعديلات</button>
                                            @if ($availability->is_active)
                                                <form method="POST" action="{{ route('vendor.availability.destroy', $availability) }}" class="availability-disable-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline btn-sm" type="submit">تعطيل الفترة</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <p class="muted">ما أضفت فترات عمل بعد. أضف فترة حتى تظهر مواعيدك للعملاء.</p>
                @endforelse
            </section>
        </main>
    </div>
    @include('components.footer')
    @include('components.mobile-nav-vendor')
@endsection
