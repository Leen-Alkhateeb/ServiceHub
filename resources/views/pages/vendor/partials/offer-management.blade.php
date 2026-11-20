@if ($offer->status !== 'rejected')
    <button class="btn btn-outline btn-sm" type="button" data-open-dialog="edit-offer-{{ $offer->id }}">تعديل العرض</button>
    @php
        $showOfferErrors = (int) old('editing_offer') === (int) $offer->id && $errors->offerEdit->any();
    @endphp
    <dialog class="hub-dialog" id="edit-offer-{{ $offer->id }}" aria-labelledby="edit-offer-title-{{ $offer->id }}" @if ($showOfferErrors) data-auto-dialog @endif>
        <div class="dialog-head">
            <h2 id="edit-offer-title-{{ $offer->id }}">تعديل العرض</h2>
            <button class="btn btn-outline btn-sm" type="button" data-close-dialog>إغلاق</button>
        </div>
        @if ($showOfferErrors)
            <ul class="notice" role="alert">@foreach ($errors->offerEdit->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        <form method="POST" action="{{ route('vendor.service-requests.update', $offer->id) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="editing_offer" value="{{ $offer->id }}">
            <div class="offer-fields">
                <div class="field"><label>السعر (د.أ)<input name="price" type="number" min="0.01" max="99999999.99" step="0.01" value="{{ $showOfferErrors ? old('price') : $offer->price }}" required></label></div>
                <div class="field"><label>المدة التقريبية (دقيقة)<input name="duration_minutes" type="number" min="5" max="1440" value="{{ $showOfferErrors ? old('duration_minutes') : $offer->duration_minutes }}"></label></div>
            </div>
            <div class="field"><label>وصف العرض<textarea name="description" rows="4" maxlength="3000" required>{{ $showOfferErrors ? old('description') : $offer->description }}</textarea></label></div>
            <button class="btn btn-yellow" type="submit">حفظ التعديلات</button>
        </form>
    </dialog>
@endif
<x-archive-button :action="route('vendor.service-requests.destroy', $offer->id)" message="هل تريد حذف هذا العرض من ملفك؟ تبقى الحجوزات السابقة محفوظة." />
