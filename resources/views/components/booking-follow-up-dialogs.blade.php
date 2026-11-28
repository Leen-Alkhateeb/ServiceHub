@if ((int) auth()->id() === (int) $booking->customer_id && $booking->status === 'completed' && $booking->completed_at)
    <dialog class="hub-dialog booking-follow-up-dialog" id="booking-rating-{{ $booking->id }}" aria-labelledby="booking-rating-title-{{ $booking->id }}">
        <div class="dialog-head">
            <h2 id="booking-rating-title-{{ $booking->id }}">تقييم الفني</h2>
            <x-modal-close data-close-dialog />
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
                <x-modal-close data-close-dialog />
            </div>
            <p class="muted">الحجز #{{ $booking->id }} · {{ $booking->service_name }}</p>
            @if($booking->completion_description)<p>إفادة الفني: {{ $booking->completion_description }}</p>@endif
            <form method="POST" action="{{ route('booking.objection', $booking) }}" enctype="multipart/form-data">
                @csrf
                <div class="field">
                    <label for="objection-reason-{{ $booking->id }}">سبب الاعتراض</label>
                    <textarea id="objection-reason-{{ $booking->id }}" name="reason" rows="4" maxlength="2000" placeholder="مثلاً: الفني حضر وما أكمل الشغل، أو المشكلة لسه موجودة" required>{{ is_string(old('reason')) ? old('reason') : '' }}</textarea>
                </div>
                <div class="field"><label for="objection-images-{{ $booking->id }}">صور توضح المشكلة (اختيارية)</label><x-file-picker><input id="objection-images-{{ $booking->id }}" name="evidence[]" type="file" accept="image/jpeg,image/png,image/webp" multiple></x-file-picker><small>حتى 4 صور، 5 ميغابايت لكل صورة.</small></div>
                <button class="btn btn-yellow" type="submit">إرسال للإدارة</button>
            </form>
        </dialog>
    @endif
@endif
