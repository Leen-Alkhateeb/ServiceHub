@extends('layouts.app')
@section('title', 'طلبات الحجز | ServiceHub')
@section('page_id', 'vendor-bookings')
@section('content')
@include('components.navbar')<div class="container layout">@include('components.sidebars.vendor')<div><div class="dash-title"><div><span class="small muted">لوحة الفني</span><h1>طلبات الحجز</h1></div><span class="demo">بيانات تجريبية</span></div><div class="panel"><h3>طلبات بانتظار الرد</h3><p>يمكن للفني الموافقة أو الرفض. عند الموافقة تبدأ مهلة الدفع 30 دقيقة.</p><div class="item-row"><b>طلب صيانة جديد</b><div><button class="btn btn-yellow btn-sm" onclick="alert('نموذج توضيحي — يحتاج Backend')">موافقة</button> <button class="btn btn-outline btn-sm" onclick="alert('نموذج توضيحي — يحتاج Backend')">اعتذار</button></div></div></div></div></div>@include('components.footer')@include('components.mobile-nav-vendor')
@endsection
