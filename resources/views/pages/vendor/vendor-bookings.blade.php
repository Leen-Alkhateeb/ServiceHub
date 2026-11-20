@extends('layouts.app')
@section('title', 'طلبات الحجز | ServiceHub')
@section('page_id', 'vendor-bookings')
@section('content')
    @include('components.navbar')
    <div class="container layout">
        @include('components.sidebars.vendor')
        <main>
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الفني</span>
                    <h1>طلبات الحجز</h1>
                </div>
            </div>

            @if (session('success'))
                <div class="notice" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="notice" role="alert">{{ $errors->first() }}</div>
            @endif

            <section class="panel">
                <h2>بانتظار ردك</h2>
                <p>عند الموافقة، سيكون لدى العميل 30 دقيقة لإتمام الدفع.</p>

                @forelse ($pendingBookings as $booking)
                    <article class="item-row">
                        <div>
                            <strong>{{ $booking->service_name }}</strong>
                            <p class="muted">
                                {{ $booking->customer->first_name }} {{ $booking->customer->last_name }}
                                · {{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('Y-m-d') }}
                                · <span dir="ltr">{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}</span>
                            </p>
                            <p>{{ number_format((float) $booking->original_price, 2) }} د.أ</p>
                        </div>
                        <div class="row">
                            <form method="POST" action="{{ route('vendor.vendor-bookings.update', $booking) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button class="btn btn-yellow btn-sm" style="width:50px"  type="submit">موافقة</button>
                            </form>
                            
                            <form method="POST" action="{{ route('vendor.vendor-bookings.update', $booking) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button class="btn btn-outline btn-sm" style="width:50px; margin-top:10px;"   type="submit">رفض</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="muted">ما عندك طلبات جديدة بانتظار الرد.</p>
                @endforelse
            </section>
            <section class="panel">
                <h2>حجوزات أخرى</h2>
                @forelse ($otherBookings as $booking)
                    @php
                        [$statusLabel, $statusTone] = match ($booking->status) {
                            'approved' => ['بانتظار الدفع', 'info'],
                            'confirmed' => ['مؤكد', 'info'],
                            'in_progress' => ['قيد التنفيذ', 'warning'],
                            'completed' => ['مكتمل', 'success'],
                            'rejected' => ['مرفوض', 'danger'],
                            'cancelled' => ['ملغي', 'muted'],
                            'expired' => ['منتهي', 'muted'],
                            'refunded' => ['مسترد', 'muted'],
                            'objection_pending' => ['اعتراض قيد المراجعة', 'warning'],
                            'closed' => ['مغلق', 'muted'],
                            'paid' => ['مدفوع', 'info'],
                            'awaiting_payment' => ['بانتظار الدفع', 'info'],
                            default => [$booking->status, 'muted'],
                        };
                    @endphp
                    <article class="item-row">
                        <div>
                            <strong>{{ $booking->service_name }}</strong>
                            <p class="muted">
                                {{ $booking->customer->first_name }} {{ $booking->customer->last_name }}
                                · {{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('Y-m-d') }}
                                · <span dir="ltr">{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}</span>
                            </p>
                            @if ($booking->status === 'approved' && $booking->payment_deadline)
                                <p class="muted">آخر موعد للدفع: {{ \Illuminate\Support\Carbon::parse($booking->payment_deadline)->format('Y-m-d H:i') }}</p>
                            @endif
                        </div>
                        <span class="badge badge-{{ $statusTone }}">{{ $statusLabel }}</span>
                    </article>
                @empty
                    <p class="muted">ما في حجوزات أخرى لعرضها.</p>
                @endforelse
            </section>
        </main>
    </div>
    @include('components.footer')
    @include('components.mobile-nav-vendor')
@endsection



