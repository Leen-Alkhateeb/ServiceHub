@extends('layouts.auth')
@section('title', 'إنشاء حساب')
@section('page_id', 'register')
@section('hide-auth-footer', 'true')
@section('auth-content')
<main class="container registration-page">
    <div class="registration-card">
        <header class="registration-intro">
            <h1>إنشاء حساب</h1>
            <p>عندك حساب؟ <a href="{{ route('login') }}">تسجيل الدخول</a></p>
        </header>
        <div class="registration-body">
            @if ($errors->any())
                <ul class="notice" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" data-registration>
                @csrf
                <div class="registration-fields" data-registration-personal>
                <h2>بيانات الحساب</h2>
                <div class="field">
                    <label for="account-type">نوع الحساب</label>
                    <select id="account-type" name="account_type" required>
                        <option value="customer" @selected(old('account_type', 'customer') === 'customer')>عميل</option>
                        <option value="vendor" @selected(old('account_type') === 'vendor')>فني</option>
                    </select>
                </div>
                <div class="field"><label for="first_name">الاسم الأول</label><input id="first_name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required></div>
                <div class="field"><label for="last_name">اسم العائلة</label><input id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required></div>
                <div class="field"><label for="email">البريد الإلكتروني</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></div>
                <div class="field"><label for="phone">رقم الهاتف</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" required></div>
                <div class="field"><label for="location">المدينة أو المنطقة</label><input id="location" name="location" value="{{ old('location') }}" autocomplete="address-level2" required></div>
                <div class="field">
                    <label for="gender">الجنس</label>
                    <select id="gender" name="gender" required>
                        <option value="">اختر</option>
                        <option value="female" @selected(old('gender') === 'female')>أنثى</option>
                        <option value="male" @selected(old('gender') === 'male')>ذكر</option>
                    </select>
                </div>
                <div class="field">
                    <label for="password">كلمة المرور</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" aria-describedby="password-requirements" data-registration-password required>
                </div>
                <div class="field"><label for="password_confirmation">تأكيد كلمة المرور</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
                <div class="field registration-image">
                    <label for="register-profile-image">الصورة الشخصية (اختياري)</label>
                    <x-file-picker>
                        <input id="register-profile-image" name="profile_image" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="register-profile-image-help">
                    </x-file-picker>
                    <small id="register-profile-image-help" class="muted">صورة JPG أو PNG أو WebP حتى 5 ميغابايت.</small>
                </div>
                <div class="password-requirements" id="password-requirements" hidden>
                        <strong>شروط كلمة المرور</strong>
                        <ul>
                            <li data-password-rule="length"><span aria-hidden="true">○</span> 8 أحرف على الأقل وبحد أقصى 72 بايت</li>
                            <li data-password-rule="uppercase"><span aria-hidden="true">○</span> حرف إنجليزي كبير (A–Z)</li>
                            <li data-password-rule="lowercase"><span aria-hidden="true">○</span> حرف إنجليزي صغير (a–z)</li>
                            <li data-password-rule="number"><span aria-hidden="true">○</span> رقم (0–9)</li>
                            <li data-password-rule="symbol"><span aria-hidden="true">○</span> رمز مثل ! أو @</li>
                        </ul>
                </div>
                </div>
                <section class="registration-fields registration-profession" data-registration-vendor hidden>
                    <h2>بيانات المهنة</h2>
                    <p class="muted">هذا الجزء للتسجيل كفني. تُراجع الإدارة بيانات المهنة وتصريح العمل قبل ظهور ملفك للعملاء.</p>
                    <div class="field"><label for="register-trade">التخصص</label><input id="register-trade" name="work_type" maxlength="255" value="{{ is_scalar(old('work_type')) ? old('work_type') : '' }}" data-vendor-required></div>
                    <div class="field"><label for="register-experience">سنوات الخبرة</label><input id="register-experience" name="experience_years" type="number" min="0" max="80" value="{{ is_scalar(old('experience_years')) ? old('experience_years') : '' }}" data-vendor-required></div>
                    <div class="field"><label for="register-expiry">تاريخ انتهاء تصريح العمل</label><input id="register-expiry" name="permit_expiry_date" type="date" min="{{ now()->toDateString() }}" max="9999-12-31" value="{{ is_scalar(old('permit_expiry_date')) ? old('permit_expiry_date') : '' }}" data-vendor-required></div>
                    <div class="field"><label for="register-permit">تصريح العمل</label><x-file-picker :document="true"><input id="register-permit" name="work_permit" type="file" accept=".pdf,.jpg,.jpeg,.png" data-vendor-required></x-file-picker><small class="muted">PDF أو JPG أو PNG حتى 5 ميغابايت.</small></div>
                    <div class="field registration-bio"><label for="register-bio">نبذة عن خبرتك</label><textarea id="register-bio" name="bio" rows="3" maxlength="5000" data-vendor-required>{{ is_string(old('bio')) ? old('bio') : '' }}</textarea></div>
                </section>
                <div class="registration-footer">
                    <p class="muted" data-registration-progress aria-live="polite"></p>
                    <div class="moderation-actions">
                        <button class="btn btn-outline" type="button" data-registration-back hidden>رجوع</button>
                        <button class="btn btn-yellow" type="button" data-registration-next hidden>التالي: بيانات المهنة</button>
                        <button class="btn btn-yellow" type="submit" data-registration-submit>إنشاء الحساب</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
