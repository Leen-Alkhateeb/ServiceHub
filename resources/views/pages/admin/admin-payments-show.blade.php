@extends('layouts.app')

@section('title', 'تفاصيل الدفعة')
@section('page_id', 'admin-payments')

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
                    <span class="small muted">لوحة الإدارة / المدفوعات</span>
                    <h1>الدفعة #{{ $payment->id }}</h1>
                    <p class="page-intro">{{ $booking?->service_name }}</p>
                </div>
                @include('components.admin-period')
                <a class="btn btn-outline" href="{{ route('admin.payments.index') }}">رجوع للمدفوعات</a>
            </div>

            <div class="details-grid">
                <section class="panel">
                    <h3>بيانات الدفعة</h3>
                    <dl class="details-list">
                        <dt>الحالة</dt>
                        <dd><span class="pill {{ $statusColor }}">{{ $statusLabel }}</span></dd>
                        <dt>المبلغ المدفوع</dt>
                        <dd>{{ number_format($payment->amount, 2) }} د.أ</dd>
                        <dt>طريقة الدفع</dt>
                        <dd>{{ $methodLabel }}</dd>
                        <dt>تاريخ الدفع</dt>
                        <dd>{{ $payment->paid_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                        @if ($booking)
                            <dt>سعر الحجز الكامل</dt>
                            <dd>{{ number_format($booking->original_price, 2) }} د.أ</dd>
                            <dt>المتبقي عند الوصول</dt>
                            <dd>{{ number_format($remainingAmount, 2) }} د.أ</dd>
                        @endif
                    </dl>
                </section>

                <section class="panel">
                    <h3>الحجز المرتبط</h3>
                    @if ($booking)
                        <dl class="details-list">
                            <dt>رقم الحجز</dt>
                            <dd>
                                <a href="{{ route('admin.bookings.show', $booking) }}">#{{ $booking->id }}</a>
                                @if ($booking->trashed())
                                    <span class="pill">مؤرشف</span>
                                @endif
                            </dd>
                            <dt>موعد الخدمة</dt>
                            <dd>{{ $booking->booking_date->format('Y-m-d') }} · {{ substr($booking->start_time, 0, 5) }}</dd>
                            <dt>العميل</dt>
                            <dd>{{ trim($booking->customer?->first_name . ' ' . $booking->customer?->last_name) ?: '—' }}</dd>
                            <dt>المزوّد</dt>
                            <dd>{{ trim($booking->vendorService?->vendor?->first_name . ' ' . $booking->vendorService?->vendor?->last_name) ?: '—' }}</dd>
                            <dt>طلب استرداد</dt>
                            <dd>{{ $booking->refundRequest ? 'يوجد طلب استرداد' : 'لا يوجد' }}</dd>
                        </dl>
                    @else
                        <p class="page-intro">الحجز المرتبط غير موجود.</p>
                    @endif
                </section>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h3>حركات محفظة المزوّد لهذا الحجز</h3>
                    <span class="results-count">{{ $booking?->walletTransactions->count() ?? 0 }} حركة</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>الرقم</th>
                                <th>النوع</th>
                                <th>المبلغ</th>
                                <th>الوصف</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($booking?->walletTransactions ?? [] as $transaction)
                                <tr>
                                    <td>#{{ $transaction->id }}</td>
                                    <td>{{ $transactionTypes[$transaction->transaction_type] ?? $transaction->transaction_type }}</td>
                                    <td>{{ number_format($transaction->amount, 2) }} د.أ</td>
                                    <td>{{ $transaction->description ?? '—' }}</td>
                                    <td>{{ $transaction->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <strong>لا توجد حركات بعد</strong>
                                            يُضاف المبلغ لمحفظة المزوّد بعد اكتمال الخدمة وانتهاء مهلة الاعتراض.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
