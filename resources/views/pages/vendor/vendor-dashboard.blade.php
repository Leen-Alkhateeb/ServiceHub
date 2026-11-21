@extends('layouts.app')
@section('title', 'لوحة الفني | ServiceHub')
@section('page_id', 'vendor-dashboard')
@section('content')
    @include('components.navbar')
    <div class="container layout vendor-workspace">
        @include('components.sidebars.vendor')
        <main class="vendor-workspace-content">
            <header class="dash-title vendor-page-header">
                <div>
                    <span class="small muted">لوحة الفني</span>
                    <h1>مرحبًا {{ $vendorName }}</h1>
                </div>
                <a class="btn btn-outline" href="{{ route('vendor.profile') }}">ملف الفني</a>
            </header>

            @if (in_array($applicationStatus ?? null, ['pending', 'rejected'], true))
                <p class="notice">حالة طلب الانضمام{{ $applicationStatus === 'pending' ? ' قيد المراجعة' : ' مرفوض' }}</p>
            @endif

            <section class="stats vendor-overview-stats" aria-label="إحصائيات الفني">
                <article class="stat">
                    <strong>{{ $stats['pending'] }}</strong>
                    <span>طلبات بانتظار الرد</span>
                </article>
                <article class="stat">
                    <strong>{{ $stats['upcoming'] }}</strong>
                    <span>مواعيد مؤكدة قادمة</span>
                </article>
                <article class="stat">
                    <strong>{{ $stats['completed'] }}</strong>
                    <span>حجوزات مكتملة</span>
                </article>
                <article class="stat">
                    <strong>{{ $stats['rating'] !== null ? number_format((float) $stats['rating'], 1) : '—' }}</strong>
                    <span>متوسط التقييم</span>
                </article>
            </section>

            <div class="vendor-chart-grid">
                <section class="panel" aria-labelledby="vendor-week-chart">
                    <div class="section-head"><h2 id="vendor-week-chart">الحجوزات خلال آخر 7 أيام</h2></div>
                    <div class="vendor-chart-frame">
                        <canvas id="bookingsChart" role="img" aria-labelledby="vendor-week-chart">
                            {{ implode('، ', $bookingChart['values']) }}
                        </canvas>
                    </div>
                </section>
                <section class="panel" aria-labelledby="vendor-status-chart">
                    <div class="section-head">
                        <h2 id="vendor-status-chart">توزيع حالات الحجوزات</h2>
                        <span class="muted">{{ $bookingStatusCounts->sum() }} حجز</span>
                    </div>
                    @if ($bookingStatusCounts->isEmpty())
                        <p class="vendor-empty">رح يظهر الرسم هون بعد ما تستقبل أول حجز.</p>
                    @endif
                    <div class="vendor-chart-frame" @if ($bookingStatusCounts->isEmpty()) hidden @endif>
                        <canvas id="statusChart" role="img" aria-labelledby="vendor-status-chart"></canvas>
                    </div>
                </section>
            </div>

            <div class="split vendor-overview-panels">
                <section class="panel">
                    <div class="section-head">
                        <h2>طلبات حجز جديدة</h2>
                        <a class="btn btn-outline btn-sm" href="{{ route('vendor.vendor-bookings.index') }}">عرض الكل</a>
                    </div>
                    @forelse ($pendingBookings as $booking)
                        <article class="item-row">
                            <div>
                                <strong>{{ $booking->service_name }}</strong>
                                <p class="muted small">
                                    <a href="{{ route('users.profile.show', $booking->customer) }}">{{ $booking->customer->first_name }} {{ $booking->customer->last_name }}</a>
                                    · {{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('Y-m-d') }}
                                    · <span dir="ltr">{{ substr($booking->start_time, 0, 5) }}</span>
                                </p>
                            </div>
                            <a class="btn btn-yellow btn-sm" href="{{ route('vendor.vendor-bookings.index') }}">مراجعة</a>
                        </article>
                    @empty
                        <p class="vendor-empty">ما عندك طلبات جديدة بانتظار الرد.</p>
                    @endforelse
                </section>

                <section class="panel">
                    <div class="section-head">
                        <h2>المواعيد القادمة</h2>
                        <a class="btn btn-outline btn-sm" href="{{ route('vendor.availability.index') }}">الجدول الأسبوعي</a>
                    </div>
                    @forelse ($upcomingBookings as $booking)
                        <article class="item-row">
                            <div>
                                <strong>{{ $booking->service_name }}</strong>
                                <p class="muted small">
                                    <a href="{{ route('users.profile.show', $booking->customer) }}">{{ $booking->customer->first_name }} {{ $booking->customer->last_name }}</a>
                                    · {{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('Y-m-d') }}
                                    · <span dir="ltr">{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}</span>
                                </p>
                            </div>
                        </article>
                    @empty
                        <p class="vendor-empty">ما عندك مواعيد مؤكدة قادمة.</p>
                    @endforelse
                </section>
            </div>
        </main>
    </div>
    @include('components.footer')
    @include('components.mobile-nav-vendor')
@include('components.booking-charts-script')
@endsection
