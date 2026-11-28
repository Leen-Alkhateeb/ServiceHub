@extends('layouts.app')
@section('title', 'طلبات صلّحها الآن')
@include('pages.instant.styles')
@section('content')
@include('components.navbar')
<main class="container page-head stack instant-page">
    @include('pages.instant.hero', ['heading' => 'مساحة عمل الفني', 'description' => 'استقبل الطلبات القريبة وأرسل عروضك.', 'badge' => $profile->instant_available ? 'الاستقبال مفعّل' : 'الاستقبال متوقف'])
    @include('pages.instant.feedback')
    <div class="instant-columns">
    <div class="instant-column" data-instant-live>
        <div class="instant-stats">
            <div class="panel"><a class="instant-nearby-link" href="#nearby-requests">طلبات قريبة <span aria-hidden="true">↓</span></a><strong>{{ $matches->count() }}</strong></div>
            <div class="panel"><span class="muted">عروض معلّقة</span><strong>{{ $offers->total() }}</strong></div>
            <div class="panel"><span class="muted">حجوزات فورية</span><strong>{{ $bookings->total() }}</strong></div>
        </div>
        @include('pages.instant.vendor-work')
    </div>
    <section class="panel stack">
        <h2>التوفّر الفوري</h2>
        <form method="POST" action="{{ route('instant.availability') }}" data-instant-location @if($profile->instant_available) data-instant-refresh-location="{{ config('instant.location_update_seconds') }}" @endif>
            @csrf
            <input type="hidden" name="instant_available" value="1">
            @include('pages.instant.location')
            <div class="booking-tabs">
                <button type="submit" class="btn btn-yellow">{{ $profile->instant_available ? 'تحديث الموقع الآن' : 'تفعيل استقبال الطلبات' }}</button>
                @if($profile->instant_available)
                    <button type="submit" form="stop-instant-availability" class="btn btn-outline">إيقاف التوفّر ومشاركة الموقع</button>
                @endif
            </div>
        </form>
        @if($profile->instant_available)
            <form id="stop-instant-availability" method="POST" action="{{ route('instant.availability') }}">
                @csrf<input type="hidden" name="instant_available" value="0">
            </form>
        @endif
        
    </section>
    </div>
    <p class="muted" data-instant-live-status role="status"></p>
    <div id="nearby-requests" data-instant-live>
        @include('pages.instant.vendor-requests')
    </div>
</main>
<script type="module" src="{{ asset('assets/instant.js') }}?v={{ filemtime(public_path('assets/instant.js')) }}"></script>
@endsection
