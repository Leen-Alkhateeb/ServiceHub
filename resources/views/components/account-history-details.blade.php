<dialog class="hub-dialog" id="history-{{ $booking->id }}" aria-labelledby="history-title-{{ $booking->id }}">
    <div class="dialog-head">
        <h2 id="history-title-{{ $booking->id }}">{{ $tab === 'payments' ? 'تفاصيل الدفعة' : 'تفاصيل الطلب' }} <bdi>#{{ $booking->id }}</bdi></h2>
        <button class="btn btn-outline btn-sm" type="button" data-close-dialog>إغلاق</button>
    </div>
    <h3>{{ $booking->service_name }}</h3>
    <p>{{ $booking->service_description }}</p>
    <dl class="booking-details">
        <dt>الفني</dt><dd>{{ $booking->vendorService->vendor->first_name }} {{ $booking->vendorService->vendor->last_name }}</dd>
        <dt>القسم</dt><dd>{{ $booking->vendorService->service->category->name }}</dd>
        <dt>تاريخ الحجز</dt><dd><bdi>{{ \Illuminate\Support\Carbon::parse($booking->booking_date)->format('d/m/Y') }}</bdi></dd>
        <dt>وقت الخدمة</dt><dd><bdi>{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</bdi></dd>
        <dt>قيمة الطلب</dt><dd><bdi>{{ number_format((float) $booking->original_price, 2) }}</bdi> د.أ</dd>
        <dt>حالة الطلب</dt><dd>{{ $statusLabel($booking->status) }}</dd>
        @if ($payment = $booking->payment)
            <dt>حالة الدفع</dt><dd>{{ $statusLabel($payment->status) }}</dd>
            <dt>المبلغ</dt><dd><bdi>{{ number_format((float) $payment->amount, 2) }}</bdi> د.أ</dd>
            <dt>طريقة الدفع</dt><dd>{{ match ($payment->payment_method) { 'card' => 'بطاقة', 'cash' => 'نقدًا', 'simulated' => 'دفع تجريبي', default => $payment->payment_method ?? '—' } }}</dd>
            <dt>تاريخ ووقت الدفع</dt><dd><x-dialog-date-time :value="$payment->paid_at" /></dd>
        @endif
    </dl>
    @if ($tab === 'bookings' && $booking->status === 'approved')
        <a class="btn btn-yellow" href="{{ route('payment.checkout', $booking) }}">الدفع</a>
    @endif
</dialog>
