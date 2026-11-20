@extends('layouts.app')
@section('title', 'تأكيد الحجز والدفع')

@section('content')
@include('components.navbar')

@php
    $total = (float) $booking->original_price;
    $deposit = round($total * 0.5, 2);
    $remaining = round($total - $deposit, 2);
@endphp

<style>
    .checkout-page {
        --green: #176b5b;
        --green-dark: #105346;
        --ink: #18342e;
        --muted: #71817c;
        --line: #e4ece8;
        direction: rtl;
        min-height: 75vh;
        padding: 38px 16px 64px;
        color: var(--ink);
        background: #f5f8f6;
    }

    .checkout-wrap {
        width: min(980px, 100%);
        margin: auto;
    }

    .checkout-back {
        display: inline-block;
        margin-bottom: 18px;
        color: var(--green);
        text-decoration: none;
        font-weight: 700;
    }

    .checkout-heading {
        margin-bottom: 22px;
    }

    .checkout-heading h1 {
        margin: 0 0 7px;
        font-size: clamp(26px, 4vw, 36px);
    }

    .checkout-heading p {
        margin: 0;
        color: var(--muted);
    }

    .checkout-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        align-items: start;
    }

    .checkout-card {
        padding: 24px;
        border: 1px solid var(--line);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 24px #18342e0a;
    }

    .checkout-card h2 {
        margin: 0 0 18px;
        font-size: 20px;
    }

    .checkout-details {
        display: grid;
        gap: 14px;
    }

    .checkout-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--line);
    }

    .checkout-row span {
        color: var(--muted);
    }

    .checkout-row strong {
        text-align: left;
    }

    .checkout-total {
        margin-top: 4px;
        font-size: 18px;
    }

    .payment-breakdown {
        display: grid;
        gap: 10px;
        margin-top: 20px;
        padding: 16px;
        border-radius: 12px;
        background: #f1f7f3;
    }

    .payment-breakdown div {
        display: flex;
        justify-content: space-between;
        gap: 12px;
    }

    .payment-breakdown .deposit {
        color: var(--green);
        font-size: 18px;
        font-weight: 800;
    }

    .payment-breakdown .remaining {
        color: #6b5941;
        font-weight: 700;
    }

    .payment-note {
        margin: 14px 0 0;
        color: var(--muted);
        font-size: 14px;
        line-height: 1.7;
    }

    .payment-demo {
        margin-bottom: 18px;
        padding: 12px 14px;
        border: 1px solid #ead8a9;
        border-radius: 10px;
        color: #715817;
        background: #fff9e9;
        font-size: 13px;
        line-height: 1.6;
    }

    .checkout-error {
        margin-bottom: 16px;
        padding: 12px 14px;
        border: 1px solid #f0caca;
        border-radius: 10px;
        color: #8b2929;
        background: #fff2f2;
    }

    .checkout-field {
        margin-bottom: 16px;
    }

    .checkout-field label {
        display: block;
        margin-bottom: 7px;
        font-size: 14px;
        font-weight: 700;
    }

    .checkout-field input {
        width: 100%;
        min-height: 48px;
        padding: 11px 13px;
        border: 1px solid var(--line);
        border-radius: 10px;
        outline: none;
        font: inherit;
    }

    .checkout-field input:focus {
        border-color: #78aa9b;
        box-shadow: 0 0 0 3px #176b5b15;
    }

    .checkout-field input[dir="ltr"] {
        text-align: left;
    }

    .checkout-submit {
        width: 100%;
        min-height: 50px;
        margin-top: 6px;
        border: 0;
        border-radius: 10px;
        color: #fff;
        background: var(--green);
        font: inherit;
        font-weight: 800;
        cursor: pointer;
    }

    .checkout-submit:hover {
        background: var(--green-dark);
    }

    .checkout-deadline {
        margin: 14px 0 0;
        color: var(--muted);
        text-align: center;
        font-size: 13px;
    }

    @media (max-width: 720px) {
        .checkout-page {
            padding-top: 24px;
        }

        .checkout-grid {
            grid-template-columns: 1fr;
        }

        .checkout-card {
            padding: 19px;
        }
    }
</style>

<main class="checkout-page">
    <div class="checkout-wrap">
        <a class="checkout-back" href="{{ route('customer.bookings') }}">
            ← الرجوع إلى حجوزاتي
        </a>

        <header class="checkout-heading">
            <h1>تأكيد الحجز والدفع</h1>
            <p>ادفع نصف قيمة الخدمة الآن، والباقي نقدًا عند وصول الفني.</p>
        </header>

        @if ($errors->any())
            <div class="checkout-error">{{ $errors->first() }}</div>
        @endif

        <div class="checkout-grid">
            <section class="checkout-card">
                <h2>تفاصيل الحجز</h2>

                <div class="checkout-details">
                    <div class="checkout-row">
                        <span>الخدمة</span>
                        <strong>{{ $booking->service_name }}</strong>
                    </div>

                    <div class="checkout-row">
                        <span>مقدّم الخدمة</span>
                        <strong>
                            {{ $booking->vendorService->vendor->first_name }}
                            {{ $booking->vendorService->vendor->last_name }}
                        </strong>
                    </div>

                    <div class="checkout-row">
                        <span>الموعد</span>
                        <strong>
                            {{ $booking->booking_date->format('Y-m-d') }}
                            · {{ substr($booking->start_time, 0, 5) }}
                        </strong>
                    </div>

                    <div class="checkout-row checkout-total">
                        <span>قيمة الخدمة كاملة</span>
                        <strong>{{ number_format($total, 2) }} د.أ</strong>
                    </div>
                </div>

                <div class="payment-breakdown">
                    <div class="deposit">
                        <span>العربون المطلوب الآن (50%)</span>
                        <strong>{{ number_format($deposit, 2) }} د.أ</strong>
                    </div>
                    <div class="remaining">
                        <span>الباقي عند وصول الفني</span>
                        <strong>{{ number_format($remaining, 2) }} د.أ</strong>
                    </div>
                </div>

                <p class="payment-note">
                    إذا لم يُدفع العربون قبل انتهاء المهلة، تنتهي صلاحية الحجز ويصبح الموعد متاحًا لغيرك.
                </p>
            </section>

            <section class="checkout-card">
                <h2>دفع العربون</h2>

               

                <form method="POST" action="{{ route('payment.store', $booking) }}">
                    @csrf
                    <input type="hidden" name="payment_method" value="card">

                    <div class="checkout-field">
                        <label for="card_name">الاسم على البطاقة</label>
                        <input id="card_name"
                               type="text"
                               name="card_name"
                               value="{{ old('card_name') }}"
                               autocomplete="cc-name"
                               required>
                    </div>

                    <div class="checkout-field">
                        <label for="card_number">رقم البطاقة</label>
                        <input id="card_number"
                               type="text"
                               name="card_number"
                               dir="ltr"
                               inputmode="numeric"
                               autocomplete="cc-number"
                               maxlength="19"
                               placeholder="4242 4242 4242 4242"
                               required>
                    </div>

                    <button class="checkout-submit" type="submit">
                        ادفع العربون {{ number_format($deposit, 2) }} د.أ
                    </button>

                    @if ($booking->payment_deadline)
                        <p class="checkout-deadline">
                            مهلة الدفع حتى
                            {{ $booking->payment_deadline->format('Y-m-d H:i') }}
                        </p>
                    @endif
                </form>
            </section>
        </div>
    </div>
</main>

@include('components.footer')
@include('components.mobile-nav-public')
@endsection