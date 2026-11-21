@extends('layouts.app')
@section('title', 'السجل المالي | ServiceHub')
@section('page_id', 'vendor-wallet')
@section('content')
@include('components.navbar')
<main class="container layout">
    @include('components.sidebars.vendor')
    <div class="dashboard-content">
        <div class="dash-title">
            <div><h1>السجل المالي</h1><p class="muted">يُحتجز المبلغ لمدة 24 ساعة بعد إتمام الخدمة. يظهر أي اعتراض أو مشكلة دفع ضمن الحالات التي تتطلب متابعة.</p></div>
            <a class="btn btn-outline" href="{{ route('vendor.profile') }}">ملف الفني</a>
        </div>

        @include('components.booking-filters', ['action' => route('vendor.wallet'), 'placeholder' => 'الخدمة، العميل، أو رقم الحجز'])

        <section class="panel wallet-overview" aria-labelledby="wallet-overview-title">
            <h2 id="wallet-overview-title">ملخص الأرصدة</h2>
            @php
                $scale = max(1, ...array_map(fn ($item) => abs((float) $item['amount']), array_values($summary)));
            @endphp
            <div class="balance-cards">
                @foreach ($summary as $key => $item)
                    <article class="balance-card" data-balance="{{ $key }}">
                        <span class="balance-label">{{ $item['label'] }}</span>
                        <strong><bdi>{{ number_format((float) $item['amount'], 2) }}</bdi> <small>د.أ</small></strong>
                        <meter class="balance-meter" data-balance="{{ $key }}" min="0" max="{{ $scale }}" value="{{ abs((float) $item['amount']) }}" aria-label="{{ $item['label'] }}"></meter>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="panel wallet-ledger">
            <div class="section-head">
                <h2>الحركات حسب الحجز</h2>
                @if ($bookings->isNotEmpty())
                    <span class="muted">{{ $bookings->firstItem() }}–{{ $bookings->lastItem() }} من {{ $bookings->total() }}</span>
                @endif
            </div>
            <div class="hub-table-wrap">
                <table class="hub-table">
                    <thead>
                        <tr><th scope="col">الحجز</th><th scope="col">الخدمة</th><th scope="col">تاريخ الحجز</th><th scope="col">حالة المبلغ</th><th scope="col">المبلغ (د.أ)</th><th scope="col">التفاصيل</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $booking)
                            @php
                                $released = $booking->walletTransactions->contains(fn ($entry) => in_array($entry->transaction_type, ['credit', 'payout'], true));
                                $paymentIssue = $booking->payment?->status !== 'paid';
                                $complaint = $booking->refundRequest && $booking->refundRequest->status !== 'rejected';
                                $releaseAt = \Illuminate\Support\Carbon::parse($booking->completed_at)->addHours(24);
                                $status = $released ? 'released' : ($paymentIssue || $complaint ? 'issue' : ($releaseAt->isFuture() ? 'held' : 'ready'));
                                $walletStatuses[$booking->id] = $labels[$status];
                                $amount = $released ? $booking->walletTransactions->sum('amount') : $booking->original_price;
                            @endphp
                            <tr>
                                <td><bdi>#{{ $booking->id }}</bdi></td>
                                <td>{{ $booking->service_name }}</td>
                                <td><bdi>{{ $booking->booking_date->format('Y-m-d') }}</bdi></td>
                                <td>
                                    <span class="hub-status" data-status="{{ $status }}">{{ $labels[$status] }}</span>
                                    @if ($released && ($paymentIssue || $complaint))
                                        <span class="hub-status" data-status="issue">{{ $labels['issue'] }}</span>
                                    @endif
                                </td>
                                <td><strong><bdi>{{ number_format((float) $amount, 2) }}</bdi></strong></td>
                                <td><button class="btn btn-outline btn-sm" type="button" data-open-dialog="wallet-{{ $booking->id }}">عرض</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="6">لا توجد حجوزات مكتملة مطابقة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $bookings->onEachSide(1)->links('components.pager') }}
        </section>

        @foreach ($bookings as $booking)
            <dialog class="hub-dialog" id="wallet-{{ $booking->id }}" aria-labelledby="wallet-title-{{ $booking->id }}">
                <div class="dialog-head">
                    <h2 id="wallet-title-{{ $booking->id }}">{{ $booking->service_name }} <bdi>#{{ $booking->id }}</bdi></h2>
                    <button type="button" class="btn btn-outline btn-sm" data-close-dialog>إغلاق</button>
                </div>
                <p>{{ $booking->service_description }}</p>
                <dl class="booking-details">
                    <dt>العميل</dt><dd><a href="{{ route('users.profile.show', $booking->customer) }}">{{ $booking->customer->first_name }} {{ $booking->customer->last_name }}</a></dd>
                    <dt>تاريخ الحجز</dt><dd><bdi>{{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('d/m/Y') }}</bdi></dd>
                    <dt>وقت الخدمة</dt><dd><bdi>{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</bdi></dd>
                    <dt>قيمة الطلب</dt><dd><bdi>{{ number_format((float) $booking->original_price, 2) }}</bdi> د.أ</dd>
                    <dt>حالة المبلغ</dt><dd>{{ $walletStatuses[$booking->id] }}</dd>
                    <dt>حالة الدفع</dt><dd>{{ match ($booking->payment?->status) { 'paid' => 'مدفوع', 'pending' => 'قيد الانتظار', 'failed' => 'فشل الدفع', 'refunded' => 'مسترد', default => $booking->payment?->status ?? 'لا يوجد سجل دفع' } }}</dd>
                    <dt>طريقة الدفع</dt><dd>{{ match ($booking->payment?->payment_method) { 'card' => 'بطاقة', 'cash' => 'نقدًا', default => $booking->payment?->payment_method ?? '—' } }}</dd>
                    <dt>تاريخ ووقت الدفع</dt><dd><x-dialog-date-time :value="$booking->payment?->paid_at" /></dd>
                    <dt>انتهاء الحجز المالي</dt><dd><x-dialog-date-time :value="\Illuminate\Support\Carbon::parse($booking->completed_at)->addHours(24)" /></dd>
                    @if ($booking->refundRequest)
                        <dt>طلب المراجعة</dt><dd>{{ match ($booking->refundRequest->status) { 'pending' => 'قيد المراجعة', 'approved' => 'تمت الموافقة', 'rejected' => 'مرفوض', default => $booking->refundRequest->status } }}</dd>
                        <dt>سبب الطلب</dt><dd>{{ $booking->refundRequest->reason }}</dd>
                        @if ($booking->refundRequest->refund_amount !== null)
                            <dt>قيمة الاسترداد</dt><dd>{{ number_format((float) $booking->refundRequest->refund_amount, 2) }} د.أ</dd>
                        @endif
                    @endif
                </dl>
            </dialog>
        @endforeach
    </div>
</main>
@include('components.footer')
@include('components.mobile-nav-vendor')
@endsection
