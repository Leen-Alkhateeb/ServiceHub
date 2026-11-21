@extends('layouts.app')

@section('title', 'تفاصيل الحجز | ServiceHub')
@section('page_id', 'admin-booking-details')

@push('styles')
    <link rel="stylesheet" href="/assets/admin-management.css">
@endpush

@section('content')
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <main class="dashboard-content admin-manage">
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة / الحجوزات</span>
                    <h1>الحجز #{{ $booking->id }}</h1>
                    <p class="page-intro">{{ $booking->service_name }}</p>
                </div>
                <a class="btn btn-outline" href="{{ route('admin.bookings.index', $booking->trashed() ? ['archived' => 1] : []) }}">رجوع للحجوزات</a>
            </div>

            @if ($booking->trashed())
                <div class="admin-alert admin-alert-error" role="status">
                    <span class="admin-alert-icon" aria-hidden="true">!</span>
                    <div>
                        <strong>حجز مؤرشف</strong>
                        <p>تمت أرشفة هذا الحجز بتاريخ {{ $booking->deleted_at->format('Y-m-d H:i') }}. بيانات الدفع المرتبطة به محفوظة.</p>
                    </div>
                </div>
            @endif

            <div class="details-grid">
                <section class="panel">
                    <h3>بيانات الحجز</h3>
                    <dl class="details-list">
                        <dt>الحالة</dt>
                        <dd><span class="pill {{ $statusColor }}">{{ $statusLabel }}</span></dd>
                        <dt>الخدمة</dt>
                        <dd>{{ $booking->service_name }}</dd>
                        <dt>الوصف</dt>
                        <dd>{{ $booking->service_description }}</dd>
                        <dt>الموعد</dt>
                        <dd>{{ $booking->booking_date->format('Y-m-d') }} · {{ substr($booking->start_time, 0, 5) }} إلى {{ substr($booking->end_time, 0, 5) }}</dd>
                        <dt>سعر الحجز</dt>
                        <dd>{{ number_format($booking->original_price, 2) }} د.أ</dd>
                        <dt>تاريخ الإنشاء</dt>
                        <dd>{{ $booking->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                        @if ($booking->approved_at)
                            <dt>تاريخ القبول</dt>
                            <dd>{{ $booking->approved_at->format('Y-m-d H:i') }}</dd>
                        @endif
                        @if ($booking->completed_at)
                            <dt>تاريخ الإكمال</dt>
                            <dd>{{ $booking->completed_at->format('Y-m-d H:i') }}</dd>
                        @endif
                        @if ($booking->cancelled_at)
                            <dt>تاريخ الإلغاء</dt>
                            <dd>
                                {{ $booking->cancelled_at->format('Y-m-d H:i') }}
                                @if ($booking->cancelledByUser)
                                    · بواسطة {{ $booking->cancelledByUser->first_name }} {{ $booking->cancelledByUser->last_name }}
                                @endif
                            </dd>
                            <dt>غرامة الإلغاء</dt>
                            <dd>{{ number_format($booking->cancellation_penalty, 2) }} د.أ</dd>
                        @endif
                    </dl>
                </section>

                <section class="panel">
                    <h3>أطراف الحجز</h3>
                    <dl class="details-list">
                        <dt>العميل</dt>
                        <dd>{{ trim($booking->customer?->first_name . ' ' . $booking->customer?->last_name) ?: '—' }}</dd>
                        <dt>إيميل العميل</dt>
                        <dd>{{ $booking->customer?->email ?? '—' }}</dd>
                        <dt>المزوّد</dt>
                        <dd>{{ trim($booking->vendorService?->vendor?->first_name . ' ' . $booking->vendorService?->vendor?->last_name) ?: '—' }}</dd>
                        <dt>إيميل المزوّد</dt>
                        <dd>{{ $booking->vendorService?->vendor?->email ?? '—' }}</dd>
                    </dl>
                </section>

                <section class="panel">
                    <h3>الدفع والاسترداد</h3>
                    <dl class="details-list">
                        <dt>الدفعة</dt>
                        <dd>
                            @if ($booking->payment)
                                #{{ $booking->payment->id }} · {{ number_format($booking->payment->amount, 2) }} د.أ
                                · {{ $paymentStatusLabel }}
                            @else
                                لا توجد دفعة مسجّلة.
                            @endif
                        </dd>
                        @if ($booking->payment?->paid_at)
                            <dt>تاريخ الدفع</dt>
                            <dd>{{ $booking->payment->paid_at->format('Y-m-d H:i') }}</dd>
                        @endif
                        <dt>طلب الاسترداد</dt>
                        <dd>
                            @if ($booking->refundRequest)
                                {{ $refundStatusLabel }}
                                @if ($booking->refundRequest->refund_amount)
                                    · {{ number_format($booking->refundRequest->refund_amount, 2) }} د.أ
                                @endif
                            @else
                                لا يوجد.
                            @endif
                        </dd>
                    </dl>
                </section>
            </div>

            @unless ($booking->trashed())
                <div class="panel">
                    <div class="panel-head">
                        <div>
                            <h3>أرشفة الحجز</h3>
                            <p class="page-intro">يختفي الحجز من القائمة الحالية وينتقل إلى الأرشيف، ولا تُحذف بيانات الدفع.</p>
                        </div>
                        <x-archive-button :action="route('admin.bookings.destroy', $booking)"
                            :message="'هل تريد حذف الحجز #' . $booking->id . '؟ سيبقى الحجز في الأرشيف وتُحفظ بيانات الدفع المرتبطة به.'"
                            label="حذف الحجز" />
                    </div>
                </div>
            @endunless
        </main>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
