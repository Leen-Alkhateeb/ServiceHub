@extends('layouts.app')
@section('title', 'خدماتي')
@section('page_id', 'vendor-services')
@section('content')
    @include('components.navbar')
    <div class="container layout">@include('components.sidebars.vendor')
        <div>
            <div class="dash-title">
                <div><span class="small muted">لوحة الفني</span>
                    <h1>خدماتي</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="panel">
                <p>يمكنك إضافة خدمات بلا حد إجمالي، لكن الحد الأقصى للخدمات النشطة هو 10.</p><a class="btn btn-yellow"
                    href="/vendor-service-form">إضافة خدمة</a>
            </div>
            <div class="grid" id="home-cards" style="margin-top:18px"></div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-vendor')
@endsection