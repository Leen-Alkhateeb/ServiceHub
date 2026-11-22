@extends('layouts.app')

@section('title', 'الحجوزات | ServiceHub')
@section('page_id', 'admin-bookings')

@push('styles')
    <x-public-stylesheet file="admin-management.css" />
@endpush

@section('content')
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <main class="dashboard-content admin-manage">
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>{{ $showArchived ? 'الحجوزات المؤرشفة' : 'الحجوزات' }}</h1>
                    <p class="page-intro">
                        @if ($showArchived)
                            حجوزات تمت أرشفتها، وتبقى بيانات الدفع المرتبطة بها محفوظة.
                        @else
                            متابعة جميع حجوزات العملاء مع المزوّدين وحالة كل حجز.
                        @endif
                    </p>
                </div>
                @if ($showArchived)
                    <a class="btn btn-outline" href="{{ route('admin.bookings.index') }}">الحجوزات الحالية ({{ $activeCount }})</a>
                @else
                    <a class="btn btn-outline" href="{{ route('admin.bookings.index', ['archived' => 1]) }}">عرض الأرشيف ({{ $archivedCount }})</a>
                @endif
            </div>

            @include('pages.admin.partials.flash-messages')

            @include('components.booking-filters', [
                'action' => route('admin.bookings.index', $showArchived ? ['archived' => 1] : []),
                'placeholder' => 'رقم الحجز، الخدمة، العميل أو المزوّد',
                'labels' => $statuses,
                'statusLabel' => 'حالة الحجز',
            ])

            <div class="panel">
                <div class="panel-head">
                    <h3>{{ $showArchived ? 'قائمة الأرشيف' : 'قائمة الحجوزات' }}</h3>
                    <span class="results-count">{{ $bookings->total() }} حجز</span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>الرقم</th>
                                <th>الخدمة</th>
                                <th>العميل</th>
                                <th>المزوّد</th>
                                <th>الموعد</th>
                                <th>السعر</th>
                                <th>الحالة</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookings as $booking)
                                <tr>
                                    <td>#{{ $booking->id }}</td>
                                    <td><span class="cell-title">{{ $booking->service_name }}</span></td>
                                    <td>{{ trim($booking->customer?->first_name . ' ' . $booking->customer?->last_name) ?: '—' }}</td>
                                    <td>{{ trim($booking->vendorService?->vendor?->first_name . ' ' . $booking->vendorService?->vendor?->last_name) ?: '—' }}</td>
                                    <td class="nowrap">
                                        <span class="cell-title">{{ $booking->booking_date->format('Y-m-d') }}</span>
                                        <span class="cell-sub"><bdi dir="ltr">{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</bdi></span>
                                    </td>
                                    <td class="nowrap">{{ number_format($booking->original_price, 2) }} د.أ</td>
                                    <td>
                                        <span class="pill {{ $statusColors[$booking->status] ?? '' }}">{{ $statuses[$booking->status] ?? $booking->status }}</span>
                                    </td>
                                    <td>
                                        <a class="btn btn-outline btn-sm" href="{{ route('admin.bookings.show', $booking) }}">التفاصيل</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <strong>لا توجد حجوزات مطابقة</strong>
                                            جرّب تغيير البحث أو الفلاتر، أو اضغط "مسح".
                                        </div>
                                    </td>
                                </tr>
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
