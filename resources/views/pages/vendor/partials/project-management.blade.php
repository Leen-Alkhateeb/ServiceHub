<div class="moderation-actions">
    <button class="btn btn-outline btn-sm" type="button" data-open-dialog="edit-project">تعديل المشروع</button>
    <x-archive-button :action="route('vendor.portfolio.destroy', $project)" message="هل تريد حذف هذا المشروع وصوره من معرض أعمالك؟" />
</div>
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
        <div class="field"><label for="edit-project-date">تاريخ الإنجاز</label><input id="edit-project-date" name="completion_date" value="{{ old('completion_date', $project->completion_date) }}" type="date" min="1900-01-01" max="{{ now()->toDateString() }}"></div>
        @include('pages.vendor.partials.project-image-fields', ['projectImages' => $editableImages, 'requiresImage' => false])
        <button class="btn btn-yellow" type="submit">حفظ التعديلات</button>
    </form>
</dialog>
