@if ((int) auth()->id() === (int) $booking->customer_id && $booking->status === 'completed' && $booking->completed_at)
    <dialog class="hub-dialog booking-follow-up-dialog" id="booking-rating-{{ $booking->id }}" aria-labelledby="booking-rating-title-{{ $booking->id }}">
        <div class="dialog-head">
            <h2 id="booking-rating-title-{{ $booking->id }}">تقييم الفني</h2>
            <button class="btn btn-outline btn-sm" type="button" data-close-dialog>إغلاق</button>
        </div>
        <p class="muted">{{ $booking->service_name }}</p>
        <form method="POST" action="{{ route('booking.review', $booking) }}">
            @csrf
            <fieldset class="rating-stars">
                <legend>اختر عدد النجوم</legend>
                <div>
                    @for ($star = 5; $star >= 1; $star--)
                        <label>
                            <input class="sr-only" type="radio" name="rating" value="{{ $star }}" required aria-label="{{ $star }} من 5">
                            <span aria-hidden="true">★</span>
                        </label>
                    @endfor
                </div>
            </fieldset>
            <div class="field">
                <label for="rating-comment-{{ $booking->id }}">تعليق (اختياري)</label>
                <textarea id="rating-comment-{{ $booking->id }}" name="comment" rows="3" maxlength="2000"></textarea>
            </div>
            <button class="btn btn-yellow" type="submit">حفظ التقييم</button>
        </form>
    </dialog>
    @if (! $booking->refundRequest && $booking->objection_deadline?->isFuture())
        <dialog class="hub-dialog booking-follow-up-dialog" id="booking-objection-{{ $booking->id }}" aria-labelledby="booking-objection-title-{{ $booking->id }}">
            <div class="dialog-head">
                <h2 id="booking-objection-title-{{ $booking->id }}">إرسال اعتراض</h2>
                <button class="btn btn-outline btn-sm" type="button" data-close-dialog>إغلاق</button>
            </div>
            <p class="muted">{{ $booking->service_name }}</p>
            <form method="POST" action="{{ route('booking.objection', $booking) }}">
                @csrf
                <div class="field">
                    <label for="objection-reason-{{ $booking->id }}">سبب الاعتراض</label>
                    <textarea id="objection-reason-{{ $booking->id }}" name="reason" rows="4" maxlength="2000" required></textarea>
                </div>
                <button class="btn btn-yellow" type="submit">إرسال للإدارة</button>
            </form>
        </dialog>
    @endif
@endif
