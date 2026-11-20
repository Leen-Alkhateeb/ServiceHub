@extends('layouts.app')
@section('title', 'لوحتي | ServiceHub')
@section('page_id', 'customer-dashboard')
@section('content')
    @include('components.navbar')<div class="container layout">@include('components.sidebars.customer')<div>
            <div class="dash-title">
                <div><span class="small muted">لوحة العميل</span>
                    <h1>لوحتي</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="stats">
                <div class="stat"><strong>—</strong><span>حجوزاتي</span></div>
                <div class="stat"><strong>—</strong><span>بانتظار الرد</span></div>
                <div class="stat"><strong>—</strong><span>المفضلة</span></div>
                <div class="stat"><strong>—</strong><span>الإشعارات</span></div>
            </div>
            <div class="grid"><a class="feature" href="/customer-bookings">
                    <div class="feature-icon">✓</div>
                    <h3>حجوزاتي</h3>
                    <p>تابع حالة الطلبات والمدفوعات.</p>
                </a><a class="feature" href="/customer-favorites">
                    <div class="feature-icon">✓</div>
                    <h3>المفضلة</h3>
                    <p>الخدمات التي حفظتها.</p>
                </a><a class="feature" href="/customer-reviews">
                    <div class="feature-icon">✓</div>
                    <h3>التقييمات</h3>
                    <p>قيّم الفني بعد الخدمة.</p>
                </a><a class="feature" href="/customer-settings">
                    <div class="feature-icon">✓</div>
                    <h3>الملف الشخصي</h3>
                    <p>بيانات حسابك.</p>
                </a></div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-customer')
@endsection
