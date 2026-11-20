@extends('layouts.app')
@section('title', 'تفاصيل الحجز | ServiceHub')
@section('page_id', 'admin-booking-details')

@section('content')
    @include('components.navbar')
    <div class="container layout">
        @include('components.sidebars.admin')
        <main class="dashboard-content">
            <div class="dash-title">
                <h1>الحجز #{{ $booking->id }}</h1>
                <a class="btn btn-outline" href="{{ route('admin.bookings.index', $booking->trashed() ? ['archived' => 1] : []) }}">رجوع للحجوزات</a>
            </div>
            <div class="panel">
                @if ($booking->trashed())
                    <p class="notice" role="status">حجز مؤرشف منذ {{ $booking->deleted_at->format('Y-m-d H:i') }}</p>
                @endif
                <dl class="booking-details">
                    <dt>الخدمة</dt>
                    <dd>{{ $booking->service_name }}</dd>
                    <dt>الوصف</dt>
                    <dd>{{ $booking->service_description }}</dd>
                    <dt>العميل</dt>
                    <dd>{{ trim($booking->customer?->first_name.' '.$booking->customer?->last_name) ?: '—' }}</dd>
                    <dt>المزوّد</dt>
                    <dd>{{ trim($booking->vendorService?->vendor?->first_name.' '.$booking->vendorService?->vendor?->last_name) ?: '—' }}</dd>
                    <dt>الموعد</dt>
                    <dd>{{ $booking->booking_date->format('Y-m-d') }} · {{ substr($booking->start_time, 0, 5) }} إلى {{ substr($booking->end_time, 0, 5) }}</dd>
                    <dt>حالة الحجز</dt>
                    <dd><span class="pill">{{ $statusLabel }}</span></dd>
                    <dt>سعر الحجز الأصلي</dt>
                    <dd>{{ number_format($booking->original_price, 2) }} د.أ</dd>
                    <dt>الدفعة المرتبطة</dt>
                    <dd>
                        @if ($booking->payment)
                            #{{ $booking->payment->id }} · {{ number_format($booking->payment->amount, 2) }} د.أ
                            · {{ $booking->payment->status === 'paid' ? 'مدفوعة' : $booking->payment->status }}
                        @else
                            لا توجد دفعة مسجّلة.
                        @endif
                    </dd>
                </dl>

                @unless ($booking->trashed())
                    <x-archive-button :action="route('admin.bookings.destroy', $booking)"
                        :message="'هل تريد حذف الحجز #'.$booking->id.'؟ سيبقى الحجز في الأرشيف وتُحفظ بيانات الدفع المرتبطة به.'"
                        label="حذف الحجز" />
                @endunless
            </div>
        </main>
    </div>
    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
