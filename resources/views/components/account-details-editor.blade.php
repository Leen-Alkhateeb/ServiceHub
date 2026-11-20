<button class="btn btn-outline btn-sm" type="button" data-open-dialog="account-details-form">تعديل البيانات</button>
@if (session('account_status'))<p role="status">{{ session('account_status') }}</p>@endif
<dialog class="hub-dialog" id="account-details-form" aria-labelledby="account-details-title" @if ($errors->accountDetails->any()) data-auto-dialog @endif>
    <div class="dialog-head">
        <h2 id="account-details-title">تعديل بيانات الملف</h2>
        <button class="btn btn-outline btn-sm" type="button" data-close-dialog>إغلاق</button>
    </div>
    @if ($errors->accountDetails->any())
        <ul class="notice" role="alert">@foreach ($errors->accountDetails->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <form method="POST" action="{{ route('account.details.update') }}">
        @csrf
        @method('PATCH')
        @php
            $fields = ['first_name' => ['الاسم الأول', 255], 'last_name' => ['اسم العائلة', 255],
                'phone' => ['رقم الهاتف', 32], 'location' => ['الموقع', 255]];
            if (isset($vendorDetails)) {
                $fields['work_type'] = ['المهنة', 255];
            }
        @endphp
        <div class="offer-fields">
            @foreach ($fields as $name => [$label, $length])
                <div class="field">
                    <label for="account-{{ $name }}">{{ $label }}</label>
                    <input id="account-{{ $name }}" name="{{ $name }}" maxlength="{{ $length }}" value="{{ is_scalar(old($name)) ? old($name) : ($name === 'work_type' ? $vendorDetails->work_type : auth()->user()->$name) }}" required>
                </div>
            @endforeach
        </div>
        @isset($vendorDetails)
            <div class="field"><label for="account-experience">سنوات الخبرة</label><input id="account-experience" name="experience_years" value="{{ is_scalar(old('experience_years')) ? old('experience_years') : $vendorDetails->experience_years }}" type="number" min="0" max="80" required></div>
            <div class="field"><label for="account-bio">نبذة</label><textarea id="account-bio" name="bio" maxlength="2000" rows="4" required>{{ is_string(old('bio')) ? old('bio') : $vendorDetails->bio }}</textarea></div>
        @endisset
        <button class="btn btn-yellow" type="submit">حفظ البيانات</button>
    </form>
</dialog>
