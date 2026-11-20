@extends('layouts.app')
@section('title', 'كل خدمات الصيانة | ServiceHub')
@section('page_id', 'services')
@section('content')
    @include('components.navbar')
    <main>
        <div class="container page-head">
            <h1>كل خدمات الصيانة</h1>
            <p class="muted">قارن الأسعار والتقييمات، واختار الخدمة المناسبة.</p>
            <div class="toolbar"><input class="searchbox" id="search-input"
                    placeholder="ابحث عن خدمة أو فني أو محافظة" /><select class="searchbox" id="category-filter"
                    style="max-width:220px">
                    <option value="">كل الأقسام</option>
                    <option>السباكة</option>
                    <option>الكهرباء</option>
                    <option>صيانة المكيفات</option>
                </select></div>
            <div class="grid" id="service-cards"></div>
        </div>
        <div style="height:70px"></div>
    </main>@include('components.footer')@include('components.mobile-nav-public')
@endsection