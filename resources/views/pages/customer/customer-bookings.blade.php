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

<div class="profile-layout">
    @include('pages.customer.menu')

    <section class="stack">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row between">
            <h2>حجوزاتي</h2>
            <a href="{{ route('market.index') }}" class="btn btn-primary btn-sm">احجز خدمة</a>
        </div>

        @foreach (['الحالية' => $current, 'السابقة' => $past] as $title => $list)
            <h3>{{ $title }}</h3>

            @forelse ($list as $booking)
                @php [$label, $tone] = $statusMap[$booking->status]; @endphp

                <div class="card booking">
                    <div>
                        <div class="row">
                            <strong>{{ $booking->service_name }}</strong>
                            <span class="badge badge-{{ $tone }}">{{ $label }}</span>
                        </div>
                        <p class="muted">{{ $booking->vendorService->vendor->first_name }} {{ $booking->vendorService->vendor->last_name }} · #{{ $booking->id }}</p>
                        <p>
                            {{ $booking->booking_date->format('Y-m-d') }} ·
                            <span dir="ltr">{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</span> ·
                            <strong>{{ number_format($booking->original_price, 2) }} د.أ</strong>
                        </p>
                        @if (in_array($booking->status, ['approved', 'awaiting_payment'], true))
                            <p class="note">ادفع قبل {{ $booking->payment_deadline?->format('Y-m-d H:i') }}</p>
                        @endif
                    </div>

                    <div class="row">
                        @if (in_array($booking->status, ['approved', 'awaiting_payment'], true))
                            <a href="{{ route('payment.checkout', $booking) }}" class="btn btn-primary btn-sm">إتمام الدفع</a>
                        @endif

                        @if (in_array($booking->status, ['pending', 'approved', 'awaiting_payment'], true) && ! $booking->payment)
                            <form method="POST" action="{{ route('booking.cancel', $booking) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm">إلغاء الحجز</button>
                            </form>
                        @endif

                        @if ($booking->payment)
                            <a href="{{ route('payment.invoice', $booking) }}" class="btn btn-outline btn-sm">الفاتورة</a>
                        @endif
                    </div>
                </div>

            @empty
                <div class="card empty"><p class="muted">ما في حجوزات {{ $title }}.</p></div>
            @endforelse
        @endforeach
    </section>
</div>
@endsection

