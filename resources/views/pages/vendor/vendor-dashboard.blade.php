@extends('layouts.app')
@section('title', 'لوحة الفني | ServiceHub')
@section('page_id', 'vendor-dashboard')
@section('content')
    @include('components.navbar')
    <div class="container layout">
        @include('components.sidebars.vendor')
        <main>
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الفني</span>
                    <h1>مرحبًا {{  auth()->user()->first_name }}</h1>
                </div>
            </div>

                @if (in_array($applicationStatus ?? null, ['pending', 'rejected'], true))
                    <p class="note">حالة طلب الانضمام{{ $applicationStatus === 'pending' ? ' قيد المراجعة' : ' مرفوض' }}</p>
                @endif

                <section class="stats" aria-label="إحصائيات الفني">
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

            <div class="split">
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
                        <p class="muted">ما عندك طلبات جديدة بانتظار الرد.</p>
                    @endforelse
                </section>

                <section class="panel">
                    <div class="section-head">
                        <h2>المواعيد القادمة</h2>
                        <a class="btn btn-outline btn-sm" href="/vendor-availability">الجدول الأسبوعي</a>
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
                        <p class="muted">ما عندك مواعيد مؤكدة قادمة.</p>
                    @endforelse
                </section>
            </div>
        </main>
    </div>
    @include('components.footer')
    @include('components.mobile-nav-vendor')
@endsection
