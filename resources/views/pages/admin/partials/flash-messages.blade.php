@if (session('success'))
    <div class="admin-alert admin-alert-success" role="status">
        <span class="admin-alert-icon" aria-hidden="true">✓</span>
        <div>
            <strong>تمت العملية بنجاح</strong>
            <p>{{ session('success') }}</p>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="admin-alert admin-alert-error" role="alert">
        <span class="admin-alert-icon" aria-hidden="true">!</span>
        <div>
            <strong>حدث خطأ</strong>
            <p>{{ session('error') }}</p>
        </div>
    </div>
@endif
