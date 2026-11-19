@extends('layouts.app')
@section('title', 'طلب اعتراض على الخدمة | ServiceHub')
@section('page_id', 'customer-refund')
@section('content')
@include('components.navbar')<main><div class="container page-head" style="max-width:620px"><div class="panel"><h1>طلب اعتراض على الخدمة</h1><p class="muted">إذا وافقت الإدارة: 70% للعميل، 15% للفني، 15% للمنصة.</p><form data-demo=""><div class="field"><label>رقم الحجز</label><input name="f0" placeholder="رقم الحجز" required="" type="text"/></div><div class="field"><label>سبب الاعتراض</label><input name="f1" placeholder="سبب الاعتراض" required="" type="text"/></div><button class="btn btn-yellow">إرسال الاعتراض</button></form></div></div><div style="height:70px"></div></main>@include('components.footer')@include('components.mobile-nav-customer')
@endsection
