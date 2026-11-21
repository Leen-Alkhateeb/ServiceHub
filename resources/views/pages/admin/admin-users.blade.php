@extends('layouts.app')

@section('title', 'المستخدمون | ServiceHub')
@section('page_id', 'admin-users')

@section('content')

    @include('components.navbar')

    <div class="container layout">

        @include('components.sidebars.admin')

        <div>
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>المستخدمون</h1>
                </div>

                <span class="demo">بيانات تجريبية</span>
            </div>


            @if (session('success'))
                <div class="admin-alert admin-alert-success" role="status">
                    <span class="admin-alert-icon">✓</span>
                    <div>
                        <strong>تمت العملية بنجاح</strong>
                        <p>{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="admin-alert admin-alert-error" role="alert">
                    <span class="admin-alert-icon">!</span>
                    <div>
                        <strong>حدث خطأ</strong>
                        <p>{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="admin-alert admin-alert-error" role="alert">
                    <span class="admin-alert-icon">!</span>
                    <div>
                        <strong>يرجى مراجعة الأخطاء التالية</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif


            <div class="panel">
                <h3>المستخدمون</h3>
                <p>إدارة حسابات العملاء والفنيين وحالة الحساب.</p>

                <form method="GET" action="{{ url()->current() }}" class="admin-users-filters">

                    <div class="admin-filter-field admin-search-field">
                        <label for="userSearch">البحث عن مستخدم</label>

                        <input type="search" id="userSearch" name="search" value="{{ request('search') }}"
                            placeholder="ابحث بالاسم أو البريد الإلكتروني...">
                    </div>

                    <div class="admin-filter-field">
                        <label for="roleFilter">الدور</label>

                        <select id="roleFilter" name="role">
                            <option value="">جميع الأدوار</option>
                            <option value="customer" @selected(request('role') === 'customer')>
                                عميل
                            </option>
                            <option value="vendor" @selected(request('role') === 'vendor')>
                                فني
                            </option>
                            <option value="admin" @selected(request('role') === 'admin')>
                                أدمن
                            </option>
                        </select>
                    </div>

                    <div class="admin-filter-field">
                        <label for="genderFilter">الجنس</label>

                        <select id="genderFilter" name="gender">
                            <option value="">جميع الأجناس</option>
                            <option value="male" @selected(request('gender') === 'male')>
                                ذكر
                            </option>
                            <option value="female" @selected(request('gender') === 'female')>
                                أنثى
                            </option>
                        </select>
                    </div>

                    <div class="admin-filter-field">
                        <label for="statusFilter">الحالة</label>

                        <select id="statusFilter" name="status">
                            <option value="">جميع الحالات</option>
                            <option value="active" @selected(request('status') === 'active')>
                                فعال
                            </option>
                            <option value="inactive" @selected(request('status') === 'inactive')>
                                غير فعال
                            </option>
                        </select>
                    </div>

                    <div class="admin-filter-actions">
                        <button type="submit" class="btn btn-primary">
                            بحث
                        </button>

                        <a href="{{ url()->current() }}" class="btn btn-outline">
                            إعادة ضبط
                        </a>
                    </div>

                </form>

                <div class="table-wrap">
                    <table class="admin-users-table">
                        <thead>
                            <tr>
                                <th>الرقم</th>
                                <th>الاسم</th>
                                <th>الحالة</th>
                                <th>الجنس</th>
                                <th>الإيميل</th>
                                <th>الصلاحيات</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>{{ $users->firstItem() + $loop->index }}</td>

                                    <td>
                                        <a href="{{ route('users.profile.show', $user) }}"> {{ $user->first_name }}
                                            {{ $user->last_name }}</a>
                                    </td>

                                    <td>
                                        <span
                                            class="user-status {{ $user->status === 'active' ? 'status-active' : 'status-inactive' }}">
                                            {{ $user->status === 'active' ? 'فعال' : 'غير فعال' }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $user->gender === 'male' ? 'ذكر' : 'أنثى' }}
                                    </td>

                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->role === "customer" ? 'عميل' : ($user->role === 'admin' ? 'ادمين' : 'فني') }}
                                    </td>
                                    <td>
                                        <div class="user-actions">

                                            <!-- Activate / Deactivate Button -->
                                            <button type="button"
                                                class="action-btn {{ $user->status === 'active' ? 'deactivate-btn' : 'activate-btn' }}"
                                                aria-label="{{ $user->status === 'active' ? 'تعطيل المستخدم' : 'تفعيل المستخدم' }}"
                                                title="{{ $user->status === 'active' ? 'تعطيل المستخدم' : 'تفعيل المستخدم' }}"
                                                data-id="{{ $user->id }}"
                                                data-name="{{ $user->first_name }} {{ $user->last_name }}"
                                                data-status="{{ $user->status }}" data-user-status>
                                                @if ($user->status === 'active')
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" width="20" height="20"
                                                        aria-hidden="true">
                                                        <path d="M18.36 6.64a9 9 0 1 1-12.73 0" />
                                                        <path d="M12 2v10" />
                                                        <path d="M4 4l16 16" />
                                                    </svg>
                                                @else
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round" width="20" height="20"
                                                        aria-hidden="true">
                                                        <path d="M18.36 6.64a9 9 0 1 1-12.73 0" />
                                                        <path d="M12 2v10" />
                                                    </svg>
                                                @endif
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" class="action-btn delete-btn" aria-label="حذف المستخدم"
                                                title="حذف المستخدم" data-id="{{ $user->id }}"
                                                data-name="{{ $user->first_name }} {{ $user->last_name }}"
                                                data-user-delete>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18" />
                                                    <path d="M8 6V4h8v2" />
                                                    <path d="M19 6l-1 14H6L5 6" />
                                                    <path d="M10 11v5M14 11v5" />
                                                </svg>
                                            </button>

                                        </div>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="7" class="empty-users">
                                        لا يوجد مستخدمون حاليًا
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $users->onEachSide(1)->links('components.pager') }}


                <div id="statusUserModal" class="user-modal" role="dialog" aria-modal="true"
                    aria-labelledby="statusUserTitle" hidden>
                    <div class="user-modal-content">
                        <div class="user-modal-header">
                            <h3 id="statusUserTitle">تأكيد تغيير حالة المستخدم</h3>
                            <button type="button" class="modal-close" aria-label="إغلاق"
                                data-user-close="statusUserModal">&times;</button>
                        </div>
                        <p>هل أنت متأكد من <strong id="statusUserAction"></strong> المستخدم
                            <strong id="statusUserName"></strong>؟
                        </p>
                        <form id="statusUserForm" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" id="statusUserValue">
                            <input type="hidden" name="user_id" id="statusUserId">
                            <div class="modal-actions">
                                <button type="button" class="btn btn-outline"
                                    data-user-close="statusUserModal">إلغاء</button>
                                <button type="submit" id="confirmStatusUserButton" class="btn btn-primary">
                                    تأكيد
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div id="deleteUserModal" class="user-modal" role="dialog" aria-modal="true"
                    aria-labelledby="deleteUserTitle" hidden>

                    <div class="user-modal-content">

                        <div class="user-modal-header">
                            <h3 id="deleteUserTitle">تأكيد حذف المستخدم</h3>

                            <button type="button" class="modal-close" aria-label="إغلاق"
                                data-user-close="deleteUserModal">
                                &times;
                            </button>
                        </div>

                        <p>
                            هل أنت متأكد من رغبتك في حذف المستخدم
                            <strong id="deleteUserName"></strong>؟
                        </p>

                        <form id="deleteUserForm" method="POST">
                            @csrf
                            @method('DELETE')

                            <div class="modal-actions">
                                <button type="button" class="btn btn-outline" data-user-close="deleteUserModal">
                                    إلغاء
                                </button>

                                <button type="submit" class="btn btn-danger">
                                    تأكيد الحذف
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')

    <script>
        (() => {
            let previousModalFocus = null;

            function showUserModal(modalId) {
                const modal = document.getElementById(modalId);

                previousModalFocus = document.activeElement;
                modal.hidden = false;

                document.body.style.overflow = 'hidden';

                const closeButton = modal.querySelector('.modal-close');

                closeButton?.focus();
            }

            function openStatusModal(button) {
                const isActive = button.dataset.status === 'active';
                const action = isActive ? 'تعطيل' : 'تفعيل';
                document.getElementById('statusUserTitle').textContent = 'تأكيد ' + action + ' المستخدم';
                document.getElementById('statusUserAction').textContent = action;
                document.getElementById('statusUserName').textContent = button.dataset.name;
                document.getElementById('statusUserId').value = button.dataset.id;
                document.getElementById('statusUserValue').value = isActive ? 'inactive' : 'active';
                document.getElementById('confirmStatusUserButton').textContent = 'تأكيد ' + action;
                const form = document.getElementById('statusUserForm');

                form.action = @json(route('admin.users.toggle-status', ['user' => '__USER_ID__']))
                    .replace('__USER_ID__', encodeURIComponent(button.dataset.id));
                showUserModal('statusUserModal');
            }

            function openDeleteModal(button) {
                const id = button.dataset.id;

                document.getElementById('deleteUserName').textContent =
                    button.dataset.name;

                const form = document.getElementById('deleteUserForm');

                form.action = @json(route('admin.users.destroy', ['user' => '__USER_ID__']))
                    .replace('__USER_ID__', encodeURIComponent(id));


                showUserModal('deleteUserModal');
            }

            function closeUserModal(modalId) {
                const modal = document.getElementById(modalId);

                modal.hidden = true;
                document.body.style.overflow = '';

                previousModalFocus?.focus();
            }

            document.querySelectorAll('[data-user-status]').forEach(button => button.addEventListener('click', () => openStatusModal(button)));
            document.querySelectorAll('[data-user-delete]').forEach(button => button.addEventListener('click', () => openDeleteModal(button)));
            document.querySelectorAll('[data-user-close]').forEach(button => button.addEventListener('click', () => closeUserModal(button.dataset.userClose)));

            // Close when clicking outside the popup
            document.querySelectorAll('.user-modal').forEach(modal => {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeUserModal(modal.id);
                    }
                });
            });

            // Close with Escape
            document.querySelectorAll('.user-modal').forEach(modal => modal.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    document.querySelectorAll('.user-modal').forEach(modal => {
                        if (!modal.hidden) {
                            closeUserModal(modal.id);
                        }
                    });
                }
            }));
        })();
    </script>

@endsection
