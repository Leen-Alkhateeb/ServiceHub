@extends('layouts.app')
@section('title', 'طلبات صلّحها الآن')
@section('content')
@include('components.navbar')
<main class="container page-head stack">
    <h1>طلبات صلّحها الآن</h1>
    @include('pages.instant.feedback')
    <section class="panel stack">
        <h2>التوفّر الفوري</h2>
        <form method="POST" action="{{ route('instant.availability') }}" data-instant-location @if($profile->instant_available) data-instant-refresh-location="{{ config('instant.location_update_seconds') }}" @endif>
            @csrf
            <input type="hidden" name="instant_available" value="1">
            @include('pages.instant.location')
            <button class="btn btn-yellow">{{ $profile->instant_available ? 'تحديث الموقع الآن' : 'تفعيل استقبال الطلبات' }}</button>
        </form>
        @if($profile->instant_available)
            <form method="POST" action="{{ route('instant.availability') }}">@csrf<input type="hidden" name="instant_available" value="0"><button class="btn btn-outline">إيقاف التوفّر ومشاركة الموقع</button></form>
        @endif
        <h3>خدماتك المؤهلة</h3>
        @forelse($services as $service)
            <form method="POST" action="{{ route('instant.vendor-service', $service) }}" class="booking-tabs">
                @csrf<input type="hidden" name="instant_enabled" value="{{ $service->instant_enabled ? '0' : '1' }}">
                <span>{{ $service->service->name }}</span><button class="btn btn-outline btn-sm">{{ $service->instant_enabled ? 'إيقاف الفوري لهذه الخدمة' : 'تفعيل الفوري لهذه الخدمة' }}</button>
            </form>
        @empty<p class="muted">ما في خدمات مؤهلة حاليًا.</p>@endforelse
    </section>
    <div class="stack" data-instant-live>
    <section class="panel stack">
        <div class="row between"><h2>طلبات قريبة</h2><a class="btn btn-outline btn-sm" href="{{ route('instant.vendor') }}">تحديث الطلبات</a></div>
        @forelse($matches as $candidate)
            @php($offer = $enabled[$candidate->service_id])
            <article class="panel stack">
                <h3>{{ $offer->service->name }} · #{{ $candidate->id }}</h3>
                <p>{{ $candidate->description }}</p>
                @foreach($images->get($candidate->id, []) as $image)
                    <a href="{{ route('instant.image', [$candidate, $image]) }}">تحميل صورة المشكلة {{ $loop->iteration }}</a>
                @endforeach
                <form method="POST" action="{{ route('instant.offer', $candidate) }}" class="stack">
                    @csrf<input type="hidden" name="vendor_service_id" value="{{ $offer->id }}">
                    <div class="field"><label for="price-{{ $candidate->id }}">سعرك بالدينار الأردني</label><input id="price-{{ $candidate->id }}" name="offered_price" type="number" min="0.001" max="9999999.999" step="0.001" required><small>حصتك العادية 85٪ من السعر المدفوع.</small></div>
                    <div class="field"><label for="arrival-{{ $candidate->id }}">الوصول بالدقائق</label><input id="arrival-{{ $candidate->id }}" name="arrival_minutes" type="number" min="1" max="1439" required></div>
                    <div class="field"><label for="duration-{{ $candidate->id }}">مدة العمل بالدقائق</label><input id="duration-{{ $candidate->id }}" name="duration_minutes" type="number" min="5" max="1440" required></div>
                    <div><button class="btn btn-yellow">إرسال عرض</button></div>
                </form>
            </article>
        @empty<p class="muted">ما في طلبات مطابقة لموقعك وتوفّرك حاليًا.</p>@endforelse
    </section>
    <section class="panel">
        <h2>عروضك المعلقة</h2>
        @forelse($offers as $offer)
            <form class="booking-tabs" method="POST" action="{{ route('instant.withdraw', $offer) }}">@csrf <span>طلب #{{ $offer->instant_request_id }} · {{ $offer->offered_price }} د.أ</span><button class="btn btn-outline btn-sm">سحب العرض</button></form>
        @empty<p>ما في عروض معلقة.</p>@endforelse
        {{ $offers->links('components.pager') }}
    </section>
    @include('pages.instant.history')
    </div>
    <p class="muted" data-instant-live-status role="status"></p>
</main>
<script type="module" src="{{ asset('assets/instant.js') }}?v={{ filemtime(public_path('assets/instant.js')) }}"></script>
@endsection
