@extends('layouts.app')

@section('title', 'إضافة قسم')
@section('page_id', 'admin-categories')

@push('styles')
    <x-public-stylesheet file="admin-management.css" />
@endpush

@section('content')
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <main class="dashboard-content admin-manage">
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة / الأقسام</span>
                    <h1>إضافة قسم جديد</h1>
                    <p class="page-intro">سيظهر القسم للعملاء مباشرة بعد الحفظ.</p>
                </div>
                <a href="{{ route('admin.categories') }}" class="btn btn-outline">رجوع للأقسام</a>
            </div>

            <div class="panel form-panel">
                <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    @include('pages.admin.partials.category-form-fields', ['category' => null])

                    <div class="form-actions">
                        <button type="submit" class="btn btn-yellow">إضافة القسم</button>
                        <a href="{{ route('admin.categories') }}" class="btn btn-outline">إلغاء</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
