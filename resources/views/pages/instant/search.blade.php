@extends('layouts.app')
@section('title', 'عروض الخدمة الفورية')
@section('content')
@include('components.navbar')
<main class="container page-head stack">
    <a href="{{ route('instant.index') }}">رجوع لطلباتي</a>
    <h1>عروض الطلب #{{ $instantRequest->id }}</h1>
    @include('pages.instant.feedback')
    <section class="panel stack" data-instant-live>
        @if($booking)
            <p><a class="btn btn-yellow" href="{{ route('instant.booking', $booking) }}">فتح الحجز الفوري</a></p>
        @elseif($instantRequest->status === 'cancelled')
            <p>ألغيت الطلب.</p>
        @elseif(!$instantRequest->expires_at->isFuture())
            <p>انتهت مهلة البحث. تقدر تبدأ طلب جديد.</p>
        @else
            <p>البحث لحد {{ $instantRequest->expires_at->copy()->timezone(\App\Models\Booking::SCHEDULE_TIMEZONE)->format('H:i') }} بتوقيت عمّان.</p>
            <div class="grid">
                @forelse($offers as $offer)
                    @php($vendor = $offer->vendorService->vendor)
                    <article class="panel stack">
                        <x-account-picture :account="$vendor" aria-hidden="true" />
                        <strong>{{ $vendor->first_name }} {{ $vendor->last_name }}</strong>
                        <p>طلب الانضمام معتمد · {{ $vendor->reviews_received_avg_rating === null ? 'لا تقييمات بعد' : number_format($vendor->reviews_received_avg_rating, 1).' / 5' }}</p>
                        <p>{{ $offer->offered_price }} د.أ · الوصول {{ $offer->arrival_minutes }} دقيقة · مدة العمل {{ $offer->duration_minutes }} دقيقة</p>
                        <form method="POST" action="{{ route('instant.accept', [$instantRequest, $offer]) }}">@csrf<button class="btn btn-yellow">اختيار العرض</button></form>
                    </article>
                @empty<p class="muted">لسه ما وصلك عرض.</p>@endforelse
            </div>
            <form method="POST" action="{{ route('instant.cancel-search', $instantRequest) }}">@csrf<button class="btn btn-outline">إلغاء البحث</button></form>
        @endif
    </section>
    <p class="muted" data-instant-live-status role="status"></p>
</main>
<script type="module" src="{{ asset('assets/instant.js') }}?v={{ filemtime(public_path('assets/instant.js')) }}"></script>
@endsection
