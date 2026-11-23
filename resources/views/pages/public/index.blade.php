@extends('layouts.app')
@section('title', 'الرئيسية')
@section('page_id', 'index')
@section('content')
@include('components.navbar')
<main>
    @php
         
        $heroSlides = [
            ['plumber-2.jpg', 'فني سباكة يركّب وصلات أنابيب المياه', 'السباكة', 'center'],
            ['electrician.jpg', 'فني كهرباء يفحص لوحة التوزيع', 'الكهرباء', 'center 30%'],
            ['ac-technician.jpg', 'فني يصلح وحدة مكيف خارجية', 'صيانة المكيفات', 'center 20%'],
            ['painter.jpg', 'دهّان يجدد جدران غرفة', 'الدهان', 'center 35%'],
            ['solar.jpg', 'فني يركّب ألواح طاقة شمسية', 'الطاقة الشمسية', 'center 30%'],
            ['cleaning.jpg', 'عاملة تنظيف تنظف غرفة جلوس', 'التنظيف المتخصص', 'center 25%'],
        ];
    @endphp
    <section class="hero home-hero" data-hero-slider aria-roledescription="carousel" aria-label="صور من خدمات المنصة">
        <div class="hero-bg" aria-hidden="true">
            @foreach ($heroSlides as $index => [$image, $alt, $label, $focus])
                <figure class="hero-slide {{ $index === 0 ? 'is-active' : '' }}" data-label="{{ $label }}">
                    <img src="{{ asset('assets/demo/hero/' . $image) }}" alt="{{ $alt }}" width="1920" height="960"
                        style="object-position: {{ $focus }}"
                        @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif>
                </figure>
            @endforeach
        </div>

        <div class="container hero-full-inner">
            <div class="home-hero-copy">
                <span class="eyebrow">خدمات الصيانة بمكان واحد</span>
                <h1>عندك عطل؟ <span>صلّحها.</span></h1>
                <p>احكيلنا شو المشكلة، اكتشف الفنيين المناسبين، وقارن الخدمات والتقييمات.</p>
                <div class="hero-actions">
                    <a class="btn btn-yellow" href="#vendors">اكتشف الفني المناسب</a>
                    <a class="btn btn-outline" href="#how-it-works">كيف تعمل المنصة؟</a>
                </div>
            </div>

            <div class="hero-controls">
                <span class="hero-current" data-hero-label aria-live="polite">{{ $heroSlides[0][2] }}</span>
                <div class="hero-dots">
                    @foreach ($heroSlides as $index => $slide)
                        <button type="button" class="{{ $index === 0 ? 'is-active' : '' }}" data-hero-dot="{{ $index }}"
                            aria-label="عرض صورة {{ $slide[2] }}"></button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <section class="assistant-search" aria-labelledby="maintenance-assistant-title">
            <h2 id="maintenance-assistant-title">مش عارف أي فني بتحتاج؟ خلّينا نساعدك</h2>
            <p class="muted">اوصف المشكلة بدون معلومات شخصية لنقترح قسم الصيانة المناسب.</p>
            <form method="POST" action="{{ route('maintenance.assistant') }}" data-maintenance-assistant>
                @csrf
                <label class="sr-only" for="maintenance-problem" data-assistant-label>وصف المشكلة</label>
                <input class="searchbox" id="maintenance-problem" name="problem" placeholder="مثال: المكيف شغال بس مش ببرّد" maxlength="500" autocomplete="off" required>
                <button class="btn btn-dark" type="submit">جرّب المساعد</button>
            </form>
            <div class="assistant-examples" data-assistant-examples>
                @foreach (['الحنفية بتنقّط', 'المكيف شغال بس مش ببرّد', 'في رطوبة على الجدار'] as $example)
                    <button class="btn btn-outline btn-sm" type="button" data-assistant-example>{{ $example }}</button>
                @endforeach
            </div>
            <div class="assistant-answer" data-assistant-answer hidden>
                <ul class="assistant-conversation" data-assistant-conversation aria-label="سياق المحادثة" hidden></ul>
                <div role="status" aria-live="polite">
                    <strong data-assistant-heading></strong>
                    <p data-assistant-result></p>
                    <ul data-assistant-services hidden></ul>
                </div>
                <div class="assistant-answer-actions">
                    <a class="btn btn-yellow btn-sm" data-assistant-category hidden>عرض الفنيين في القسم</a>
                    <div class="assistant-category-options" data-assistant-options hidden></div>
                    <button class="btn btn-outline btn-sm" type="button" data-assistant-reset>مشكلة ثانية</button>
                </div>
            </div>
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
