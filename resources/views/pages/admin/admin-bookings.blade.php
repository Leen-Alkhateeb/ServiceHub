@extends('layouts.app')
@section('title', 'الحجوزات | ServiceHub')
@section('page_id', 'admin-bookings')
@section('content')
    @include('components.navbar')
    <div class="container layout">
        @include('components.sidebars.admin')
        <main class="dashboard-content">
            <div class="dash-title">
                <h1>{{ !empty($filters['archived']) ? 'الحجوزات المؤرشفة' : 'الحجوزات' }}</h1>
                <a class="btn btn-outline" href="{{ route('admin.bookings.index', empty($filters['archived']) ? ['archived' => 1] : []) }}">
                    {{ !empty($filters['archived']) ? 'الحجوزات الحالية' : 'عرض الأرشيف' }}
                </a>
            </div>

            @if (session('status'))
                <p class="notice" role="status">{{ session('status') }}</p>
            @endif

            @include('components.booking-filters', [
                'action' => route('admin.bookings.index', !empty($filters['archived']) ? ['archived' => 1] : []),
                'placeholder' => 'رقم الحجز أو اسم الخدمة',
                'labels' => $statuses,
                'statusLabel' => 'حالة الحجز',
            ])

            <div class="panel">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>الرقم</th>
                                <th>الخدمة</th>
                                <th>العميل</th>
                                <th>المزوّد</th>
                                <th>الموعد</th>
                                <th>الحالة</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookings as $booking)
                                <tr>
                                    <td>#{{ $booking->id }}</td>
                                    <td>{{ $booking->service_name }}</td>
                                    <td>{{ trim($booking->customer?->first_name.' '.$booking->customer?->last_name) ?: '—' }}</td>
                                    <td>{{ trim($booking->vendorService?->vendor?->first_name.' '.$booking->vendorService?->vendor?->last_name) ?: '—' }}</td>
                                    <td>{{ $booking->booking_date->format('Y-m-d') }} {{ substr($booking->start_time, 0, 5) }}</td>
                                    <td><span class="pill">{{ $statuses[$booking->status] ?? $booking->status }}</span></td>
                                    <td><a class="btn btn-outline btn-sm" href="{{ route('admin.bookings.show', $booking) }}">التفاصيل</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7">لا توجد حجوزات مطابقة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            {{ $bookings->onEachSide(1)->links('components.pager') }}
        </main>
    </div>
    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
