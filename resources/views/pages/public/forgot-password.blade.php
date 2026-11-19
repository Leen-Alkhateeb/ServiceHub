@extends('layouts.app')
@section('title', 'استعادة كلمة المرور | ServiceHub')
@section('page_id', 'forgot-password')
@section('content')
@include('components.navbar')<main><div class="container page-head" style="max-width:620px"><div class="panel"><h1>استعادة كلمة المرور</h1><p class="muted"></p><form data-demo=""><div class="field"><label>البريد الإلكتروني</label><input name="f0" placeholder="البريد الإلكتروني" required="" type="email"/></div><button class="btn btn-yellow">إرسال رابط الاستعادة</button></form></div></div><div style="height:70px"></div></main>@include('components.footer')@include('components.mobile-nav-public')
@endsection
