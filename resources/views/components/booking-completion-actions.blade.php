@php
    $isRequester = (int) auth()->id() === (int) $booking->customer_id;
    $confirmedByViewer = $isRequester ? $booking->customer_completed_at : $booking->vendor_completed_at;
@endphp

@if ($booking->acceptsCompletionConfirmation())
    @if ($confirmedByViewer)
        <p class="muted">تم تأكيد الاكتمال من طرفك. بانتظار الطرف الآخر.</p>
    @else
        <form method="POST" action="{{ route('booking.completion', $booking) }}">
            @csrf
            <button class="btn btn-outline btn-sm" type="submit">تأكيد اكتمال الخدمة</button>
        </form>
    @endif
@endif

@if ($isRequester && $booking->status === 'completed' && $booking->completed_at)
    <div class="booking-follow-up-actions">
        <button class="btn btn-yellow btn-sm" type="button" data-open-dialog="booking-rating-{{ $booking->id }}">تقييم الفني</button>
        @if (! $booking->refundRequest && $booking->objection_deadline?->isFuture())
            <button class="btn btn-outline btn-sm" type="button" data-open-dialog="booking-objection-{{ $booking->id }}">إرسال اعتراض</button>
        @endif
    </div>
    @if ($booking->refundRequest)
        <p class="muted">{{ $booking->refundRequest->status === 'pending' ? 'الاعتراض قيد المراجعة.' : 'تمت مراجعة الاعتراض.' }}</p>
    @endif
@endif
