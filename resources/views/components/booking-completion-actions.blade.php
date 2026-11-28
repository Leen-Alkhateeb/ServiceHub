@php
    $isRequester = (int) auth()->id() === (int) $booking->customer_id;
    $confirmedByViewer = $isRequester ? $booking->customer_completed_at : $booking->vendor_completed_at;
@endphp

@if ($isRequester ? ($booking->acceptsCustomerAcknowledgment() || ($confirmedByViewer && $booking->acceptsCompletionConfirmation())) : $booking->acceptsCompletionConfirmation())
    @if ($confirmedByViewer)
        <p class="muted">تم تأكيد الاكتمال من طرفك. بانتظار الطرف الآخر.</p>
    @elseif($isRequester)
        <form method="POST" action="{{ route('booking.completion', $booking) }}">
            @csrf
            <button class="btn btn-outline btn-sm" type="submit">الخدمة مكتملة</button>
        </form>
        @if($booking->vendor_completed_at && !$booking->objection_deadline)
            <a href="{{ route('contact', ['incomplete_booking' => $booking->id]) }}">الإبلاغ عن خدمة غير مكتملة</a>
        @endif
    @else
        <button class="btn btn-outline btn-sm" type="button" data-open-dialog="complete-{{ $booking->id }}">تأكيد إتمام الخدمة</button>
        <dialog class="hub-dialog" id="complete-{{ $booking->id }}" aria-labelledby="complete-title-{{ $booking->id }}">
            <div class="dialog-head"><h2 id="complete-title-{{ $booking->id }}">تأكيد إتمام الخدمة</h2><x-modal-close data-close-dialog /></div>
            <p>الحجز #{{ $booking->id }} · {{ $booking->service_name }} · {{ $booking->customer?->first_name }} {{ $booking->customer?->last_name }}</p>
            <form method="POST" action="{{ route('booking.completion', $booking) }}" enctype="multipart/form-data">
                @csrf
                <div class="field"><label for="completion-description-{{ $booking->id }}">صف العمل اللي أنجزته</label><textarea id="completion-description-{{ $booking->id }}" name="completion_description" maxlength="2000" rows="3" required>{{ is_string(old('completion_description')) ? old('completion_description') : '' }}</textarea></div>
                <div class="field"><label for="completion-images-{{ $booking->id }}">صور قبل أو بعد العمل (اختيارية)</label><x-file-picker><input id="completion-images-{{ $booking->id }}" type="file" name="evidence[]" accept="image/jpeg,image/png,image/webp" multiple></x-file-picker><small>حتى 4 صور، 5 ميغابايت لكل صورة.</small></div>
                <p>بالإرسال بتأكد إنك أنجزت الخدمة لحجز هذا العميل.</p>
                <button class="btn btn-yellow" type="submit">إرسال تأكيد الإنجاز</button>
            </form>
        </dialog>
    @endif
@endif

@if ($isRequester && $booking->status === 'completed' && $booking->completed_at)
    <div class="booking-follow-up-actions">
        <button class="btn btn-yellow btn-sm" type="button" data-open-dialog="booking-rating-{{ $booking->id }}">تقييم الفني</button>
        @if (! $booking->refundRequest && $booking->objection_deadline?->isFuture())
            <button class="btn btn-outline btn-sm" type="button" data-open-dialog="booking-objection-{{ $booking->id }}">لدي اعتراض على الخدمة</button>
        @endif
    </div>
    @if ($booking->refundRequest)
        <p class="muted">{{ $booking->refundRequest->status === 'pending' ? 'الاعتراض قيد المراجعة.' : 'تمت مراجعة الاعتراض.' }}</p>
    @endif
@endif
