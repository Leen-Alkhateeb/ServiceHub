@extends('layouts.app')
@section('title', 'عروض الخدمة الفورية')
@include('pages.instant.styles')
@section('content')
@include('components.navbar')
<main class="container page-head stack instant-page">
    <a href="{{ route('instant.index') }}">رجوع لطلباتي</a>
    @include('pages.instant.hero', ['heading' => 'عروض الطلب #'.$instantRequest->id, 'description' => 'قارن السعر ووقت الوصول قبل اختيار الفني.'])
    @include('pages.instant.steps', ['currentStep' => 1])
    @include('pages.instant.feedback')
    <div class="instant-columns" data-instant-live>
    <section class="panel stack">
        <h2>عروض الفنيين</h2>
        @if($booking)
            <p><a class="btn btn-yellow" href="{{ route('instant.booking', $booking) }}">فتح الحجز الفوري</a></p>
        @elseif($instantRequest->status === 'cancelled')
            <p>ألغيت الطلب.</p>
        @elseif($instantRequest->status === 'expired')
            <p>انتهت مهلة البحث. تقدر تبدأ طلب جديد.</p>
        @else
            <p>البحث شغّال لحد ما تختار عرض أو توقفه.</p>
            <div>
                @forelse($offers as $offer)
                    @php($vendor = $offer->vendorService->vendor)
                    <article class="instant-row">
                        <div class="instant-person">
                            <x-account-picture :account="$vendor" aria-hidden="true" />
                            <div>
                                <strong>{{ $vendor->first_name }} {{ $vendor->last_name }}</strong>
                                <div class="muted">{{ $vendor->reviews_received_avg_rating === null ? 'لا تقييمات بعد' : number_format($vendor->reviews_received_avg_rating, 1).' / 5' }}</div>
                                <div class="muted">الوصول {{ $offer->arrival_minutes }} دقيقة · مدة العمل {{ $offer->duration_minutes }} دقيقة</div>
                            </div>
                        </div>
                        <form class="instant-person" method="POST" action="{{ route('instant.accept', [$instantRequest, $offer]) }}">
                            @csrf<strong class="instant-price">{{ $offer->offered_price }} د.أ</strong><button class="btn btn-yellow">اختيار العرض</button>
                        </form>
                    </article>
                @empty<p class="muted">لسه ما وصلك عرض.</p>@endforelse
            </div>
        @endif
    </section>
    <section class="panel">
        <h2>طلبك الحالي</h2>
        <p class="instant-row"><span>الطلب</span><strong>#{{ $instantRequest->id }}</strong></p>
        <p>{{ $instantRequest->description }}</p>
        @if($instantRequest->status === 'searching')
            <form method="POST" action="{{ route('instant.cancel-search', $instantRequest) }}">@csrf<button class="btn btn-outline">إلغاء البحث</button></form>
        @endif
    </section>
    </div>
    <p class="muted" data-instant-live-status role="status"></p>
</main>
<script type="module" src="{{ asset('assets/instant.js') }}?v={{ filemtime(public_path('assets/instant.js')) }}"></script>
@endsection
