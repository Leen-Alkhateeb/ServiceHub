@extends('layouts.app')

@section('title', 'المدفوعات | ServiceHub')
@section('page_id', 'admin-payments')

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
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>المدفوعات</h1>
                    <p class="page-intro">متابعة دفعات العربون التي سجّلها العملاء للحجوزات. هذه الصفحة للعرض فقط.</p>
                </div>
            </div>

            <div class="stats">
                <div class="stat">
                    <strong>{{ number_format($paidTotal * 0.15, 2) }}</strong>
                    <span>أرباح المنصة (د.أ)</span>
                </div>
                <div class="stat">
                    <strong>{{ number_format($paidTotal, 2) }}</strong>
                    <span>إجمالي المدفوع (د.أ)</span>
                </div>
                <div class="stat">
                    <strong>{{ $paidCount }}</strong>
                    <span>دفعات مكتملة</span>
                </div>
                <div class="stat">
                    <strong>{{ $pendingCount }}</strong>
                    <span>بانتظار الدفع</span>
                </div>
                <div class="stat">
                    <strong>{{ number_format($refundedTotal, 2) }}</strong>
                    <span>مبالغ مستردة (د.أ)</span>
                </div>
            </div>

            @if ($errors->any())
                <div class="admin-alert admin-alert-error" role="alert">
                    <span class="admin-alert-icon" aria-hidden="true">!</span>
                    <div>
                        <strong>تحقق من الفلاتر</strong>
                        <p>{{ $errors->first() }}</p>
                    </div>
                </div>
            @endif

            <form method="GET" action="{{ route('admin.payments.index') }}" class="panel hub-filters">
                <div class="field">
                    <label for="payment-search">بحث</label>
                    <input id="payment-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="رقم الدفعة أو الحجز، الخدمة أو العميل">
                </div>
                <div class="field">
                    <label for="payment-status">الحالة</label>
                    <select id="payment-status" name="status">
                        <option value="">جميع الحالات</option>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="payment-method">طريقة الدفع</label>
                    <select id="payment-method" name="method">
                        <option value="">جميع الطرق</option>
                        @foreach ($methods as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['method'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="payment-from">من تاريخ الدفع</label>
                    <input id="payment-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="field">
                    <label for="payment-to">إلى تاريخ الدفع</label>
                    <input id="payment-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="filter-actions">
                    <button class="btn btn-yellow" type="submit">تصفية</button>
                    <a class="btn btn-outline" href="{{ route('admin.payments.index') }}">مسح</a>
                </div>
            </form>

            <div class="panel">
                <div class="panel-head">
                    <h3>قائمة المدفوعات</h3>
                    <span class="results-count">{{ $payments->total() }} دفعة</span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>الرقم</th>
                                <th>الحجز</th>
                                <th>العميل</th>
                                <th>المبلغ</th>
                                <th>الطريقة</th>
                                <th>تاريخ الدفع</th>
                                <th>الحالة</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $payment)
                                <tr>
                                    <td>#{{ $payment->id }}</td>
                                    <td>
                                        <span class="cell-title">{{ $payment->booking?->service_name ?? '—' }}</span>
                                        <span class="cell-sub">
                                            حجز #{{ $payment->booking_id }}
                                            @if ($payment->booking?->trashed())
                                                · مؤرشف
                                            @endif
                                        </span>
                                    </td>
                                    <td>{{ trim($payment->booking?->customer?->first_name . ' ' . $payment->booking?->customer?->last_name) ?: '—' }}</td>
                                    <td class="nowrap"><span class="cell-title">{{ number_format($payment->amount, 2) }} د.أ</span></td>
                                    <td>{{ $methods[$payment->payment_method] ?? '—' }}</td>
                                    <td class="nowrap">{{ $payment->paid_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>
                                        <span class="pill {{ $statusColors[$payment->status] ?? '' }}">{{ $statuses[$payment->status] ?? $payment->status }}</span>
                                    </td>
                                    <td>
                                        <a class="btn btn-outline btn-sm" href="{{ route('admin.payments.show', $payment) }}">التفاصيل</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <strong>لا توجد مدفوعات مطابقة</strong>
                                            جرّب تغيير البحث أو الفلاتر، أو اضغط "مسح".
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $payments->onEachSide(1)->links('components.pager') }}
        </main>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
