@extends('layouts.app')
@section('title', 'السجل المالي | ServiceHub')
@section('page_id', 'vendor-wallet')
@section('content')
    @include('components.navbar')
    <div class="container layout">@include('components.sidebars.vendor')
        <div>
            <div class="dash-title">
                <div><span class="small muted">لوحة الفني</span>
                    <h1>السجل المالي</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="panel">
                <h3>السجل المالي</h3>
                <p>الحركات المالية، عمولة المنصة 15%، وتوزيع المبالغ بعد انتهاء مهلة الاعتراض.</p>
                <div class="notice">البيانات الفعلية تتطلب Laravel API.</div>
            </div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-vendor')
@endsection