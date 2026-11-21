@extends('layouts.app')
@section('title', 'كيف تعمل المنصة | ServiceHub')
@section('page_id', 'how-it-works')
@section('content')
    @include('components.navbar')
    <main>
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h2>من وصف المشكلة لمتابعة الطلب</h2>
                        <p class="muted"></p>
                    </div>
                </div>
                <div class="grid"><a class="feature" href="/services">
                        <div class="feature-icon">✓</div>
                        <h3>١ · اوصف المشكلة أو اختار الخدمة</h3>
                        <p>ابحث عن نوع الصيانة المناسب.</p>
                    </a><a class="feature" href="/vendors">
                        <div class="feature-icon">✓</div>
                        <h3>٢ · قارن الفنيين والخدمات</h3>
                        <p>شوف الأسعار والتقييمات والأعمال السابقة.</p>
                    </a><a class="feature" href="/booking">
                        <div class="feature-icon">✓</div>
                        <h3>٣ · احجز الموعد المناسب</h3>
                        <p>اختار نافذة من جدول الفني الأسبوعي.</p>
                    </a><div class="feature">
                        <div class="feature-icon">✓</div>
                        <h3>٤ · استلم الخدمة واطبع فاتورتك</h3>
                        <p>بعد الدفع ستظهر فاتورتك لتطبعها أو تحفظها PDF.</p>
                    </div></div>
            </div>
        </section>
    </main>@include('components.footer')@include('components.mobile-nav-public')
@endsection
