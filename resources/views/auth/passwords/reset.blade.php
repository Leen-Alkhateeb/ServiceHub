@extends('layouts.auth')
@section('title', 'تعيين كلمة مرور جديدة')
@section('page_id', 'password-reset')
@section('auth-content')
<main class="container password-page">
    <section class="panel">
        <h1>تعيين كلمة مرور جديدة</h1>
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field">
                <label for="email">البريد الإلكتروني</label>
                <input id="email" type="email" name="email" value="{{ is_string(old('email', $email)) ? old('email', $email) : '' }}" required autocomplete="email">
                @error('email')<p class="notice" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="password">كلمة المرور الجديدة</label>
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
                @error('password')<p class="notice" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="password-confirm">تأكيد كلمة المرور الجديدة</label>
                <input id="password-confirm" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </div>
            @error('token')<p class="notice" role="alert">{{ $message }}</p>@enderror
            <button type="submit" class="btn btn-yellow">حفظ كلمة المرور</button>
        </form>
    </section>
</main>
@endsection
