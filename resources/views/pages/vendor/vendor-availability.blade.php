@extends('layouts.app')
@section('title', 'الجدول الأسبوعي | ServiceHub')
@section('page_id', 'vendor-availability')
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
                <form method="POST" action="{{ route('vendor.availability.store') }}">
                    @csrf
                    <div class="form-row">
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

            <section class="panel">
                <h2>فتراتك الأسبوعية</h2>
                @forelse ($availabilities as $availability)
                    <article class="item-row availability-row">
                        <form method="POST" action="{{ route('vendor.availability.update', $availability) }}" class="availability-form">
                            @csrf
                            @method('PATCH')
                            <div class="form-row">
                                <div class="field">
                                    <label for="day-{{ $availability->id }}">اليوم</label>
                                    <select class="input" id="day-{{ $availability->id }}" name="day_of_week" required>
                                        @foreach ($days as $day => $label)
                                            <option value="{{ $day }}" @selected((int) $availability->day_of_week === $day)>{{ $label }}</option>
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
                            <div class="row">
                                <button class="btn btn-outline btn-sm" type="submit">حفظ التعديلات</button>
                                <span class="muted small">{{ $availability->bookings_count }} حجز مرتبط</span>
                            </div>
                        </form>
                        @if ($availability->is_active)
                            <form method="POST" action="{{ route('vendor.availability.destroy', $availability) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline btn-sm" type="submit">تعطيل الفترة</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="muted">ما أضفت فترات عمل بعد. أضف فترة حتى تظهر مواعيدك للعملاء.</p>
                @endforelse
            </section>
        </main>
    </div>
    @include('components.footer')
    @include('components.mobile-nav-vendor')
@endsection
