@extends('layouts.app')

@section('title', 'الأقسام | ServiceHub')
@section('page_id', 'admin-categories')

@push('styles')
    <link rel="stylesheet" href="/assets/admin-management.css">
@endpush

@section('content')
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <main class="dashboard-content admin-manage">
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>الأقسام</h1>
                    <p class="page-intro">إدارة أقسام الخدمات التي تظهر للعملاء في الموقع.</p>
                </div>
                <a href="{{ route('admin.categories.create') }}" class="btn btn-yellow">+ إضافة قسم جديد</a>
            </div>

            @include('pages.admin.partials.flash-messages')

            <form method="GET" action="{{ route('admin.categories') }}" class="panel hub-filters">
                <div class="field">
                    <label for="category-search">بحث باسم القسم</label>
                    <input id="category-search" name="q" value="{{ $search }}" maxlength="100" placeholder="مثال: سباكة">
                </div>
                <div class="filter-actions">
                    <button class="btn btn-yellow" type="submit">بحث</button>
                    <a class="btn btn-outline" href="{{ route('admin.categories') }}">مسح</a>
                </div>
            </form>

            <div class="panel">
                <div class="panel-head">
                    <h3>قائمة الأقسام</h3>
                    <span class="results-count">
                        @if ($search !== '')
                            {{ $categories->total() }} نتيجة من أصل {{ $totalCategories }} قسم
                        @else
                            {{ $totalCategories }} قسم نشط
                        @endif
                    </span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>الرقم</th>
                                <th>القسم</th>
                                <th>الخدمات النشطة</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($categories as $category)
                                <tr>
                                    <td>#{{ $category->id }}</td>
                                    <td>
                                        <div class="category-cell">
                                            <x-directory-photo :picture="$category->image" :trade="$category->name" kind="category" />
                                            <div>
                                                <span class="cell-title">{{ $category->name }}</span>
                                                <span class="cell-sub">{{ \Illuminate\Support\Str::limit($category->description, 60) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $category->services_count }}</td>
                                    <td><span class="pill green">نشط</span></td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline btn-sm">تعديل</a>
                                            <x-archive-button
                                                :action="route('admin.categories.destroy', $category)"
                                                :message="'هل تريد حذف قسم ' . $category->name . '؟ سيختفي القسم من الموقع، وتبقى الخدمات والحجوزات المرتبطة به محفوظة.'"
                                                label="حذف" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            @if ($search !== '')
                                                <strong>لا توجد أقسام مطابقة للبحث</strong>
                                                جرّب كلمة بحث أخرى أو امسح البحث.
                                            @else
                                                <strong>لا توجد أقسام بعد</strong>
                                                ابدأ بإضافة أول قسم من زر "إضافة قسم جديد".
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $categories->onEachSide(1)->links('components.pager') }}
        </main>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
