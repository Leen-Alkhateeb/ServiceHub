@extends('layouts.auth')
@section('title', 'تسجيل الدخول')
@section('page_id', 'login')
@section('auth-content')
<main>
    <div class="container page-head" style="max-width:620px">
        <div class="panel">
            <h1>تسجيل الدخول</h1>
            @if ($errors->any())
                <ul class="notice" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="field"><label for="email">البريد الإلكتروني</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></div>
                <div class="field"><label for="password">كلمة المرور</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
                <label><input type="checkbox" name="remember" value="1" @checked(old('remember'))> تذكرني</label>
                <button class="btn btn-yellow">دخول</button>
            </form>
            <p><a href="{{ route('register') }}">إنشاء حساب</a> · <a href="{{ route('password.request') }}">نسيت كلمة المرور؟</a></p>
        </div>
    </div>
</main>
@endsection
