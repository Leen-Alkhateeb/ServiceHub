@extends('layouts.app')
@section('title', 'حركات المحفظة | ServiceHub')
@section('page_id', 'admin-wallet')
@section('content')
    @include('components.navbar')
    <div class="container layout">@include('components.sidebars.admin')
        <div>
            <div class="dash-title">
                <div><span class="small muted">لوحة الإدارة</span>
                    <h1>حركات المحفظة</h1>
                </div>
            </div>
            <div class="panel">
                @php
                    $types = [
                        'credit' => 'إضافة رصيد',
                        'payout' => 'صرف مستحقات',
                        'penalty' => 'خصم',
                        'refund_share' => 'حصة الاسترداد',
                        'adjustment' => 'تسوية',
                    ];
                @endphp
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>الرقم</th>
                                <th>الفني</th>
                                <th>الحجز</th>
                                <th>نوع الحركة</th>
                                <th>المبلغ</th>
                                <th>الوصف</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->id }}</td>
                                    <td>
                                        @if ($transaction->vendor)
                                            {{ $transaction->vendor->first_name }} {{ $transaction->vendor->last_name }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($transaction->booking)
                                            <a href="{{ route('admin.bookings.show', $transaction->booking) }}">
                                                #{{ $transaction->booking->id }} · {{ $transaction->booking->service_name }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $types[$transaction->transaction_type] ?? $transaction->transaction_type }}</td>
                                    <td>{{ number_format($transaction->amount, 2) }} د.أ</td>
                                    <td>{{ $transaction->description ?: '—' }}</td>
                                    <td>{{ $transaction->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7">لا توجد حركات محفظة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $transactions->onEachSide(1)->links('components.pager') }}
            </div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-admin')
@endsection
