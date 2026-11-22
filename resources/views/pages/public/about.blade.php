@extends('layouts.app')
@section('title', 'من نحن')
@section('page_id', 'about')
@section('content')
    @include('components.navbar')
    <main>
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h2>{{ config('app.site_name') }} — خدمات صيانة بطريقة أوضح</h2>
                        <p class="muted"></p>
                    </div>
                </div>
                <div class="panel">
                    <p>منصة تجمع العملاء والفنيين لعرض الخدمات، مقارنة الأسعار والتقييمات، وتنظيم الحجوزات والمدفوعات
                        التجريبية والمتابعة بعد الإنجاز.</p><a class="btn btn-yellow" href="/services">اكتشف الخدمات</a>
                </div>
            </div>
        </section>
    </main>@include('components.footer')@include('components.mobile-nav-public')
@endsection