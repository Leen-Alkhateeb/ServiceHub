@extends('layouts.app')
@section('title', 'المفضلة | ServiceHub')
@section('page_id', 'customer-favorites')
@section('content')
    @include('components.navbar')<div class="container layout">@include('components.sidebars.customer')<div>
            <div class="dash-title">
                <div><span class="small muted">لوحة العميل</span>
                    <h1>المفضلة</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="grid" id="favorite-cards"></div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-customer')
@endsection
