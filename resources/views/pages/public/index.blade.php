@extends('layouts.app')
@section('title', 'الرئيسية | ServiceHub')
@section('page_id', 'index')
@section('content')
@include('components.navbar')
<main>
    <section class="hero home-hero">
        <div class="container home-hero-layout">
            <div class="home-hero-copy">
                <span class="eyebrow">خدمات الصيانة بمكان واحد</span>
                <h1>عندك عطل؟ <span>صلّحها.</span></h1>
                <p>احكيلنا شو المشكلة، اكتشف الفنيين المناسبين، وقارن الخدمات والتقييمات.</p>
                <div class="hero-actions">
                    <a class="btn btn-yellow" href="#vendors">اكتشف الفني المناسب</a>
                    <a class="btn btn-outline" href="#how-it-works">كيف تعمل المنصة؟</a>
                </div>
            </div>
            <picture class="home-hero-media">
                <source media="(max-width: 720px)" srcset="{{ asset('assets/demo/directory/hero-repair-mobile.jpg') }}" width="720" height="480">
                <img src="{{ asset('assets/demo/directory/hero-repair.jpg') }}" alt="فني صيانة يفحص تمديدات المياه في مطبخ منزلي"
                    width="1200" height="900" fetchpriority="high">
            </picture>
        </div>
    </section>

    <div class="container">
        <section class="assistant-search">
            <h2>مش عارف أي فني بتحتاج؟ خلّينا نساعدك</h2>
            <p class="muted">اوصف المشكلة بدون معلومات شخصية لنقترح قسم الصيانة المناسب.</p>
            <form method="POST" action="{{ route('maintenance.assistant') }}" data-maintenance-assistant>
                @csrf
                <label class="sr-only" for="maintenance-problem">وصف المشكلة</label>
                <input class="searchbox" id="maintenance-problem" name="problem" placeholder="مثال: المكيف شغال بس مش ببرّد" maxlength="500" required>
                <button class="btn btn-dark" type="submit">جرّب المساعد</button>
            </form>
            <p data-assistant-result role="status" aria-live="polite"></p>
        </section>
    </div>

    <section class="section" id="categories">
        <div class="container">
            <h2>شو نوع الصيانة اللي بتحتاجها؟</h2>
            <div class="grid home-directory-grid">
                @foreach ($categories as $category)
                    <x-category-card :category="$category" />
                @endforeach
                <a class="card directory-card directory-more" href="{{ route('categories.index') }}">
                    <div class="card-body">
                        <h3>عرض المزيد</h3>
                        <p>تصفّح جميع أقسام الصيانة.</p>
                    </div>
                    <span class="directory-arrow" aria-hidden="true">←</span>
                </a>
            </div>
        </div>
    </section>

    <section class="section" id="vendors">
        <div class="container">
            <div class="section-head">
                <h2>فنيون على المنصة</h2>
                <a class="btn btn-outline btn-sm" href="{{ route('vendors.index') }}">عرض كل الفنيين <span aria-hidden="true">←</span></a>
            </div>
            <div class="grid home-directory-grid">
                @forelse ($vendors as $vendor)
                    <x-vendor-card :vendor="$vendor" />
                @empty
                    <p class="muted">لا يوجد فنيون متاحون حاليًا.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="section" id="how-it-works">
        <div class="container">
            <h2>من وصف المشكلة لمتابعة الطلب</h2>
            <div class="grid">
                <article class="feature"><h3>اختار الخدمة</h3><p>ابحث عن نوع الصيانة المناسب.</p></article>
                <article class="feature"><h3>قارن الفنيين</h3><p>شوف الأسعار والتقييمات والأعمال السابقة.</p></article>
                <article class="feature"><h3>احجز الموعد وتابع</h3><p>اختار موعدًا مناسبًا وتابع حالة الطلب.</p></article>
            </div>
        </div>
    </section>

    @if (auth()->guest() || (auth()->user()->role === 'customer' && auth()->user()->status === 'active'))
        <section class="section">
            <div class="container panel">
                <h2>عندك خبرة بالصيانة؟</h2>
                <p>قدّم طلب الانضمام وارفع تصريح العمل.</p>
                <a class="btn btn-yellow" href="{{ route('vendor.apply') }}">قدّم طلب الانضمام</a>
            </div>
        </section>
    @endif
    <section class="section home-faq-section" id="faq">
        <div class="container">
            <div class="home-faq-layout">
                <div>
                    <h2>الأسئلة الأكثر تكرارًا</h2>
                    @include('components.home-faq')
                </div>
                <img class="home-faq-photo" src="{{ asset('assets/demo/directory/faq-assistance.jpg') }}"
                    alt="فني صيانة يشرح تفاصيل الإصلاح لأحد العملاء" width="800" height="600" loading="lazy">
            </div>
        </div>
    </section>
</main>
@include('components.footer')
@include('components.mobile-nav-public')
@endsection
