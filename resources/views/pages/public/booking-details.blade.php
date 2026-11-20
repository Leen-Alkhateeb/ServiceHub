@extends('layouts.app')
@section('title', 'فاتورة ' . $booking->invoice_number)

@section('content')

@include('pages.partials.market-assets')

<div class="row between no-print">
    <a href="{{ route('customer.bookings') }}">← الرجوع لحجوزاتي</a>
    <button type="button" class="btn btn-dark btn-sm" onclick="window.print()">طباعة الفاتورة</button>
</div>

<div class="card invoice">
    <div class="invoice-head">
        <div>
            <h1 class="brand">صلّحها</h1>
            <p class="muted">منصة أردنية لحجز خدمات الصيانة</p>
        </div>
        <div class="invoice-meta">
            <h2>فاتورة</h2>
            <p><strong>{{ $booking->invoice_number }}</strong></p>
            <p class="muted">تاريخ الدفع: {{ $booking->payment->paid_at->format('Y-m-d H:i') }}</p>
            <span class="badge badge-success">مدفوعة</span>
        </div>
    </div>

    <div class="grid two">
        <div>
            <p class="muted">العميل</p>
            <strong>{{ $booking->customer->first_name }} {{ $booking->customer->last_name }}</strong>
            <p>{{ $booking->customer->email }}<br><span dir="ltr">{{ $booking->customer->phone }}</span></p>
        </div>
        <div>
            <p class="muted">مقدّم الخدمة</p>
            <strong>{{ $booking->vendorService->vendor->first_name }} {{ $booking->vendorService->vendor->last_name }}</strong>
            <p>{{ $booking->vendorService->vendor->location }}<br><span dir="ltr">{{ $booking->vendorService->vendor->phone }}</span></p>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr><th>الخدمة</th><th>التاريخ</th><th>الوقت</th><th>المبلغ</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $booking->service_name }}</strong>
                    @if ($booking->service_description)
                        <div class="muted">{{ \Illuminate\Support\Str::limit($booking->service_description, 120) }}</div>
                    @endif
                </td>
                <td>{{ $booking->booking_date->format('Y-m-d') }}</td>
                <td dir="ltr">{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</td>
                <td>{{ number_format($booking->original_price, 2) }} د.أ</td>
            </tr>
        </tbody>
    </table>

    <div class="invoice-total">
        <div>
            <span class="muted">طريقة الدفع</span>
            <strong>{{ $booking->payment->payment_method === 'card' ? 'بطاقة' : 'نقداً' }}</strong>
        </div>
        <div>
            <span class="muted">المبلغ المدفوع</span>
            <strong class="price">{{ number_format($booking->payment->amount, 2) }} د.أ</strong>
        </div>
    </div>

    <p class="muted center">فاتورة تجريبية — لم تتم أي عملية دفع حقيقية.</p>
</div>

@endsection