@extends('layouts.app')
@section('title', 'معرض الأعمال')
@section('page_id', 'vendor-portfolio')
@section('content')
    @include('components.navbar')
    <div class="container layout">@include('components.sidebars.vendor')
        <div>
            <div class="dash-title">
                <div><span class="small muted">لوحة الفني</span>
                    <h1>معرض الأعمال</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="panel">
                <p>لكل مشروع اسم وصورة واحدة على الأقل، ويمكن إضافة عدة صور.</p><a class="btn btn-yellow"
                    href="/vendor-portfolio-form">إضافة مشروع</a>
            </div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-vendor')
@endsection