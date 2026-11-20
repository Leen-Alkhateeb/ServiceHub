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


            {{-- Success Message --}}
            @if (session('success'))
                <div class="admin-alert admin-alert-success" role="status">
                    <span class="admin-alert-icon">✓</span>
                    <div>
                        <strong>تمت العملية بنجاح</strong>
                        <p>{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            {{-- Error Message --}}
            @if (session('error'))
                <div class="admin-alert admin-alert-error" role="alert">
                    <span class="admin-alert-icon">!</span>
                    <div>
                        <strong>حدث خطأ</strong>
                        <p>{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            {{-- Laravel Validation Errors --}}
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
                                    <td>{{ ++$count }}</td>

                                    <td>
                                        {{ $user->first_name }}
                                        {{ $user->last_name }}
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
                                    <td>{{ $user->role }}</td>
                                    <td>
                                        <div class="user-actions">

                                            <!-- Delete Button -->
                                            <button type="button" class="action-btn delete-btn" aria-label="حذف المستخدم"
                                                title="حذف المستخدم" data-id="{{ $user->id }}"
                                                data-name="{{ $user->first_name }} {{ $user->last_name }}"
                                                onclick="openDeleteModal(this)">
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
                                    <td colspan="6" class="empty-users">
                                        لا يوجد مستخدمون حاليًا
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>


                <!-- Delete Modal -->
                <div id="deleteUserModal" class="user-modal" role="dialog" aria-modal="true"
                    aria-labelledby="deleteUserTitle" hidden>

                    <div class="user-modal-content">

                        <div class="user-modal-header">
                            <h3 id="deleteUserTitle">تأكيد حذف المستخدم</h3>

                            <button type="button" class="modal-close" aria-label="إغلاق"
                                onclick="closeUserModal('deleteUserModal')">
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
                                <button type="button" class="btn btn-outline" onclick="closeUserModal('deleteUserModal')">
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
        let previousModalFocus = null;

        function showUserModal(modalId) {
            const modal = document.getElementById(modalId);

            previousModalFocus = document.activeElement;
            modal.hidden = false;

            document.body.style.overflow = 'hidden';

            const closeButton = modal.querySelector('.modal-close');

            closeButton?.focus();
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

        // Close when clicking outside the popup
        document.querySelectorAll('.user-modal').forEach(modal => {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeUserModal(modal.id);
                }
            });
        });

        // Close with Escape
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.user-modal').forEach(modal => {
                    if (!modal.hidden) {
                        closeUserModal(modal.id);
                    }
                });
            }
        });


    </script>

@endsection