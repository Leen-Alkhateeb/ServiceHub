<div class="moderation-actions">
    <button class="btn btn-outline btn-sm" type="button" data-open-dialog="edit-project">تعديل المشروع</button>
    <x-archive-button :action="route('vendor.portfolio.destroy', $project)" message="هل تريد حذف هذا المشروع وصوره من معرض أعمالك؟" />
</div>
@if ($images->isNotEmpty())
    <details class="project-image-management">
        <summary>إدارة الصور</summary>
        @foreach ($images as $photo)
            <div class="item-row">
                @if ($photoUrl = $photo->displayUrl())
                    <img src="{{ $photoUrl }}" alt="" width="72" height="54" loading="lazy">
                @endif
                <span>صورة {{ $images->firstItem() + $loop->index }}</span>
                <x-archive-button :action="route('vendor.portfolio.image.destroy', $photo)" message="هل تريد حذف هذه الصورة من معرض المشروع؟" />
            </div>
        @endforeach
    </details>
@endif
<dialog class="hub-dialog" id="edit-project" aria-labelledby="edit-project-title" @if ($errors->portfolioEdit->any()) data-auto-dialog @endif>
    <div class="dialog-head">
        <h2 id="edit-project-title">تعديل المشروع</h2>
        <button class="btn btn-outline btn-sm" type="button" data-close-dialog>إغلاق</button>
    </div>
    @if ($errors->portfolioEdit->any())
        <ul class="notice" role="alert">@foreach ($errors->portfolioEdit->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <form method="POST" action="{{ route('vendor.portfolio.update', $project) }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="field"><label for="edit-project-name">اسم المشروع</label><input id="edit-project-name" name="name" value="{{ old('name', $project->name) }}" maxlength="255" required></div>
        <div class="field"><label for="edit-project-description">الوصف</label><textarea id="edit-project-description" name="description" rows="4" maxlength="5000" required>{{ old('description', $project->description) }}</textarea></div>
        <div class="field"><label for="edit-project-price">قيمة المشروع (د.أ)</label><input id="edit-project-price" name="project_price" value="{{ old('project_price', $project->project_price) }}" type="number" min="0" max="99999999.99" step="0.01"></div>
        <div class="field"><label for="edit-project-date">تاريخ الإنجاز</label><input id="edit-project-date" name="completion_date" value="{{ old('completion_date', $project->completion_date) }}" type="date" max="{{ now()->toDateString() }}"></div>
        <div class="field"><label for="edit-project-images">إضافة صور</label><input id="edit-project-images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple><small>حتى 8 صور معروضة لكل مشروع.</small></div>
        <button class="btn btn-yellow" type="submit">حفظ التعديلات</button>
    </form>
</dialog>
