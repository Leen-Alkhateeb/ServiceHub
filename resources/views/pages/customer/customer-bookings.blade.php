@extends('layouts.app')
@section('title', 'حجوزاتي')

@section('content')

@include('pages.partials.market-assets')

@php
    $statusMap = [
        'pending'   => ['بانتظار موافقة الفني', 'warning'],
        'approved'  => ['بانتظار الدفع',        'info'],
        'awaiting_payment' => ['بانتظار الدفع', 'info'],
        'confirmed' => ['مؤكّد',                'info'],
        'paid' => ['مدفوع', 'info'],
        'in_progress' => ['قيد التنفيذ', 'info'],
        'objection_pending' => ['اعتراض قيد المراجعة', 'warning'],
        'completed' => ['مكتمل',                'success'],
        'rejected'  => ['مرفوض',                'danger'],
        'cancelled' => ['ملغي',                 'muted'],
        'expired'   => ['منتهي — لم يُدفع',     'muted'],
        'closed'    => ['مغلق',                 'muted'],
        'refunded'  => ['مسترد',                'muted'],
    ];
@endphp

@include('components.navbar')
<div class="profile-layout">
    @include('pages.customer.menu')

    <section class="stack">
        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row between">
            <h2>حجوزاتي</h2>
            <a href="{{ route('market.index') }}" class="btn btn-primary btn-sm">احجز خدمة</a>
        </div>

        <nav class="booking-tabs" aria-label="تصنيف الحجوزات">
            @foreach ($tabs as $key => $label)
                <a class="btn btn-sm {{ $tab === $key ? 'btn-yellow' : 'btn-outline' }}" @if($tab === $key) aria-current="page" @endif href="{{ route('customer.bookings', ['tab' => $key, 'q' => $filters['q'] ?? null]) }}">{{ $label }}</a>
            @endforeach
        </nav>
        <form class="booking-tabs" method="GET" action="{{ route('customer.bookings') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label class="sr-only" for="customer-booking-search">ابحث باسم الخدمة أو رقم الحجز</label>
            <input class="input" id="customer-booking-search" name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="اسم الخدمة أو رقم الحجز">
            <button class="btn btn-outline btn-sm">بحث</button>
        </form>
        <div class="customer-booking-grid">
            @forelse ($bookings as $booking)
                @php
                    [$label, $tone] = $statusMap[$booking->status] ?? [$booking->status, 'muted'];
                    $offer = $booking->vendorService;
                    $vendor = $offer?->vendor;
                    $image = $offer && in_array($offer->id, $visibleOffers) ? $offer->imageUrl() : null;
                    $canPay = in_array($booking->status, ['approved', 'awaiting_payment'], true) && !$booking->payment && $booking->scheduledStart()?->isFuture() && $booking->payment_deadline?->isFuture();
                @endphp
                <article class="panel customer-booking-card">
                    @if($image)<img class="booking-cover" src="{{ $image }}" loading="lazy" alt="">@endif
                    <div class="booking-card-title"><h3>{{ $booking->service_name }}</h3><span class="badge badge-{{ $tone }}">{{ $booking->refundRequest?->status === 'pending' ? 'اعتراض قيد المراجعة' : $label }}</span></div>
                    @if($vendor)<p class="booking-card-vendor"><x-account-picture :account="$vendor" aria-hidden="true" /> {{ $vendor->first_name }} {{ $vendor->last_name }}</p>@endif
                    <p>{{ $booking->booking_date->format('Y-m-d') }} · <bdi dir="ltr">{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</bdi></p>
                    <strong>{{ number_format($booking->original_price, 2) }} د.أ</strong>
                    @if($canPay)<p class="muted">مهلة الدفع: {{ $booking->payment_deadline->copy()->timezone(\App\Models\Booking::SCHEDULE_TIMEZONE)->format('Y-m-d H:i') }} بتوقيت عمّان</p>@endif
                    <div class="booking-tabs">
                        @if($canPay)<a class="btn btn-yellow btn-sm" href="{{ route('payment.checkout', $booking) }}">إتمام الدفع</a>@endif
                        <a class="btn btn-outline btn-sm" href="{{ route('customer.bookings.show', $booking) }}">{{ $booking->vendor_completed_at && !$booking->customer_completed_at ? 'مراجعة إنجاز الخدمة' : 'تفاصيل الحجز' }}</a>
                    </div>
                </article>
            @empty
                <p class="panel">ما في حجوزات مطابقة. جرّب تغيّر التصنيف أو البحث.</p>
            @endforelse
        </div>
        {{ $bookings->onEachSide(1)->links('components.pager') }}
    </section>
</div>
@endsection
