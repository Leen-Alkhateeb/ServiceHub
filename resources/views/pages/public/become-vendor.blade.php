@extends('layouts.app')
@section('title', 'انضم كفني')
@section('page_id', 'become-vendor')
@section('content')
@include('components.navbar')

<main class="container vendor-join">
    <header class="vendor-join-heading">
        <h1>انضم كفني</h1>
        <p class="muted">قدّم طلبك واعرض خدمات الصيانة بعد موافقة الإدارة.</p>
    </header>

    <div class="vendor-join-layout">
        <section class="panel vendor-join-panel">
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
                <p><span class="pill {{ match ($application->application_status) { 'approved' => 'green', 'rejected' => 'red', default => 'yellow' } }}">{{ $status }}</span></p>
                @if ($application->applied_at)
                    <p class="muted">تاريخ التقديم <time datetime="{{ substr($application->applied_at, 0, 10) }}">{{ substr($application->applied_at, 0, 10) }}</time></p>
                @endif
                @if ($application->application_status === 'rejected' && $application->rejection_reason)
                    <div class="notice notice-error">{{ $application->rejection_reason }}</div>
                @endif
            @elseif ($canApply)
                <h2>طلب الانضمام</h2>
                <button type="button" class="btn btn-yellow" data-join-open hidden>ابدأ طلب الانضمام</button>
                <dialog class="hub-dialog join-wizard" data-join-dialog aria-labelledby="join-wizard-title">
                    <div class="dialog-head"><h2 id="join-wizard-title">طلب الانضمام</h2><x-modal-close data-join-close /></div>
                    <div data-join-progress hidden><span data-join-step aria-live="polite"></span> <progress max="4" value="1" aria-label="تقدّم الطلب"></progress></div>
                </dialog>
                <form class="vendor-join-form" method="POST" action="{{ route('vendor.apply') }}" enctype="multipart/form-data" data-join-form @if($errors->any()) data-join-errors @endif>
                    @csrf
                    <fieldset class="vendor-join-wide" data-join-panel hidden>
                        <legend tabindex="-1">بيانات حسابك</legend>
                        <p>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                        <p><bdi>{{ auth()->user()->email }}</bdi></p>
                        <p class="muted">رح نستخدم البيانات الموجودة بحسابك.</p>
                    </fieldset>
                    <fieldset class="vendor-join-wide" data-join-panel>
                    <legend tabindex="-1">التخصص والخبرة</legend>
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
                    <div class="field vendor-join-wide">
                        <label for="bio">نبذة عن خبرتك</label>
                        <textarea id="bio" name="bio" rows="3" maxlength="5000" required>{{ is_string(old('bio')) ? old('bio') : '' }}</textarea>
                        @error('bio') <p class="notice" role="alert">{{ $message }}</p> @enderror
                    </div>
                    </fieldset>
                    <fieldset class="vendor-join-wide" data-join-panel>
                    <legend tabindex="-1">تصريح العمل</legend>
                    <div class="field">
                        <label for="permit_expiry_date">تاريخ انتهاء تصريح العمل</label>
                        <input id="permit_expiry_date" name="permit_expiry_date" type="date" value="{{ is_string(old('permit_expiry_date')) ? old('permit_expiry_date') : '' }}" min="{{ now()->toDateString() }}" max="9999-12-31" required>
                        @error('permit_expiry_date') <p class="notice" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="work_permit">تصريح العمل</label>
                        <x-file-picker :document="true"><input id="work_permit" name="work_permit" type="file" accept=".pdf,.jpg,.jpeg,.png" aria-describedby="permit-formats" required></x-file-picker>
                        <small id="permit-formats" class="muted">PDF أو JPG أو PNG، حتى 5 ميغابايت.</small>
                        @error('work_permit') <p class="notice" role="alert">{{ $message }}</p> @enderror
                    </div>
                    </fieldset>
                    <fieldset class="vendor-join-wide" data-join-panel hidden>
                        <legend tabindex="-1">راجع طلبك</legend>
                        <dl class="join-summary" data-join-summary></dl>
                        <p>تصريح العمل متاح للإدارة لمراجعة طلبك.</p>
                    </fieldset>
                    <div class="vendor-join-wide vendor-join-actions">
                        <button class="btn btn-outline" type="button" data-join-back hidden>السابق</button>
                        <button class="btn btn-yellow" type="button" data-join-next hidden>التالي</button>
                        <button class="btn btn-yellow" type="submit" data-join-submit>إرسال الطلب</button>
                    </div>
                    <div class="vendor-join-wide notice" data-join-discard hidden>
                        <p>في تفاصيل لسه ما أرسلتها. بدك ترجع للطلب أو تمسحها؟</p>
                        <button type="button" class="btn btn-outline" data-join-keep>متابعة الطلب</button>
                        <button type="button" class="btn btn-outline" data-join-cancel>مسح التفاصيل وإغلاق</button>
                    </div>
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

        <aside class="panel vendor-join-panel vendor-join-steps">
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
