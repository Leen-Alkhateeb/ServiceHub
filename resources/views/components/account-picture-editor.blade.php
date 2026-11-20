<button class="btn btn-outline btn-sm" type="button" data-open-dialog="picture-form">تغيير الصورة الشخصية</button>
@if (auth()->user()->profile_image)
    <x-archive-button :action="route('account.picture.destroy')" message="هل تريد حذف صورتك الشخصية؟" label="حذف الصورة" />
@endif
@if (session('picture_status'))<p role="status">{{ session('picture_status') }}</p>@endif
<dialog class="hub-dialog" id="picture-form" aria-labelledby="picture-title" @if ($errors->picture->any()) data-auto-dialog @endif>
    <div class="dialog-head">
        <h2 id="picture-title">تغيير الصورة الشخصية</h2>
        <button type="button" class="btn btn-outline btn-sm" data-close-dialog>إغلاق</button>
    </div>
    @if ($errors->picture->any())
        <ul class="notice" role="alert">@foreach ($errors->picture->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <form method="POST" action="{{ route('account.picture.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="field">
            <label for="account-picture">اختر صورة</label>
            <input id="account-picture" type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" required>
            <small class="muted">JPG أو PNG أو WebP، حتى 5 ميغابايت. يُفضّل استخدام صورة مربّعة.</small>
        </div>
        <button type="submit" class="btn btn-yellow">حفظ الصورة</button>
    </form>
</dialog>
