@extends('layouts.app')
@section('title', 'تسجيل الدخول | ServiceHub')
@section('page_id', 'login')
@section('content')
@include('components.navbar')<main><div class="container page-head" style="max-width:620px"><div class="panel"><h1>تسجيل الدخول</h1><p class="muted">واجهة توضيحية — المصادقة الحقيقية تحتاج Laravel.</p><form data-demo=""><div class="field"><label>البريد الإلكتروني</label><input name="f0" placeholder="البريد الإلكتروني" required="" type="email"/></div><div class="field"><label>كلمة المرور</label><input name="f1" placeholder="كلمة المرور" required="" type="password"/></div><button class="btn btn-yellow">دخول</button></form></div></div><div style="height:70px"></div></main>@include('components.footer')@include('components.mobile-nav-public')
@endsection
