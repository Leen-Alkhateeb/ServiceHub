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
        <form action="{{ route('vendor.service-requests.store') }}" method="POST">
            @csrf
            <div class="field">
                <label for="offer-service">الخدمة</label>
                <select id="offer-service" name="service_id" required>
                    <option value="">اختر من دليل الخدمات</option>
                    @foreach ($catalog->groupBy('category_name') as $category => $options)
                        <optgroup label="{{ $category }}">
                            @foreach ($options as $option)
                                <option value="{{ $option->id }}" @selected((string) $option->id === old('service_id'))>{{ $option->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            @include('pages.vendor.partials.service-offer-fields', ['prefix' => 'offer'])
            <button type="submit" class="btn btn-yellow">إضافة إلى الملف</button>
        </form>
    @endif
</dialog>
