@extends('layouts.app')
@section('title', 'تفاصيل الخدمة | ServiceHub')
@section('page_id', 'service-details')
@section('content')
@include('components.navbar')<main><div class="container page-head"><p class="muted">الرئيسية ‹ الخدمات ‹ تفاصيل الخدمة</p><div class="split" id="service-detail"></div></div><div style="height:70px"></div></main>@include('components.footer')@include('components.mobile-nav-public')
@endsection
