@extends('layouts.app')
@section('title', 'حجوزاتي | ServiceHub')
@section('page_id', 'customer-bookings')
@section('content')
    @include('components.navbar')
    <div class="container layout">@include('components.sidebars.customer')
        <div>
            <div class="dash-title">
                <div><span class="small muted">لوحة العميل</span>
                    <h1>حجوزاتي</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="panel">
                <div class="filters"><span class="pill yellow">الكل</span><span class="pill">بانتظار الرد</span><span
                        class="pill">القادمة</span><span class="pill">المكتملة</span></div>
                <div id="booking-list"></div>
            </div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-customer')
@endsection