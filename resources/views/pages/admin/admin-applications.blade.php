
@extends('layouts.app')

@section('title', 'طلبات انضمام الفنيين | ServiceHub')
@section('page_id', 'admin-applications')

@section('content')
    @include('components.navbar')

    <div class="container layout" id="admin-applications">
        @include('components.sidebars.admin')

        <main class="applications-main admin-request-review">
            <div class="requests-heading">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>طلبات انضمام الفنيين</h1>
                    <p class="muted">راجع بيانات الفنيين وتصاريح العمل قبل الموافقة أو الرفض.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="admin-alert admin-alert-success" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="admin-alert admin-alert-error" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="admin-alert admin-alert-error" role="alert">
                    تأكد من صحة الفلاتر، وأن نهاية كل نطاق تساوي بدايته أو تأتي بعدها.
                </div>
            @endif

            <form class="panel hub-filters application-filters" method="GET" action="{{ route('admin.admin-applications') }}" aria-label="تصفية طلبات الانضمام">
                <div class="field">
                    <label for="applications-search">بحث</label>
                    <input id="applications-search" name="search" type="search" maxlength="100" value="{{ $filters['search'] ?? '' }}" placeholder="الاسم أو البريد أو النبذة">
                </div>
                <div class="field">
                    <label for="applications-status">حالة الطلب</label>
                    <select id="applications-status" name="status">
                        <option value="">كل الحالات</option>
                        @foreach (['pending' => 'قيد المراجعة', 'reviewed' => 'تمت مراجعتها', 'approved' => 'مقبولة', 'rejected' => 'مرفوضة'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="applications-work-type">المهنة</label>
                    <select id="applications-work-type" name="work_type">
                        <option value="">كل المهن</option>
                        @foreach ($workTypes as $workType)
                            <option value="{{ $workType }}" @selected(($filters['work_type'] ?? null) === $workType)>{{ $workType }}</option>
                        @endforeach
                    </select>
                </div>
                @foreach ([
                    'experience_from' => 'الخبرة من (سنوات)',
                    'experience_to' => 'الخبرة إلى (سنوات)',
                    'applied_from' => 'تاريخ التقديم من',
                    'applied_to' => 'تاريخ التقديم إلى',
                    'permit_from' => 'انتهاء التصريح من',
                    'permit_to' => 'انتهاء التصريح إلى',
                ] as $key => $label)
                    <div class="field">
                        <label for="applications-{{ $key }}">{{ $label }}</label>
                        <input id="applications-{{ $key }}" name="{{ $key }}" value="{{ $filters[$key] ?? '' }}"
                            type="{{ str_starts_with($key, 'experience') ? 'number' : 'date' }}" @if (str_starts_with($key, 'experience')) min="0" @endif>
                    </div>
                @endforeach
                <div class="filter-actions">
                    <button class="btn btn-yellow" type="submit">تصفية</button>
                    <a class="btn btn-outline" href="{{ route('admin.admin-applications') }}">مسح</a>
                </div>
            </form>

            <div class="requests-list">
                @forelse ($applications as $application)
                    @php
                        [$statusLabel, $statusTone] = match ($application->application_status) {
                            'pending' => ['قيد المراجعة', 'status-pending'],
                            'approved' => ['مقبول', 'status-active'],
                            'rejected' => ['مرفوض', 'status-rejected'],
                            default => ['حالة غير معروفة', ''],
                        };
                    @endphp

                    <article class="panel request-card">
                        <div class="request-top">
                            <div>
                                <h2>{{ $application->user?->first_name }} {{ $application->user?->last_name }}</h2>
                                <p class="muted">{{ $application->work_type }}</p>
                            </div>
                            <span class="request-status {{ $statusTone }}">{{ $statusLabel }}</span>
                        </div>

                        @if (filled($application->bio))
                            <p class="request-description">{{ $application->bio }}</p>
                        @endif

                        <div class="request-details">
                            <span>الخبرة: {{ $application->experience_years }} سنة</span>
                            <span>تاريخ التقديم: <bdi>{{ $application->applied_at ? \Carbon\Carbon::parse($application->applied_at)->toDateString() : '—' }}</bdi></span>
                            <span>انتهاء التصريح: <bdi>{{ $application->permit_expiry_date ? \Carbon\Carbon::parse($application->permit_expiry_date)->toDateString() : '—' }}</bdi></span>
                            @if ($application->work_permit_path && str_starts_with($application->work_permit_path, 'vendor-permits/') && \Illuminate\Support\Facades\Storage::disk('local')->exists($application->work_permit_path))
                                <a href="{{ route('admin.applications.permit', $application) }}" target="_blank" rel="noopener noreferrer">عرض التصريح</a>
                            @else
                                <span class="muted">التصريح غير متوفر</span>
                            @endif
                        </div>

                        @if ($application->application_status === 'pending')
                            <div class="request-actions">
                                <form action="{{ route('admin.admin-applications.approved', $application) }}" method="POST" data-turbo="false"
                                    onsubmit="return confirm('هل أنت متأكد من الموافقة على طلب انضمام هذا الفني؟');">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn request-approve">موافقة</button>
                                </form>
                                <form action="{{ route('admin.admin-applications.rejected', $application) }}" method="POST" data-turbo="false"
                                    onsubmit="return confirm('هل أنت متأكد من رفض طلب انضمام هذا الفني؟');">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn request-reject">رفض</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="panel requests-empty">{{ $filters ? 'لا توجد طلبات مطابقة للفلاتر.' : 'لا توجد طلبات انضمام حاليًا.' }}</div>
                @endforelse
            </div>

            @if ($applications->hasPages())
                <div class="requests-pagination">
                    {{ $applications->onEachSide(1)->links('components.pager', ['showArrows' => false]) }}
                </div>
            @endif
        </main>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
