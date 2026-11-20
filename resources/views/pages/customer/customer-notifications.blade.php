@extends('layouts.app')
@section('title', 'الإشعارات | ServiceHub')
@section('page_id', 'customer-notifications')
@section('content')
    @include('components.navbar')
    <div class="container layout">@include('components.sidebars.customer')
        <div>
            <div class="dash-title">
                <div><span class="small muted">لوحة العميل</span>
                    <h1>الإشعارات</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="panel">
                <h3>الإشعارات</h3>
                <p>إشعارات حالة الحجوزات والمدفوعات والاعتراضات.</p>
                <form data-demo="">
                    <div class="field"><label>عنوان الإشعار</label><input name="f0" placeholder="عنوان الإشعار" required=""
                            type="text" /></div><button class="btn btn-yellow">حفظ تجريبي</button>
                </form>
            </div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-customer')
@endsection