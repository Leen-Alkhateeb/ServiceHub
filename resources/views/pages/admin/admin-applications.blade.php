
@extends('layouts.app')

@section('title', 'طلبات انضمام الفنيين | ServiceHub')
@section('page_id', 'admin-applications')

@section('content')
    @include('components.navbar')

    <div class="container layout" id="admin-applications">
        @include('components.sidebars.admin')

        <div>
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>طلبات انضمام الفنيين</h1>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="panel">
                <h3>مراجعة طلبات الانضمام</h3>
                <p class="muted">
                    مراجعة بيانات الفنيين وتصاريح العمل قبل اتخاذ قرار الموافقة أو الرفض.
                </p>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>الرقم</th>
                                <th>اسم المستخدم</th>
                                <th>نوع العمل</th>
                                <th>سنوات الخبرة</th>
                                <th>تصريح العمل</th>
                                <th>انتهاء التصريح</th>
                                <th>تاريخ التقديم</th>
                                <th>الحالة</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($applications as $application)
                                <tr>
                                    <td>{{ $applications->firstItem() + $loop->index }}</td>

                                    <td>
                                        <strong>
                                            {{ $application->user->first_name ?? '' }}
                                            {{ $application->user->last_name ?? '' }}
                                        </strong>
                                    </td>

                                    <td>{{ $application->work_type }}</td>

                                    <td>{{ $application->experience_years }}</td>

                                    <td>
                                        @if ($application->work_permit_path)
                                            <a href="{{ asset('storage/' . $application->work_permit_path) }}"
                                               target="_blank"
                                               rel="noopener noreferrer">
                                                عرض التصريح
                                            </a>
                                        @else
                                            <span class="muted">غير متوفر</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $application->permit_expiry_date
                                            ? \Carbon\Carbon::parse($application->permit_expiry_date)->format('Y-m-d')
                                            : '—' }}
                                    </td>

                                    <td>
                                        {{ $application->applied_at
                                            ? \Carbon\Carbon::parse($application->applied_at)->format('Y-m-d')
                                            : '—' }}
                                    </td>

                                    <td>
                                        @switch($application->application_status)
                                            @case('pending')
                                                <span class="pill yellow">قيد المراجعة</span>
                                                @break

                                            @case('approved')
                                                <span class="pill green">مقبول</span>
                                                @break

                                            @case('rejected')
                                                <span class="pill red">مرفوض</span>
                                                @break

                                            @default
                                                <span class="pill">
                                                    {{ $application->application_status }}
                                                </span>
                                        @endswitch
                                    </td>

                                    <td>
                                        @if ($application->application_status === 'pending')
                                            <div class="application-actions">
                                                <form action="{{ route('admin.admin-applications.approved' , $application->id) }}" method="post"
                                                onsubmit="return confirm('هل أنت متأكد من الموافقة على طلب انضمام هذا الفني؟');">
                                                    @csrf
                                                    @method("PUT")
                                                <button
                                                    type="submit"
                                                    class="btn btn-primary">
                                                    موافقة
                                                </button>
                                                </form>
                                                <form action="{{ route('admin.admin-applications.rejected' , $application->id) }}" method="post"
                                                 onsubmit="return confirm('هل أنت متأكد من رفض طلب انضمام هذا الفني؟');">
                                                    @csrf
                                                    @method("PUT")
                                                <button
                                                    type="submit"
                                                    class="btn btn-outline"
                                                    onsubmit=""
                                                    ">

                                                    رفض
                                                </button>
                                                </form>

                                            </div>
                                        @else
                                            <span class="muted">تمت المراجعة</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="muted" style="text-align:center;">
                                        لا توجد طلبات انضمام حاليًا.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($applications, 'links'))
                    <div class="pagination-wrap">
                        {{ $applications->onEachSide(1)->links('components.pager') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
