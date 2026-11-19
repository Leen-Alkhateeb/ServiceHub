@extends('layouts.app')
@section('title', 'تقييماتي | ServiceHub')
@section('page_id', 'customer-reviews')
@section('content')
@include('components.navbar')<div class="container layout">@include('components.sidebars.customer')<div><div class="dash-title"><div><span class="small muted">لوحة العميل</span><h1>تقييماتي</h1></div><span class="demo">بيانات تجريبية</span></div><div class="panel"><h3>تقييماتي</h3><p>التقييم متاح بعد حجز مؤهل، مرة واحدة لكل طرف، ولا يمكن تعديله بعد الإرسال.</p><form data-demo=""><div class="field"><label>رقم الحجز</label><input name="f0" placeholder="رقم الحجز" required="" type="text"/></div><div class="field"><label>التعليق</label><input name="f1" placeholder="التعليق" required="" type="text"/></div><button class="btn btn-yellow">حفظ تجريبي</button></form></div></div></div>@include('components.footer')@include('components.mobile-nav-customer')
@endsection
