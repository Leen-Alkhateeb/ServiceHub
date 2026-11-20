@extends('layouts.app')
@section('title', 'الملف الشخصي | ServiceHub')
@section('page_id', 'customer-settings')
@section('content')
    @include('components.navbar')
    <div class="container layout">@include('components.sidebars.customer')
        <div>
            <div class="dash-title">
                <div><span class="small muted">لوحة العميل</span>
                    <h1>الملف الشخصي</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="panel">
                <h3>الملف الشخصي</h3>
                <p>تحديث الاسم ورقم الهاتف والموقع والصورة الشخصية.</p>
                <form data-demo="">
                    <div class="field"><label>الاسم الأول</label><input name="f0" placeholder="الاسم الأول" required=""
                            type="text" /></div>
                    <div class="field"><label>اسم العائلة</label><input name="f1" placeholder="اسم العائلة" required=""
                            type="text" /></div><button class="btn btn-yellow">حفظ تجريبي</button>
                </form>
            </div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-customer')
@endsection