@extends('layouts.app')
@section('title', 'إتمام الدفع')

@section('content')

@include('pages.partials.market-assets')

<p class="muted"><a href="{{ route('customer.bookings') }}">← الرجوع لحجوزاتي</a></p>

<div class="card" style="max-width:560px;margin:auto">
    <h2>إتمام الدفع</h2>
    <p class="note">دفع تجريبي — لا يتم خصم أي مبلغ فعلي.</p>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="summary">
        <div><span class="muted">الخدمة</span><strong>{{ $booking->service_name }}</strong></div>
        <div><span class="muted">مقدّم الخدمة</span><strong>{{ $booking->vendorService->vendor->first_name }} {{ $booking->vendorService->vendor->last_name }}</strong></div>
        <div><span class="muted">الموعد</span><strong>{{ $booking->booking_date->format('Y-m-d') }}، {{ substr($booking->start_time, 0, 5) }}</strong></div>
        <div><span class="muted">المجموع</span><strong>{{ number_format($booking->original_price, 2) }} د.أ</strong></div>
    </div>

    <form method="POST" action="{{ route('payment.store', $booking) }}">
        @csrf

        <div class="field">
            <label>طريقة الدفع</label>
            <select class="input" name="payment_method">
                <option value="card">بطاقة</option>
                <option value="cash">نقداً</option>
            </select>
        </div>
        <div class="field">
            <label>الاسم على البطاقة (للبطاقة فقط)</label>
            <input class="input" type="text" name="card_name" value="{{ old('card_name') }}" placeholder="الاسم الكامل">
        </div>
        <div class="field">
            <label>رقم البطاقة، 16 رقم (للبطاقة فقط)</label>
            <input class="input" type="text" name="card_number" dir="ltr" inputmode="numeric" maxlength="19" placeholder="4242 4242 4242 4242">
        </div>

        <button type="submit" class="btn btn-primary btn-block">ادفع {{ number_format($booking->original_price, 2) }} د.أ</button>
    </form>
</div>

@endsection