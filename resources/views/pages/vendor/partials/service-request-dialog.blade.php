<dialog class="hub-dialog" id="offer-form" aria-labelledby="offer-form-title" @if ($errors->offer->any()) data-auto-dialog @endif>
    <div class="dialog-head">
        <h2 id="offer-form-title">إضافة خدمة</h2>
        <button type="button" class="btn btn-outline btn-sm" data-close-dialog>إغلاق</button>
    </div>
    @if ($errors->offer->any())
        <ul class="notice" role="alert">
            @foreach ($errors->offer->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif
    @if ($catalog->isEmpty())
        <p>جميع الخدمات المعتمدة مضافة إلى ملفك.</p>
    @else
        <p class="muted">اختر خدمة معتمدة من الدليل وحدد تفاصيل عرضك. يظهر العرض فورًا على ملفك.</p>
        <form action="{{ route('vendor.service-requests.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <x-inline-choice name="service_id" label="الخدمة" placeholder="اختر من دليل الخدمات"
                :groups="$catalog->groupBy('category_name')" :selected="old('service_id')" />
            @include('pages.vendor.partials.service-offer-fields', ['prefix' => 'offer'])
            <button type="submit" class="btn btn-yellow">إضافة إلى الملف</button>
        </form>
    @endif
</dialog>
