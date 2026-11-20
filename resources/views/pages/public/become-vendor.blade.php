@extends('layouts.app')
@section('title', 'انضم كفني | ServiceHub')
@section('page_id', 'become-vendor')
@section('content')
@include('components.navbar')

<main class="container page-head">
    <div class="section-head">
        <div>
            <h1>انضم كفني</h1>
            <p class="muted">قدّم طلبك واعرض خدمات الصيانة بعد موافقة الإدارة.</p>
        </div>
    </div>

    <div class="split">
        <section class="panel">
            @if ($application)
                @php
                    $status = match ($application->application_status) {
                        'pending' => 'قيد المراجعة',
                        'approved' => 'تمت الموافقة',
                        'rejected' => 'لم تتم الموافقة',
                        default => $application->application_status,
                    };
                @endphp
                <h2>حالة طلبك</h2>
                <p><span class="pill yellow">{{ $status }}</span></p>
                @if ($application->applied_at)
                    <p class="muted">تاريخ التقديم <time datetime="{{ substr($application->applied_at, 0, 10) }}">{{ substr($application->applied_at, 0, 10) }}</time></p>
                @endif
                @if ($application->application_status === 'rejected' && $application->rejection_reason)
                    <div class="notice">{{ $application->rejection_reason }}</div>
                @endif
            @elseif ($canApply)
                <h2>طلب الانضمام</h2>
                <form method="POST" action="{{ route('vendor.apply') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="field">
                        <label for="work_type">التخصص</label>
                        <input id="work_type" name="work_type" value="{{ is_string(old('work_type')) ? old('work_type') : '' }}" placeholder="مثال: صيانة المكيفات" maxlength="255" required>
                        @error('work_type') <p class="notice" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="experience_years">سنوات الخبرة</label>
                        <input id="experience_years" name="experience_years" type="number" value="{{ is_scalar(old('experience_years')) ? old('experience_years') : '' }}" min="0" max="80" required>
                        @error('experience_years') <p class="notice" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="bio">نبذة عن خبرتك</label>
                        <textarea id="bio" name="bio" rows="4" maxlength="5000" required>{{ is_string(old('bio')) ? old('bio') : '' }}</textarea>
                        @error('bio') <p class="notice" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="permit_expiry_date">تاريخ انتهاء تصريح العمل</label>
                        <input id="permit_expiry_date" name="permit_expiry_date" type="date" value="{{ is_string(old('permit_expiry_date')) ? old('permit_expiry_date') : '' }}" min="{{ now()->toDateString() }}" max="9999-12-31" required>
                        @error('permit_expiry_date') <p class="notice" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="work_permit">تصريح العمل</label>
                        <input id="work_permit" name="work_permit" type="file" accept=".pdf,.jpg,.jpeg,.png" aria-describedby="permit-formats" required>
                        <small id="permit-formats" class="muted">PDF أو JPG أو PNG، حتى 5 ميغابايت.</small>
                        @error('work_permit') <p class="notice" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <button class="btn btn-yellow" type="submit">إرسال الطلب</button>
                </form>
            @elseif (auth()->guest())
                <h2>ابدأ طلب الانضمام</h2>
                <p>سجّل الدخول أو أنشئ حسابًا لتقديم طلبك ومتابعته.</p>
                <a class="btn btn-yellow" href="{{ route('vendor.apply', ['start' => 1]) }}">متابعة إلى تسجيل الدخول</a>
            @else
                <h2>طلب الانضمام</h2>
                <p>يمكن تقديم الطلب من حساب عميل نشط.</p>
            @endif
        </section>

        <aside class="panel">
            <h2>خطوات الانضمام</h2>
            <ol>
                <li>اذكر تخصصك وخبرتك وارفع تصريح عمل ساري المفعول.</li>
                <li>تابع حالة الطلب من هذه الصفحة أثناء مراجعته.</li>
                <li>بعد الموافقة، يمكنك عرض خدماتك وأسعارك ومواعيدك.</li>
            </ol>
        </aside>
    </div>
</main>

@include('components.footer')
@include('components.mobile-nav-public')
@endsection
