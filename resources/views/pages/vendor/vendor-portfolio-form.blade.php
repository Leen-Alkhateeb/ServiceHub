@extends('layouts.app')
@section('title', 'إضافة مشروع')
@section('page_id', 'vendor-portfolio-form')
@section('content')
    @include('components.navbar')
    <main>
        <div class="container page-head" style="max-width:620px">
            <div class="panel">
                <h1>إضافة مشروع</h1>
                <p class="muted">إرفاق صورة واحدة على الأقل؛ دعم رفع عدة صور يحتاج Backend.</p>
                <form data-demo="">
                    <div class="field"><label>اسم المشروع</label><input name="f0" placeholder="اسم المشروع" required=""
                            type="text" /></div>
                    <div class="field"><label>الوصف</label><input name="f1" placeholder="الوصف" required="" type="text" />
                    </div>
                    <div class="field"><label>تاريخ الإنجاز</label><input name="f2" placeholder="تاريخ الإنجاز" required=""
                            type="text" /></div>
                    <div class="field"><label>رابط المشروع</label><input name="f3" placeholder="رابط المشروع" required=""
                            type="text" /></div><button class="btn btn-yellow">حفظ المشروع</button>
                </form>
            </div>
        </div>
        <div style="height:70px"></div>
    </main>@include('components.footer')@include('components.mobile-nav-vendor')
@endsection