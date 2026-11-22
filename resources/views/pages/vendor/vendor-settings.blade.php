@extends('layouts.app')
@section('title', 'الإعدادات')
@section('page_id', 'vendor-settings')
@section('content')
    @include('components.navbar')
    <div class="container layout">@include('components.sidebars.vendor')
        <div>
            <div class="dash-title">
                <div><span class="small muted">لوحة الفني</span>
                    <h1>الإعدادات</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="panel">
                <h3>الإعدادات</h3>
                <p>تحديث بيانات الفني وحساب المستخدم.</p>
                <div class="notice">البيانات الفعلية تتطلب Laravel API.</div>
            </div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-vendor')
@endsection