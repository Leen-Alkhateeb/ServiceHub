@extends('layouts.app')
@section('title', 'حجوزاتي')

@section('content')

@include('pages.partials.market-assets')

@php
    $statusMap = [
        'pending'   => ['بانتظار موافقة الفني', 'warning'],
        'approved'  => ['بانتظار الدفع',        'info'],
        'confirmed' => ['مؤكّد',                'info'],
        'completed' => ['مكتمل',                'success'],
        'rejected'  => ['مرفوض',                'danger'],
        'cancelled' => ['ملغي',                 'muted'],
        'expired'   => ['منتهي — لم يُدفع',     'muted'],
        'closed'    => ['مغلق',                 'muted'],
    ];
@endphp

<div class="profile-layout">
    @include('pages.customer.menu')

    <section class="stack">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row between">
            <h2>حجوزاتي</h2>
            <a href="{{ route('market.index') }}" class="btn btn-primary btn-sm">احجز خدمة</a>
        </div>

        @foreach (['الحالية' => $current, 'السابقة' => $past] as $title => $list)
            <h3>{{ $title }}</h3>

            @forelse ($list as $booking)
                @php [$label, $tone] = $statusMap[$booking->status]; @endphp

                <div class="card booking">
                    <div>
                        <div class="row">
                            <strong>{{ $booking->service_name }}</strong>
                            <span class="badge badge-{{ $tone }}">{{ $label }}</span>
                        </div>
                        <p class="muted">{{ $booking->vendorService->vendor->first_name }} {{ $booking->vendorService->vendor->last_name }} · #{{ $booking->id }}</p>
                        <p>
                            {{ $booking->booking_date->format('Y-m-d') }} ·
                            <span dir="ltr">{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</span> ·
                            <strong>{{ number_format($booking->original_price, 2) }} د.أ</strong>
                        </p>
                        @if ($booking->status === 'approved')
                            <p class="note">ادفع قبل {{ $booking->payment_deadline?->format('Y-m-d H:i') }}</p>
                        @endif
                    </div>

                    <div class="row">
                        @if ($booking->status === 'approved')
                            <button type="button" class="btn btn-primary btn-sm" data-open="pay-{{ $booking->id }}">إتمام الدفع</button>
                        @endif

                        @if (in_array($booking->status, ['pending', 'approved']))
                            <form method="POST" action="{{ route('booking.cancel', $booking) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm">إلغاء الحجز</button>
                            </form>
                        @endif

                        @if ($booking->payment)
                            <a href="{{ route('payment.invoice', $booking) }}" class="btn btn-outline btn-sm">الفاتورة</a>
                        @endif
                    </div>
                </div>

                {{-- نافذة الدفع (تجريبي) --}}
                @if ($booking->status === 'approved')
                    <div class="modal" id="pay-{{ $booking->id }}" role="dialog" aria-modal="true">
                        <div class="modal-backdrop" data-close></div>
                        <div class="modal-dialog">
                            <div class="modal-header">
                                <h3>إتمام الدفع</h3>
                                <button type="button" class="modal-close" data-close aria-label="إغلاق">✕</button>
                            </div>

                            <form method="POST" action="{{ route('payment.store', $booking) }}" class="modal-body">
                                @csrf
                                <p class="note">دفع تجريبي — لا يتم خصم أي مبلغ فعلي.</p>

                                <div class="summary">
                                    <div><span class="muted">الخدمة</span><strong>{{ $booking->service_name }}</strong></div>
                                    <div><span class="muted">الموعد</span><strong>{{ $booking->booking_date->format('Y-m-d') }}، {{ substr($booking->start_time, 0, 5) }}</strong></div>
                                    <div><span class="muted">المجموع</span><strong>{{ number_format($booking->original_price, 2) }} د.أ</strong></div>
                                </div>

                                <div class="field">
                                    <label>طريقة الدفع</label>
                                    <select class="input" name="payment_method">
                                        <option value="card">بطاقة</option>
                                        <option value="cash">نقداً</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label>الاسم على البطاقة (للبطاقة فقط)</label>
                                    <input class="input" type="text" name="card_name" placeholder="الاسم الكامل">
                                </div>
                                <div class="field">
                                    <label>رقم البطاقة، 16 رقم (للبطاقة فقط)</label>
                                    <input class="input" type="text" name="card_number" dir="ltr" inputmode="numeric" maxlength="19" placeholder="4242 4242 4242 4242">
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">ادفع {{ number_format($booking->original_price, 2) }} د.أ</button>
                            </form>
                        </div>
                    </div>
                @endif
            @empty
                <div class="card empty"><p class="muted">ما في حجوزات {{ $title }}.</p></div>
            @endforelse
        @endforeach
    </section>
</div>
@endsection

