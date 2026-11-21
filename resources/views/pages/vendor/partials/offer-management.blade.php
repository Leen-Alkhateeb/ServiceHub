@if ($offer->status !== 'rejected')
    <button class="btn btn-outline btn-sm" type="button" data-open-dialog="edit-offer-{{ $offer->id }}">تعديل العرض</button>
    @php
        $showOfferErrors = (int) old('editing_offer') === (int) $offer->id && $errors->offerEdit->any();
    @endphp
    <dialog class="hub-dialog offer-edit-dialog" id="edit-offer-{{ $offer->id }}" aria-labelledby="edit-offer-title-{{ $offer->id }}" @if ($showOfferErrors) data-auto-dialog @endif>
        <div class="dialog-head">
            <h2 id="edit-offer-title-{{ $offer->id }}">تعديل العرض</h2>
            <button class="btn btn-outline btn-sm" type="button" data-close-dialog>إغلاق</button>
        </div>
        @if ($showOfferErrors)
            <ul class="notice" role="alert">@foreach ($errors->offerEdit->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        <form class="offer-edit-form" method="POST" enctype="multipart/form-data" action="{{ route('vendor.service-requests.update', $offer->id) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="editing_offer" value="{{ $offer->id }}">
            <div class="offer-fields">
                <div class="field">
                    <label for="edit-offer-price-{{ $offer->id }}">السعر (د.أ)</label>
                    <input id="edit-offer-price-{{ $offer->id }}" name="price" type="number" min="0.01" max="99999999.99" step="0.01" value="{{ $showOfferErrors ? old('price') : $offer->price }}" required>
                </div>
                <div class="field">
                    <label for="edit-offer-duration-{{ $offer->id }}">المدة التقريبية (دقيقة)</label>
                    <input id="edit-offer-duration-{{ $offer->id }}" name="duration_minutes" type="number" min="5" max="1440" value="{{ $showOfferErrors ? old('duration_minutes') : $offer->duration_minutes }}">
                </div>
            </div>
            <div class="field">
                <label for="edit-offer-description-{{ $offer->id }}">وصف العرض</label>
                <textarea id="edit-offer-description-{{ $offer->id }}" name="description" rows="4" maxlength="3000" required>{{ $showOfferErrors ? old('description') : $offer->description }}</textarea>
            </div>
            @include('pages.vendor.partials.offer-image-field', [
                'imageFieldId' => 'edit-offer-image-'.$offer->id,
                'imageFieldLabel' => 'استبدال الصورة (اختياري)',
                'currentImageUrl' => $offer->image_url,
            ])
            <div class="offer-edit-actions">
                <button class="btn btn-yellow" type="submit">حفظ التعديلات</button>
            </div>
        </form>
    </dialog>
@endif
<x-archive-button :action="route('vendor.service-requests.destroy', $offer->id)" message="هل تريد حذف هذا العرض من ملفك؟ تبقى الحجوزات السابقة محفوظة." />
