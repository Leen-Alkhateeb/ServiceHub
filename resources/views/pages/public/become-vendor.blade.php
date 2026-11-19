@extends('layouts.app')
@section('title', 'انضم كفني | ServiceHub')
@section('page_id', 'become-vendor')
@section('content')
@include('components.navbar')<main><div class="container page-head" style="max-width:620px"><div class="panel"><h1>انضم كفني</h1><p class="muted">تصريح عمل واحد إلزامي؛ رفع الملف ومراجعته يتطلبان Backend.</p><form data-demo=""><div class="field"><label>سنوات الخبرة</label><input name="f0" placeholder="سنوات الخبرة" required="" type="text"/></div><div class="field"><label>نوع العمل</label><input name="f1" placeholder="نوع العمل" required="" type="text"/></div><div class="field"><label>نبذة عن خبرتك</label><input name="f2" placeholder="نبذة عن خبرتك" required="" type="text"/></div><div class="field"><label>تاريخ انتهاء التصريح</label><input name="f3" placeholder="تاريخ انتهاء التصريح" required="" type="text"/></div><button class="btn btn-yellow">تقديم طلب الانضمام</button></form></div></div><div style="height:70px"></div></main>@include('components.footer')@include('components.mobile-nav-public')
@endsection
