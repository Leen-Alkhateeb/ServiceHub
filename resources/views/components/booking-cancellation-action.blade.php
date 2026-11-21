@php($requesterCancels = (int) auth()->id() === (int) $booking->customer_id)

@if ($booking->canBeCancelled() && ($requesterCancels || $booking->status !== 'pending'))
    <button class="btn btn-outline btn-sm" type="button" data-open-dialog="cancel-booking-{{ $booking->id }}">إلغاء الحجز</button>
    <dialog class="hub-dialog booking-follow-up-dialog" id="cancel-booking-{{ $booking->id }}" aria-labelledby="cancel-booking-title-{{ $booking->id }}">
        <div class="dialog-head">
            <h2 id="cancel-booking-title-{{ $booking->id }}">تأكيد إلغاء الحجز</h2>
        </div>
        <p>هل تريد إلغاء حجز «{{ $booking->service_name }}»؟</p>
        @if ($booking->payment?->status === 'paid')
            <p class="notice">
                {{ $requesterCancels
                    ? 'ستُخصم غرامة 30٪ من قيمة الحجز، ويُردّ باقي المبلغ المدفوع.'
                    : 'سيُردّ كامل المبلغ للعميل وتُسجّل عليك غرامة 15٪ من قيمة الخدمة.' }}
            </p>
        @endif
        <form class="booking-follow-up-actions" method="POST" action="{{ route('booking.cancel', $booking) }}" data-turbo="false">
            @csrf
            <button class="btn btn-outline" type="button" data-close-dialog autofocus>رجوع</button>
            <button class="btn btn-danger" type="submit">تأكيد الإلغاء</button>
        </form>
    </dialog>
@elseif ($booking->status === 'cancelled' && $booking->cancellation_refund_amount !== null)
    <p class="muted">المبلغ المسترد للعميل: <bdi>{{ number_format($booking->cancellation_refund_amount, 2) }}</bdi> د.أ</p>
    @if ($booking->cancellation_penalty > 0)
        <p class="muted">غرامة {{ (int) $booking->cancelled_by === (int) $booking->customer_id ? 'العميل' : 'الفني' }}: <bdi>{{ number_format($booking->cancellation_penalty, 2) }}</bdi> د.أ</p>
    @endif
@endif
