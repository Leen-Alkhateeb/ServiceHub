@extends('layouts.app')
@section('title', 'تواصل معنا')
@section('page_id', 'contact')
@section('content')
@include('components.navbar')
<main class="container page-head" style="max-width:620px">
    <section class="panel">
        <h1>تواصل معنا</h1>
        @if (session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
        @auth
            <form method="POST" action="{{ route('contact.store') }}">
                @csrf
                <div class="field"><label for="contact-subject">الموضوع</label><input id="contact-subject" name="subject" maxlength="255" value="{{ is_string(old('subject')) ? old('subject') : '' }}" required>@error('subject')<p class="notice" role="alert">{{ $message }}</p>@enderror</div>
                <div class="field"><label for="contact-message">الرسالة</label><textarea id="contact-message" name="message" rows="5" maxlength="5000" required>{{ is_string(old('message')) ? old('message') : '' }}</textarea>@error('message')<p class="notice" role="alert">{{ $message }}</p>@enderror</div>
                <button class="btn btn-yellow" type="submit">إرسال الرسالة</button>
            </form>
        @else
            <p>سجّل الدخول أولًا حتى تقدر تبعث رسالة للإدارة.</p>
            <a class="btn btn-yellow" href="{{ route('login') }}">تسجيل الدخول</a>
        @endauth
    </section>
</main>
@include('components.footer')
@include('components.mobile-nav-public')
@endsection
