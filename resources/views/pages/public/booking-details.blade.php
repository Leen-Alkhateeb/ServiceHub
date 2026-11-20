@extends('layouts.app')
@section('title', 'الفاتورة ' . $booking->invoice_number)
@section('page_id', 'invoice')

@section('content')
@php
    $total = (float) $booking->original_price;
    $paid = (float) ($booking->payment->amount ?? 0);
    $remaining = max(0, round($total - $paid, 2));
    $isPartial = $remaining > 0;

    $paymentMethod = match ($booking->payment->payment_method ?? '') {
        'card' => 'بطاقة',
        'simulated' => 'دفعة مقدّمة عبر المنصة',
        'cash' => 'نقدًا',
        default => '—',
    };
@endphp

<style>
    .invoice-page {
        --iv-ink: #18342e;
        --iv-muted: #71817c;
        --iv-green: #176b5b;
        --iv-line: #e4ece8;
        direction: rtl;
        min-height: 100vh;
        padding: 28px 16px 60px;
        color: var(--iv-ink);
        background: #f4f7f5;
    }

    .invoice-page *,
    .invoice-page *::before,
    .invoice-page *::after {
        box-sizing: border-box;
    }

    .invoice-wrap {
        width: min(900px, 100%);
        margin: auto;
    }

    .invoice-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }

    .invoice-back {
        color: var(--iv-green);
        text-decoration: none;
        font-weight: 700;
    }

    .invoice-print {
        border: 0;
        border-radius: 9px;
        padding: 11px 16px;
        color: #fff;
        background: var(--iv-ink);
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    .invoice-paper {
        overflow: hidden;
        border: 1px solid var(--iv-line);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 14px 38px #18342e0d;
    }

    .invoice-header {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        padding: 30px;
        color: #fff;
        background: linear-gradient(120deg, #176b5b, #104b41);
    }

    .invoice-brand {
        margin: 0;
        font-size: 28px;
        font-weight: 800;
    }

    .invoice-subtitle {
        margin: 7px 0 0;
        color: #d4e9e2;
        font-size: 14px;
    }

    .invoice-number {
        text-align: left;
    }

    .invoice-number h2 {
        margin: 0 0 7px;
        font-size: 23px;
    }

    .invoice-number p {
        margin: 4px 0;
        color: #e2f0eb;
        font-size: 14px;
    }

    .invoice-status {
        display: inline-block;
        margin-top: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        color: #125142;
        background: #e2f5eb;
        font-size: 13px;
        font-weight: 800;
    }

    .invoice-content {
        padding: 26px 30px 30px;
    }

    .invoice-parties {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 24px;
    }

    .invoice-party {
        padding: 17px;
        border: 1px solid var(--iv-line);
        border-radius: 12px;
        background: #fbfcfb;
    }

    .invoice-party h3 {
        margin: 0 0 10px;
        color: var(--iv-muted);
        font-size: 13px;
        font-weight: 700;
    }

    .invoice-party strong {
        display: block;
        margin-bottom: 6px;
        font-size: 16px;
    }

    .invoice-party p {
        margin: 4px 0;
        color: var(--iv-muted);
        font-size: 14px;
        overflow-wrap: anywhere;
    }

    .invoice-table-wrap {
        overflow-x: auto;
        border: 1px solid var(--iv-line);
        border-radius: 12px;
    }

    .invoice-table {
        width: 100%;
        border-collapse: collapse;
        text-align: right;
    }

    .invoice-table th,
    .invoice-table td {
        padding: 14px 15px;
        border-bottom: 1px solid var(--iv-line);
        vertical-align: top;
    }

    .invoice-table th {
        color: #435952;
        background: #f2f7f4;
        font-size: 13px;
    }

    .invoice-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .invoice-service-description {
        margin-top: 6px;
        color: var(--iv-muted);
        font-size: 13px;
        line-height: 1.6;
    }

    .invoice-summary {
        width: min(390px, 100%);
        margin: 22px 0 0 auto;
        padding: 17px;
        border: 1px solid var(--iv-line);
        border-radius: 12px;
        background: #fbfcfb;
    }

    .invoice-summary-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 9px 0;
        color: var(--iv-muted);
        border-bottom: 1px solid var(--iv-line);
    }

    .invoice-summary-row strong {
        color: var(--iv-ink);
        text-align: left;
        white-space: nowrap;
    }

    .invoice-summary-row:last-child {
        border-bottom: 0;
    }

    .invoice-summary-row.deposit strong {
        color: var(--iv-green);
    }

    .invoice-summary-row.remaining strong {
        color: #875d13;
    }

    .invoice-footer {
        margin: 22px 0 0;
        padding-top: 16px;
        border-top: 1px solid var(--iv-line);
        color: var(--iv-muted);
        text-align: center;
        font-size: 13px;
    }

    @media (max-width: 600px) {
        .invoice-page {
            padding: 16px 10px 36px;
        }

        .invoice-header {
            flex-direction: column;
            padding: 22px;
        }

        .invoice-number {
            text-align: right;
        }

        .invoice-content {
            padding: 18px;
        }

        .invoice-parties {
            grid-template-columns: 1fr;
        }

        .invoice-table th,
        .invoice-table td {
            padding: 11px 9px;
            font-size: 13px;
        }

        .invoice-actions {
            align-items: stretch;
        }

        .invoice-print {
            padding-inline: 10px;
        }
    }

    @media print {
        @page {
            size: A4;
            margin: 12mm;
        }

        body {
            background: #fff !important;
        }

        .invoice-page {
            min-height: 0;
            padding: 0;
            background: #fff;
        }

        .invoice-actions {
            display: none !important;
        }

        .invoice-paper {
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .invoice-header {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .invoice-table,
        .invoice-party,
        .invoice-summary {
            break-inside: avoid;
        }
    }
</style>

<main class="invoice-page">
    <div class="invoice-wrap">
        <div class="invoice-actions">
            <a class="invoice-back" href="{{ route('customer.bookings') }}">
                ← الرجوع إلى حجوزاتي
            </a>

            <button class="invoice-print" type="button" onclick="window.print()">
                طباعة الفاتورة / حفظ PDF
            </button>
        </div>

        <article class="invoice-paper">
            <header class="invoice-header">
                <div>
                    <h1 class="invoice-brand">ServiceHub</h1>
                    <p class="invoice-subtitle">فاتورة حجز خدمات الصيانة</p>
                </div>

                <div class="invoice-number">
                    <h2>فاتورة</h2>
                    <p>رقم الفاتورة: {{ $booking->invoice_number }}</p>
                    <p>
                        تاريخ الدفع:
                        {{ $booking->payment->paid_at?->format('Y-m-d H:i') ?? '—' }}
                    </p>
                    <span class="invoice-status">
                        {{ $isPartial ? 'العربون مدفوع' : 'مدفوعة بالكامل' }}
                    </span>
                </div>
            </header>

            <div class="invoice-content">
                <section class="invoice-parties">
                    <div class="invoice-party">
                        <h3>بيانات العميل</h3>
                        <strong>
                            {{ $booking->customer->first_name }}
                            {{ $booking->customer->last_name }}
                        </strong>
                        <p>{{ $booking->customer->email }}</p>
                        <p dir="ltr">{{ $booking->customer->phone ?? '—' }}</p>
                    </div>

                    <div class="invoice-party">
                        <h3>مقدّم الخدمة</h3>
                        <strong>
                            {{ $booking->vendorService->vendor->first_name }}
                            {{ $booking->vendorService->vendor->last_name }}
                        </strong>
                        <p>{{ $booking->vendorService->vendor->location ?? '—' }}</p>
                        <p dir="ltr">{{ $booking->vendorService->vendor->phone ?? '—' }}</p>
                    </div>
                </section>

                <div class="invoice-table-wrap">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th>الخدمة</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>السعر الكامل</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>{{ $booking->service_name }}</strong>
                                    @if ($booking->service_description)
                                        <div class="invoice-service-description">
                                            {{ \Illuminate\Support\Str::limit($booking->service_description, 180) }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $booking->booking_date->format('Y-m-d') }}</td>
                                <td dir="ltr">
                                    {{ substr($booking->start_time, 0, 5) }}
                                    –
                                    {{ substr($booking->end_time, 0, 5) }}
                                </td>
                                <td>{{ number_format($total, 2) }} د.أ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <section class="invoice-summary">
                    <div class="invoice-summary-row">
                        <span>قيمة الخدمة كاملة</span>
                        <strong>{{ number_format($total, 2) }} د.أ</strong>
                    </div>
                    <div class="invoice-summary-row deposit">
                        <span>العربون المدفوع (50%)</span>
                        <strong>{{ number_format($paid, 2) }} د.أ</strong>
                    </div>
                    <div class="invoice-summary-row remaining">
                        <span>الباقي عند وصول الفني</span>
                        <strong>{{ number_format($remaining, 2) }} د.أ</strong>
                    </div>
                    <div class="invoice-summary-row">
                        <span>طريقة دفع العربون</span>
                        <strong>{{ $paymentMethod }}</strong>
                    </div>
                </section>

                <p class="invoice-footer">
                    {{ $isPartial
                        ? 'يرجى دفع المبلغ المتبقي لمقدّم الخدمة عند وصوله.'
                        : 'شكرًا لاستخدامك ServiceHub.' }}
                </p>
            </div>
        </article>
    </div>
</main>
@endsection