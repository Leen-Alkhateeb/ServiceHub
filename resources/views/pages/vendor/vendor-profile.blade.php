@extends('layouts.app')
@section('title', 'ملف الفني | ServiceHub')
@section('page_id', 'vendor-profile')
@section('content')
@include('components.navbar')<main><div class="container page-head"><h1>ملف الفني ومعرض الأعمال</h1><div class="split"><div class="panel"><span class="pill yellow">فني معتمد — عرض تجريبي</span><h2>خالد العمري</h2><p>السباكة · عمّان · 9 سنوات خبرة</p><p>تقييم العملاء: ★ 4.8</p><h3>الأعمال السابقة</h3><div class="art blue">مشروع إصلاح تسريبات</div><p>يمكن للفني إضافة مشاريع متعددة ولكل مشروع عدة صور.</p></div><div class="panel"><h3>الخدمات المتاحة</h3><div class="grid" id="home-cards" style="grid-template-columns:1fr"></div></div></div></div><div style="height:70px"></div></main>@include('components.footer')@include('components.mobile-nav-vendor')
@endsection
