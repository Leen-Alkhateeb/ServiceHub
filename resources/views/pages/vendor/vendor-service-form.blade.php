@extends('layouts.app')
@section('title', 'إضافة خدمة فني')
@section('page_id', 'vendor-service-form')
@section('content')
    @include('components.navbar')
    <main>
        <div class="container page-head" style="max-width:620px">
            <div class="panel">
                <h1>إضافة خدمة فني</h1>
                <p class="muted">حد 10 خدمات نشطة في الوقت نفسه. الصورة الواحدة إلزامية.</p>
                <form data-demo="">
                    <div class="field"><label>الخدمة من الكتالوج</label><input name="f0" placeholder="الخدمة من الكتالوج"
                            required="" type="text" /></div>
                    <div class="field"><label>الوصف</label><input name="f1" placeholder="الوصف" required="" type="text" />
                    </div>
                    <div class="field"><label>السعر</label><input name="f2" placeholder="السعر" required="" type="text" />
                    </div><button class="btn btn-yellow">حفظ الخدمة</button>
                </form>
            </div>
        </div>
        <div style="height:70px"></div>
    </main>@include('components.footer')@include('components.mobile-nav-vendor')
@endsection