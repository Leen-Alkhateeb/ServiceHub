@extends('layouts.auth')
@section('title', 'إنشاء حساب | ServiceHub')
@section('page_id', 'register')
@section('auth-content')
<main>
    <div class="container page-head" style="max-width:620px">
        <div class="panel">
            <h1>إنشاء حساب</h1>
            @if ($errors->any())
                <ul class="notice" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            <form method="POST" action="{{ route('register') }}">
                @csrf
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
                <div class="field"><label for="password">كلمة المرور</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required></div>
                <div class="field"><label for="password_confirmation">تأكيد كلمة المرور</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>
                <button class="btn btn-yellow">إنشاء الحساب</button>
            </form>
            <p><a href="{{ route('login') }}">تسجيل الدخول</a></p>
        </div>
    </div>
</main>
@endsection
