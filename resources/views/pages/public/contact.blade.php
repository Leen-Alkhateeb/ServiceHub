@extends('layouts.app')
@section('title', 'تواصل معنا | ServiceHub')
@section('page_id', 'contact')
@section('content')
@include('components.navbar')<main><div class="container page-head" style="max-width:620px"><div class="panel"><h1>تواصل معنا</h1><p class="muted">الإدارة تقرأ الرسائل فقط؛ لا يوجد رد داخلي.</p><form data-demo=""><div class="field"><label>الموضوع</label><input name="f0" placeholder="الموضوع" required="" type="text"/></div><div class="field"><label>الرسالة</label><input name="f1" placeholder="الرسالة" required="" type="text"/></div><button class="btn btn-yellow">إرسال الرسالة</button></form></div></div><div style="height:70px"></div></main>@include('components.footer')@include('components.mobile-nav-public')
@endsection
