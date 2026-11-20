@extends('layouts.app')
@section('title', 'إنشاء حساب | ServiceHub')
@section('page_id', 'register')
@section('content')
    @include('components.navbar')
    <main>
        <div class="container page-head" style="max-width:620px">
            <div class="panel">
                <h1>إنشاء حساب</h1>
                <p class="muted"></p>
                <form data-demo="">
                    <div class="field"><label>الاسم الأول</label><input name="f0" placeholder="الاسم الأول" required=""
                            type="text" /></div>
                    <div class="field"><label>اسم العائلة</label><input name="f1" placeholder="اسم العائلة" required=""
                            type="text" /></div>
                    <div class="field"><label>البريد الإلكتروني</label><input name="f2" placeholder="البريد الإلكتروني"
                            required="" type="email" /></div>
                    <div class="field"><label>رقم الهاتف</label><input name="f3" placeholder="رقم الهاتف" required=""
                            type="text" /></div>
                    <div class="field"><label>كلمة المرور</label><input name="f4" placeholder="كلمة المرور" required=""
                            type="password" /></div><button class="btn btn-yellow">إنشاء الحساب</button>
                </form>
            </div>
        </div>
        <div style="height:70px"></div>
    </main>@include('components.footer')@include('components.mobile-nav-public')
@endsection