<dialog class="hub-dialog" id="suggestion-form" aria-labelledby="suggestion-form-title" @if ($errors->suggestion->any()) data-auto-dialog @endif>
    <div class="dialog-head">
        <h2 id="suggestion-form-title">اقتراح خدمة</h2>
        <button type="button" class="btn btn-outline btn-sm" data-close-dialog>إغلاق</button>
    </div>
    @if ($errors->suggestion->any())
        <ul class="notice" role="alert">
            @foreach ($errors->suggestion->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    @endif
    @if ($categories->isEmpty())
        <p>لا توجد أقسام متاحة لاختيارها.</p>
    @else
        <p class="muted">إذا كانت الخدمة غير موجودة في الدليل، اختر القسم المناسب وأرسل اقتراحك إلى الإدارة.</p>
        <form action="{{ route('vendor.service-suggestions.store') }}" method="POST">
            @csrf
            <div class="field">
                <label for="suggestion-category">القسم</label>
                <select id="suggestion-category" name="category_id" required>
                    <option value="">اختر القسم</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('category_id') === (int) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label for="suggestion-name">اسم الخدمة الجديدة</label><input id="suggestion-name" name="name" maxlength="255" value="{{ is_string(old('name')) ? old('name') : '' }}" required></div>
            @include('pages.vendor.partials.service-offer-fields', ['prefix' => 'suggestion'])
            <button type="submit" class="btn btn-yellow">إرسال الاقتراح</button>
        </form>
    @endif
</dialog>
