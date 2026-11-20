@props(['entries', 'tab', 'filters', 'categories', 'routeName'])
@php
    $statusLabel = fn ($status) => match ($status) {
        'pending', 'awaiting_payment' => 'قيد الانتظار', 'approved' => 'تمت الموافقة',
        'paid', 'confirmed' => 'مدفوع', 'completed' => 'مكتمل',
        'cancelled' => 'ملغى', 'failed' => 'فشل الدفع', 'refunded' => 'مسترد',
        'credit', 'payout' => 'مبلغ محرّر', 'penalty' => 'غرامة', 'adjustment' => 'تسوية',
        'refund_share' => 'حصة الاسترداد', 'in_progress' => 'قيد التنفيذ', 'expired' => 'منتهي',
        'rejected' => 'مرفوض', 'objection_pending' => 'اعتراض قيد المراجعة', 'closed' => 'مغلق',
        default => $status,
    };
@endphp
<section class="section profile-history" id="profile-content">
    <div class="panel">
        <h2>{{ match ($tab) { 'bookings' => 'الخدمات التي طلبتها', 'payments' => 'مدفوعاتي', default => 'مستحقاتي' } }}</h2>
        @if (session('success'))<p class="notice" role="status">{{ session('success') }}</p>@endif
        @if (session('error'))<p class="notice" role="alert">{{ session('error') }}</p>@endif
        @if ($tab !== 'income')
            @if ($errors->hasAny(['q', 'category', 'from', 'to']))
                <p class="notice" role="alert">تأكد من اختيار قسم صحيح، وأن تاريخ النهاية يأتي بعد تاريخ البداية أو يساويه.</p>
            @endif
            <form class="hub-filters" action="{{ route($routeName) }}" method="GET">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="field"><label for="history-search">اسم الفني أو الخدمة</label><input id="history-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100"></div>
                <div class="field"><label for="history-category">القسم</label><select id="history-category" name="category"><option value="">كل الأقسام</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((int) ($filters['category'] ?? 0) === $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="field"><label for="history-from">من تاريخ {{ $tab === 'payments' ? 'الدفعة' : 'الحجز' }}</label><input id="history-from" name="from" type="date" value="{{ $filters['from'] ?? '' }}"></div>
                <div class="field"><label for="history-to">إلى تاريخ</label><input id="history-to" name="to" type="date" value="{{ $filters['to'] ?? '' }}"></div>
                <div class="filter-actions"><button class="btn btn-yellow btn-sm" type="submit">تصفية</button><a class="btn btn-outline btn-sm" href="{{ route($routeName, ['tab' => $tab]) }}">مسح</a></div>
            </form>
        @endif
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
                                $date = $tab === 'income' ? $entry->created_at : ($tab === 'payments' ? ($payment->paid_at ?? $payment->created_at) : $booking->booking_date);
                            @endphp
                            <tr>
                                <td>{{ $booking->service_name }} <bdi>#{{ $booking->id }}</bdi>
                                    @if ($tab !== 'income')
                                        <small class="history-provider">{{ $booking->vendorService->vendor->first_name }} {{ $booking->vendorService->vendor->last_name }}</small>
                                    @endif
                                </td>
                                <td><bdi>{{ $date ? \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') : '—' }}</bdi></td>
                                <td><bdi>{{ number_format((float) $amount, 2) }}</bdi></td>
                                <td>{{ $statusLabel($status) }}</td>
                                @if ($tab !== 'income')
                                    <td>
                                        <button class="btn btn-outline btn-sm" type="button" data-open-dialog="history-{{ $booking->id }}">عرض</button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($tab !== 'income')
                @foreach ($entries as $booking)
                    @include('components.account-history-details')
                @endforeach
            @endif
            {{ $entries->onEachSide(1)->links('components.pager') }}
        @else
            <p class="muted">لا يوجد سجل لعرضه حاليًا.</p>
        @endif
        @if ($tab === 'income')
            <a class="btn btn-outline btn-sm" href="{{ route('vendor.wallet') }}">فتح السجل المالي</a>
        @endif
    </div>
</section>
