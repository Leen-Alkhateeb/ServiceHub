@extends('layouts.auth')
@section('title', 'استعادة كلمة المرور')
@section('page_id', 'password-request')
@section('auth-content')
<main class="container password-page">
    <section class="panel">
        <h1>استعادة كلمة المرور</h1>
        <p class="muted">أدخل البريد الإلكتروني لحسابك لإرسال رابط إعادة تعيين كلمة المرور.</p>
        @if (session('status'))
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="field">
                <label for="email">البريد الإلكتروني</label>
                <input id="email" type="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}" required autocomplete="email">
                @error('email')<p class="notice" role="alert">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-yellow">إرسال رابط الاستعادة</button>
        </form>
        <p><a href="{{ route('login') }}">العودة لتسجيل الدخول</a></p>
    </section>
</main>
@endsection
