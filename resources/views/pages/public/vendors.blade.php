@extends('layouts.app')
@section('title', 'الفنيون | ServiceHub')
@section('page_id', 'vendors')
@section('content')
@include('components.navbar')<main><div class="container page-head"><h1>فنيون على المنصة</h1><p class="muted">ملفات الفنيين وخدماتهم وتقييماتهم.</p><div class="grid" id="service-cards"></div></div><div style="height:70px"></div></main>@include('components.footer')@include('components.mobile-nav-public')
@endsection
