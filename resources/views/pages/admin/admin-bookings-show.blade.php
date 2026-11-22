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

        <main class="dashboard-content admin-manage booking-inspect">
            @php
                $payment = $booking->payment;
                $refund = $booking->refundRequest;
                $events = [
                    'إنشاء الحجز' => $booking->created_at,
                    'موافقة الفني' => $booking->approved_at,
                    'تأكيد العميل للاكتمال' => $booking->customer_completed_at,
                    'تأكيد الفني للاكتمال' => $booking->vendor_completed_at,
                    'اكتمال الحجز' => $booking->completed_at,
                    'إلغاء الحجز' => $booking->cancelled_at,
                ];
            @endphp
            <div class="booking-inspect-header">
                <div>
                    <span class="muted">الحجز <bdi>#{{ $booking->id }}</bdi></span>
                    <h1>{{ $booking->service_name }}</h1>
                </div>
                <a class="btn btn-outline" href="{{ route('admin.bookings.index', $booking->trashed() ? ['archived' => 1] : []) }}">رجوع للحجوزات</a>
            </div>

            @if ($booking->trashed())
                <div class="admin-alert admin-alert-error" role="status">
                    <div>
                        <strong>حجز مؤرشف</strong>
                        <x-dialog-date-time :value="$booking->deleted_at" />
                    </div>
                </div>
            @endif

            <dl class="booking-inspect-summary">
                <div>
                    <dt>حالة الحجز</dt>
                    <dd><span class="pill {{ $statusColor }}">{{ $statusLabel }}</span></dd>
                </div>
                <div>
                    <dt>موعد الخدمة</dt>
                    <dd>
                        <bdi>{{ $booking->booking_date?->format('d/m/Y') ?? '—' }}</bdi>
                        <small><bdi>{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</bdi></small>
                    </dd>
                </div>
                <div>
                    <dt>قيمة الحجز</dt>
                    <dd><bdi>{{ number_format($booking->original_price, 2) }}</bdi> د.أ</dd>
                </div>
            </dl>

            @if (filled($booking->service_description))
                <section class="panel booking-inspect-section">
                    <h2>وصف الخدمة</h2>
                    <p class="booking-inspect-description">{{ $booking->service_description }}</p>
                </section>
            @endif

            <div class="booking-inspect-grid">
                <section class="panel booking-inspect-section">
                    <h2>أطراف الحجز</h2>
                    <dl class="booking-inspect-fields">
                        @foreach (['العميل' => $booking->customer, 'مقدّم الخدمة' => $booking->vendorService?->vendor] as $role => $participant)
                            <div>
                                <dt>{{ $role }}</dt>
                                <dd>
                                    <strong>{{ trim($participant?->first_name . ' ' . $participant?->last_name) ?: '—' }}</strong>
                                    <small><bdi>{{ $participant?->email ?? '—' }}</bdi></small>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section class="panel booking-inspect-section">
                    <h2>الدفع والاسترداد</h2>
                    <dl class="booking-inspect-fields">
                        <div>
                            <dt>حالة الدفع</dt>
                            <dd>{{ $payment ? $paymentStatusLabel : 'لا توجد دفعة مسجّلة.' }}</dd>
                        </div>
                        @if ($payment)
                            <div><dt>رقم الدفعة</dt><dd><bdi>#{{ $payment->id }}</bdi></dd></div>
                            <div><dt>المبلغ المسجّل</dt><dd><bdi>{{ number_format($payment->amount, 2) }}</bdi> د.أ</dd></div>
                            @if ($payment->paid_at)
                                <div><dt>تاريخ الدفع</dt><dd><x-dialog-date-time :value="$payment->paid_at" /></dd></div>
                            @endif
                        @endif
                        <div><dt>طلب الاسترداد</dt><dd>{{ $refund ? $refundStatusLabel : 'لا يوجد.' }}</dd></div>
                        @if ($refund?->refund_amount !== null)
                            <div><dt>مبلغ الاسترداد</dt><dd><bdi>{{ number_format($refund->refund_amount, 2) }}</bdi> د.أ</dd></div>
                        @endif
                        @if ($booking->objection_deadline)
                            <div><dt>نهاية مهلة الاعتراض</dt><dd><x-dialog-date-time :value="$booking->objection_deadline" /></dd></div>
                        @endif
                        @if ($booking->cancellation_refund_amount !== null)
                            <div><dt>استرداد الإلغاء للعميل</dt><dd><bdi>{{ number_format($booking->cancellation_refund_amount, 2) }}</bdi> د.أ</dd></div>
                            <div><dt>حصة المنصة من الإلغاء</dt><dd><bdi>{{ number_format($booking->cancellation_platform_share, 2) }}</bdi> د.أ</dd></div>
                            <div><dt>تسوية محفظة الفني</dt><dd><bdi>{{ number_format($booking->cancellation_vendor_amount ?? 0, 2) }}</bdi> د.أ</dd></div>
                        @endif
                    </dl>
                </section>

                <section class="panel booking-inspect-section booking-inspect-timeline">
                    <h2>سجل الحجز</h2>
                    <dl class="booking-inspect-fields">
                        @foreach (collect($events)->filter()->sortBy(fn ($date) => $date->timestamp) as $label => $time)
                            <div><dt>{{ $label }}</dt><dd><x-dialog-date-time :value="$time" /></dd></div>
                        @endforeach
                        @if ($booking->cancelled_at)
                            @if ($booking->cancelledByUser)
                                <div><dt>ألغاه</dt><dd>{{ $booking->cancelledByUser->first_name }} {{ $booking->cancelledByUser->last_name }}</dd></div>
                            @endif
                            <div><dt>غرامة الإلغاء</dt><dd><bdi>{{ number_format($booking->cancellation_penalty, 2) }}</bdi> د.أ</dd></div>
                        @endif
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
