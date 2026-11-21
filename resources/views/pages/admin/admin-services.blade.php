@extends('layouts.app')
@section('title', 'كتالوج الخدمات | ServiceHub')
@section('page_id', 'admin-services')
@push('styles')
    <meta name="turbo-visit-control" content="reload">
@endpush
@section('content')
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <main class="admin-services-page" id="admin-services">
            <div class="dash-title services-heading">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>كتالوج الخدمات</h1>
                    <p class="muted">إدارة الخدمات وصورها وأقسامها وحالة ظهورها على الموقع.</p>
                </div>
                <button type="button" class="services-btn services-btn-primary" data-service-open="create">
                    + إضافة خدمة جديدة
                </button>
            </div>

            @if (session('success'))
                <div class="services-alert services-alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="services-alert services-alert-error" role="alert">{{ session('error') }}</div>
            @endif

            <section class="panel services-panel" aria-label="الخدمات الموجودة">
                <div class="services-panel-heading">
                    <div>
                        <h2>الخدمات الموجودة</h2>
                        <p class="muted">استعرض الخدمات وتحكّم بحالة كل خدمة.</p>
                    </div>
                    <span class="services-count">{{ isset($services) ? $services->total() : 0 }} خدمة</span>
                </div>

                <div class="services-grid">
                    @forelse (($services ?? collect()) as $service)
                        @php
                            $serviceImage = $service->image
                                ? (\Illuminate\Support\Str::startsWith($service->image, ['http://', 'https://', '/'])
                                    ? $service->image
                                    : asset('storage/' . $service->image))
                                : null;
                            $isDisabled = !$service->is_active;
                        @endphp
                        <article class="service-card">
                            <div class="service-card-image">
                            @if ($service->image)
                                <img
                                    src="{{ asset('storage/' . $service->image) }}"
                                    alt="صورة خدمة {{ $service->name }}"
                                    loading="lazy"
                                >
                            @endif
                            </div>
                            <div class="service-card-content">
                                <span class="service-category">{{ $service->category->name ?? 'بدون قسم' }}</span>
                                <h3>{{ $service->name }}</h3>
                                <p class="service-description">{{ $service->description ?: 'لا يوجد وصف لهذه الخدمة.' }}</p>
                                <div class="service-card-actions">
                                    <button type="button" class="services-btn services-btn-edit"
                                        data-service-open="edit"
                                        data-id="{{ $service->id }}"
                                        data-name="{{ $service->name }}"
                                        data-description="{{ $service->description }}"
                                        data-category="{{ $service->category_id }}"
                                        data-price="{{ $service->base_price ?? '' }}">تعديل</button>
                                    <button type="button"
                                     class="services-btn services-btn-toggle {{ $isDisabled ? 'services-btn-deactivate' : 'services-btn-activate' }}"
                                     data-service-open="toggle"
                                     data-id="{{ $service->id }}"
                                     data-name="{{ $service->name }}"
                                     data-disabled="{{ $isDisabled ? '1' : '0' }}">
                                    
                                     {{ $isDisabled ? 'تفعيل' : 'تعطيل' }}
                                    </button>
                                    <button type="button" class="services-btn services-btn-delete"
                                        data-service-open="delete" data-id="{{ $service->id }}"
                                        data-name="{{ $service->name }}">حذف</button>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="services-empty">
                            <h3>لا توجد خدمات لعرضها</h3>
                            <p class="muted">يمكنك إضافة أول خدمة من الزر الموجود أعلى الصفحة.</p>
                            <button type="button" class="services-btn services-btn-primary" data-service-open="create">إضافة خدمة</button>
                        </div>
                    @endforelse
                </div>

                @if (isset($services) && method_exists($services, 'links'))
                    <div class="services-pagination" data-turbo="false">
                        {{ $services->withQueryString()->links() }}
                    </div>
                @endif
            </section>
        </main>
    </div>

    <div class="service-modal-backdrop" id="service-modal-create" hidden>
        <section class="service-modal" role="dialog" aria-modal="true" aria-labelledby="service-create-title">
            <div class="service-modal-header">
                <h2 id="service-create-title">إضافة خدمة جديدة</h2>
                <button type="button" class="service-modal-close" data-service-close aria-label="إغلاق">&times;</button>
            </div>
            <form id="service-create-form" class="service-modal-form" action="{{ route('admin.addServices') }}" method="post" enctype="multipart/form-data">
              @csrf
            <label>اسم الخدمة <input name="name" type="text" placeholder="أدخل اسم الخدمة" required></label>
                <label>القسم
                    <select name="category_id" required>
                        <option value="">اختر القسم</option>
                        @foreach (($categories ?? collect()) as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>الوصف <textarea name="description" rows="4" placeholder="اكتب وصف الخدمة"></textarea></label>
                <label>صورة الخدمة <x-file-picker><input name="image" type="file" accept="image/*"></x-file-picker></label>
                <div class="service-modal-actions">
                    <button type="button" class="services-btn services-btn-neutral" data-service-close>إلغاء</button>
                    <button type="submit" class="services-btn services-btn-primary">حفظ الخدمة</button>
                </div>
            </form>
        </section>
    </div>

    <div class="service-modal-backdrop" id="service-modal-edit" hidden>
        <section class="service-modal" role="dialog" aria-modal="true" aria-labelledby="service-edit-title">
            <div class="service-modal-header">
                <h2 id="service-edit-title">تعديل الخدمة</h2>
                <button type="button" class="service-modal-close" data-service-close aria-label="إغلاق">&times;</button>
            </div>
            <form id="service-edit-form" class="service-modal-form"  method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="service_id" id="edit-service-id">
                <label>اسم الخدمة <input id="edit-service-name" name="name" type="text" required></label>
                <label>القسم
                    <select id="edit-service-category" name="category_id" required>
                        <option value="">اختر القسم</option>
                        @foreach (($categories ?? collect()) as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>الوصف <textarea id="edit-service-description" name="description" rows="4"></textarea></label>
                <label>استبدال الصورة (اختياري) <x-file-picker><input name="image" type="file" accept="image/*"></x-file-picker></label>
                <div class="service-modal-actions">
                    <button type="button" class="services-btn services-btn-neutral" data-service-close>إلغاء</button>
                    <button type="submit" class="services-btn services-btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </section>
    </div>

    <div class="service-modal-backdrop" id="service-modal-toggle" hidden>
        <section class="service-modal service-modal-small" role="dialog" aria-modal="true" aria-labelledby="service-toggle-title">
            <div class="service-modal-header">
                <h2 id="service-toggle-title">تغيير حالة الخدمة</h2>
                <button type="button" class="service-modal-close" data-service-close aria-label="إغلاق">&times;</button>
            </div>
            <p id="service-toggle-message"></p>
            <form id="service-toggle-form" method="post">
                @csrf
                @method('PUt')
                <input type="hidden" name="service_id" id="toggle-service-id">
                <div class="service-modal-actions">
                    <button type="button" class="services-btn services-btn-neutral" data-service-close>إلغاء</button>
                    <button type="submit" class="services-btn services-btn-primary" id="service-toggle-confirm">تأكيد</button>
                </div>
            </form>
        </section>
    </div>

    <div class="service-modal-backdrop" id="service-modal-delete" hidden>
        <section class="service-modal service-modal-small" role="dialog" aria-modal="true" aria-labelledby="service-delete-title">
            <div class="service-modal-header">
                <h2 id="service-delete-title">تأكيد حذف الخدمة</h2>
                <button type="button" class="service-modal-close" data-service-close aria-label="إغلاق">&times;</button>
            </div>
            <p id="service-delete-message"></p>
            <form id="service-delete-form" method="post">
                @csrf
                @method("DELETE")
                <input type="hidden" name="service_id" id="delete-service-id">
                <div class="service-modal-actions">
                    <button type="button" class="services-btn services-btn-neutral" data-service-close>إلغاء</button>
                    <button type="submit" class="services-btn services-btn-danger">حذف الخدمة</button>
                </div>
            </form>
        </section>
    </div>

    
    <script>
        (() => {
            const modalIds = ['create', 'edit', 'toggle', 'delete'];
            let lastTrigger = null;
            const closeModals = () => {
                modalIds.forEach(type => {
                    const modal = document.getElementById('service-modal-' + type);
                    if (modal) modal.hidden = true;
                });
                document.body.style.overflow = '';
                if (lastTrigger) lastTrigger.focus();
            };
            document.querySelectorAll('[data-service-open]').forEach(button => {
                button.addEventListener('click', () => {
                    const type = button.dataset.serviceOpen;
                    const modal = document.getElementById('service-modal-' + type);
                    if (!modal) return;
                    lastTrigger = button;
                    if (type === 'edit') {
                        document.getElementById('edit-service-id').value = button.dataset.id || '';
                        document.getElementById('edit-service-name').value = button.dataset.name || '';
                        document.getElementById('edit-service-description').value = button.dataset.description || '';
                        document.getElementById('edit-service-category').value = button.dataset.category || '';
                        const form = document.getElementById('service-edit-form');
                        form.action = @json(route('admin.editServices', ['service' => '__SERVICE_ID__']))
                       .replace('__SERVICE_ID__', encodeURIComponent(button.dataset.id));
                    }
                    if (type === 'toggle') {
                        const activating = button.dataset.disabled === '1';
                        document.getElementById('toggle-service-id').value = button.dataset.id || '';
                        document.getElementById('service-toggle-title').textContent = activating ? 'تفعيل الخدمة' : 'تعطيل الخدمة';
                        document.getElementById('service-toggle-message').textContent = `هل أنت متأكد من ${activating ? 'تفعيل' : 'تعطيل'} خدمة «${button.dataset.name || ''}»؟`;
                        document.getElementById('service-toggle-confirm').textContent = activating ? 'تأكيد التفعيل' : 'تأكيد التعطيل';
                        const form = document.getElementById('service-toggle-form');

                        form.action = @json(route('admin.toggleServices', ['service' => '__SERVICE_ID__']))
                       .replace('__SERVICE_ID__', encodeURIComponent(button.dataset.id));
                    }
                    if (type === 'delete') {
                        document.getElementById('delete-service-id').value = button.dataset.id || '';
                        document.getElementById('service-delete-message').textContent = `هل أنت متأكد من حذف خدمة «${button.dataset.name || ''}»؟`;
                        const form = document.getElementById('service-delete-form');
                        form.action = @json(route('admin.deleteServices', ['service' => '__SERVICE_ID__']))
                       .replace('__SERVICE_ID__', encodeURIComponent(button.dataset.id));
                    }
                    modal.hidden = false;
                    document.body.style.overflow = 'hidden';
                    modal.querySelector('[data-service-close]')?.focus();
                });
            });
            document.querySelectorAll('[data-service-close]').forEach(button => button.addEventListener('click', closeModals));
            document.querySelectorAll('.service-modal-backdrop').forEach(backdrop => {
                backdrop.addEventListener('click', event => { if (event.target === backdrop) closeModals(); });
            });
            document.querySelectorAll('.service-modal-backdrop').forEach(modal => modal.addEventListener('keydown', event => {
                if (event.key === 'Escape' && modalIds.some(type => !document.getElementById('service-modal-' + type)?.hidden)) closeModals();
            }));
        })();
    </script>
    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
