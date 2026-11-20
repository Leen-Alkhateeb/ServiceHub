@props(['entries', 'tab'])
<section class="section profile-history" id="profile-content">
    <div class="panel">
        <h2>{{ match ($tab) { 'bookings' => 'الخدمات التي طلبتها', 'payments' => 'مدفوعاتي', default => 'مستحقاتي' } }}</h2>
        @if (session('success'))<p class="notice" role="status">{{ session('success') }}</p>@endif
        @if (session('error'))<p class="notice" role="alert">{{ session('error') }}</p>@endif
        @if ($entries->isNotEmpty())
            <div class="hub-table-wrap" tabindex="0">
                <table class="hub-table">
                    <thead><tr><th>الخدمة</th><th>التاريخ</th><th>المبلغ (د.أ)</th><th>الحالة</th>@if ($tab !== 'income')<th>الإجراء</th>@endif</tr></thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            @php
                                $booking = $tab === 'income' ? $entry->booking : $entry;
                                $payment = $tab === 'payments' ? $booking->payment : null;
                                $status = $tab === 'income' ? $entry->transaction_type : ($payment?->status ?? $booking->status);
                                $amount = $tab === 'income' ? $entry->amount : ($payment?->amount ?? $booking->original_price);
                                $date = $tab === 'income' ? $entry->created_at : ($payment?->paid_at ?? $booking->booking_date);
                            @endphp
                            <tr>
                                <td>{{ $booking->service_name }} <bdi>#{{ $booking->id }}</bdi>
                                    @if ($tab === 'bookings')
                                        <small class="history-provider">{{ $booking->vendorService->vendor->first_name }} {{ $booking->vendorService->vendor->last_name }}</small>
                                    @endif
                                </td>
                                <td><bdi>{{ $date ? \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') : '—' }}</bdi></td>
                                <td><bdi>{{ number_format((float) $amount, 2) }}</bdi></td>
                                <td>{{ match ($status) {
                                    'pending', 'awaiting_payment' => 'قيد الانتظار', 'approved' => 'تمت الموافقة',
                                    'paid', 'confirmed' => 'مدفوع', 'completed' => 'مكتمل',
                                    'cancelled' => 'ملغى', 'failed' => 'فشل الدفع', 'refunded' => 'مسترد',
                                    'credit', 'payout' => 'مبلغ محرّر', 'penalty' => 'غرامة', 'adjustment' => 'تسوية',
                                    'refund_share' => 'حصة الاسترداد', 'in_progress' => 'قيد التنفيذ', 'expired' => 'منتهي',
                                    'rejected' => 'مرفوض', 'objection_pending' => 'اعتراض قيد المراجعة', 'closed' => 'مغلق',
                                    default => $status,
                                } }}</td>
                                @if ($tab !== 'income')
                                    <td>
                                        @if ($tab === 'bookings' && $booking->status === 'approved')
                                            <a class="btn btn-outline btn-sm" href="{{ route('payment.checkout', $booking) }}">الدفع</a>
                                        @elseif ($payment?->status === 'paid')
                                            <a class="btn btn-outline btn-sm" href="{{ route('payment.invoice', $booking) }}">الفاتورة</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $entries->onEachSide(1)->links('components.pager') }}
        @else
            <p class="muted">لا يوجد سجل لعرضه حاليًا.</p>
        @endif
        @if ($tab === 'income')
            <a class="btn btn-outline btn-sm" href="{{ route('vendor.wallet') }}">فتح السجل المالي</a>
        @endif
    </div>
</section>
