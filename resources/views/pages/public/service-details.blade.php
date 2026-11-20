@extends('layouts.app')

@section('title', $vendorService->service->name . ' | ServiceHub')
@section('page_id', 'service-details')

@section('content')
@include('components.navbar')

@php
    $cover = $vendorService->image
        ? asset('storage/' . $vendorService->image)
        : null;

    $isCustomer = auth()->check()
        && auth()->user()->role === 'customer';

    $hasSlots = collect($days)->contains(
        fn ($day) => count($day['slots']) > 0
    );
@endphp

<style>
    .service-details-page {
        --sd-green: #176b5b;
        --sd-green-dark: #105346;
        --sd-ink: #18342e;
        --sd-muted: #71817c;
        --sd-line: #e4ece8;
        direction: rtl;
        min-height: 70vh;
        padding: 28px 0 64px;
        color: var(--sd-ink);
        background: #f8faf9;
    }

    .service-details-page *,
    .service-details-page *::before,
    .service-details-page *::after {
        box-sizing: border-box;
    }

    .sd-container {
        width: min(1120px, calc(100% - 32px));
        margin-inline: auto;
    }

    .sd-breadcrumb {
        margin: 0 0 18px;
        color: var(--sd-muted);
        font-size: 14px;
    }

    .sd-breadcrumb a {
        color: var(--sd-green);
        text-decoration: none;
    }

    .sd-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.55fr) minmax(290px, .8fr);
        gap: 22px;
        align-items: start;
    }

    .sd-main,
    .sd-aside {
        display: grid;
        gap: 18px;
    }

    .sd-card {
        overflow: hidden;
        border: 1px solid var(--sd-line);
        border-radius: 17px;
        background: #fff;
        box-shadow: 0 8px 25px #18342e08;
    }

    .sd-cover {
        height: 320px;
        background: linear-gradient(135deg, #e4eee9, #f3f7f5);
        background-position: center;
        background-size: cover;
    }

    .sd-card-content {
        padding: 23px;
    }

    .sd-category {
        display: inline-block;
        padding: 6px 11px;
        border-radius: 999px;
        color: var(--sd-green);
        background: #eaf4ef;
        font-size: 13px;
        font-weight: 700;
    }

    .sd-title {
        margin: 13px 0 9px;
        font-size: clamp(25px, 3vw, 35px);
        line-height: 1.35;
    }

    .sd-description {
        margin: 0;
        color: var(--sd-muted);
        line-height: 1.9;
        white-space: pre-line;
    }

    .sd-aside-card {
        padding: 21px;
    }

    .sd-price-label {
        margin: 0 0 3px;
        color: var(--sd-muted);
        font-size: 14px;
    }

    .sd-price {
        margin: 0 0 17px;
        color: var(--sd-green);
        font-size: 30px;
        font-weight: 800;
    }

    .sd-button {
        display: block;
        width: 100%;
        padding: 12px 15px;
        border: 0;
        border-radius: 10px;
        color: #fff;
        background: var(--sd-green);
        text-align: center;
        text-decoration: none;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
        transition: background .15s ease;
    }

    .sd-button:hover {
        color: #fff;
        background: var(--sd-green-dark);
    }

    .sd-button-outline {
        margin-top: 10px;
        border: 1px solid var(--sd-line);
        color: var(--sd-green);
        background: #fff;
    }

    .sd-button-outline:hover {
        color: var(--sd-green-dark);
        background: #f4f8f6;
    }

    .sd-heading {
        margin: 0 0 15px;
        font-size: 19px;
    }

    .sd-vendor {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sd-avatar {
        display: grid;
        flex: 0 0 52px;
        width: 52px;
        height: 52px;
        place-items: center;
        overflow: hidden;
        border-radius: 50%;
        color: var(--sd-green);
        background: #e8f3ee;
        object-fit: cover;
        font-size: 20px;
        font-weight: 700;
    }

    .sd-vendor-name {
        margin: 0 0 4px;
        font-weight: 800;
    }

    .sd-muted {
        color: var(--sd-muted);
        font-size: 14px;
    }

    .sd-rating {
        margin: 14px 0;
        color: var(--sd-muted);
        font-size: 14px;
    }

    .sd-stars {
        color: #a8790c;
        font-weight: 800;
        white-space: nowrap;
    }

    .sd-booking-intro {
        margin: -4px 0 16px;
        color: var(--sd-muted);
        font-size: 14px;
        line-height: 1.7;
    }

    .sd-day {
        margin: 18px 0 9px;
        font-size: 15px;
        font-weight: 800;
    }

    .sd-slots {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
    }

    .sd-slot {
        position: relative;
        display: block;
        cursor: pointer;
    }

    .sd-slot input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .sd-slot span {
        display: block;
        padding: 11px 8px;
        border: 1px solid var(--sd-line);
        border-radius: 9px;
        background: #fff;
        text-align: center;
        direction: ltr;
        font-weight: 700;
        transition: .15s ease;
    }

    .sd-slot input:checked + span {
        border-color: var(--sd-green);
        color: var(--sd-green);
        background: #edf6f1;
        box-shadow: 0 0 0 2px #176b5b18;
    }

    .sd-slot input:focus-visible + span {
        outline: 3px solid #176b5b55;
    }

    .sd-error {
        margin: 0 0 14px;
        padding: 11px 13px;
        border: 1px solid #f0caca;
        border-radius: 9px;
        color: #8b2929;
        background: #fff2f2;
    }

    .sd-review {
        padding: 14px 0;
        border-top: 1px solid var(--sd-line);
    }

    .sd-review:first-of-type {
        border-top: 0;
    }

    .sd-review-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .sd-review-comment {
        margin: 8px 0 0;
        color: var(--sd-muted);
        line-height: 1.7;
    }

    @media (max-width: 800px) {
        .sd-layout {
            grid-template-columns: 1fr;
        }

        .sd-cover {
            height: 260px;
        }
    }

    @media (max-width: 520px) {
        .sd-container {
            width: min(100% - 24px, 1120px);
        }

        .sd-cover {
            height: 210px;
        }

        .sd-card-content,
        .sd-aside-card {
            padding: 17px;
        }
    }
</style>

<main class="service-details-page">
    <div class="sd-container">
        <p class="sd-breadcrumb">
            <a href="{{ route('home') }}">الرئيسية</a>
            ‹
            <a href="{{ route('market.index') }}">الخدمات</a>
            ‹ تفاصيل الخدمة
        </p>

        @if ($errors->any())
            <div class="sd-error">{{ $errors->first() }}</div>
        @endif

        @if (session('success'))
            <div class="sd-error" style="border-color:#b8dfc9;color:#176b5b;background:#effaf3">
                {{ session('success') }}
            </div>
        @endif

        <div class="sd-layout">
            <div class="sd-main">
                <article class="sd-card">
                    <div class="sd-cover"
                         @if ($cover) style="background-image:url('{{ $cover }}')" @endif>
                    </div>

                    <div class="sd-card-content">
                        <span class="sd-category">
                            {{ $vendorService->service->category->name }}
                        </span>

                        <h1 class="sd-title">
                            {{ $vendorService->service->name }}
                        </h1>

                        <p class="sd-description">
                            {{ $vendorService->description
                                ?? $vendorService->service->description
                                ?? 'لا يوجد وصف إضافي لهذه الخدمة.' }}
                        </p>
                    </div>
                </article>

                <section class="sd-card sd-aside-card">
                    <h2 class="sd-heading">تقييمات العملاء</h2>

                    @forelse ($reviews as $review)
                        <article class="sd-review">
                            <div class="sd-review-top">
                                <strong>
                                    {{ $review->reviewer->first_name }}
                                    {{ $review->reviewer->last_name }}
                                </strong>

                                <span class="sd-stars">
                                    {{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', 5 - (int) $review->rating) }}
                                </span>
                            </div>

                            @if ($review->comment)
                                <p class="sd-review-comment">{{ $review->comment }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="sd-muted">لا توجد تقييمات لهذه الخدمة حتى الآن.</p>
                    @endforelse
                </section>
            </div>

            <aside class="sd-aside">
                <section class="sd-card sd-aside-card">
                    <p class="sd-price-label">سعر الخدمة</p>
                    <p class="sd-price">{{ number_format($vendorService->price, 2) }} د.أ</p>

                    @guest
                        <a class="sd-button" href="{{ route('login') }}">
                            سجّل الدخول للحجز
                        </a>
                    @elseif ($isCustomer)
                        <a class="sd-button" href="#booking">
                            اختر موعدًا
                        </a>

                        <form method="POST"
                              action="{{ route('favorites.toggle', $vendorService) }}">
                            @csrf
                            <button class="sd-button sd-button-outline" type="submit">
                                {{ $isFavorite ? '♥ إزالة من المفضلة' : '♡ إضافة للمفضلة' }}
                            </button>
                        </form>
                    @else
                        <p class="sd-muted">الحجز متاح للعملاء فقط.</p>
                    @endguest
                </section>

                <section class="sd-card sd-aside-card">
                    <h2 class="sd-heading">مقدّم الخدمة</h2>

                    <div class="sd-vendor">
                        @if ($vendor->profile_image)
                            <img class="sd-avatar"
                                 src="{{ asset('storage/' . $vendor->profile_image) }}"
                                 alt="صورة {{ $vendor->first_name }}">
                        @else
                            <span class="sd-avatar">
                                {{ mb_substr($vendor->first_name, 0, 1) }}
                            </span>
                        @endif

                        <div>
                            <p class="sd-vendor-name">
                                {{ $vendor->first_name }} {{ $vendor->last_name }}
                            </p>
                            <div class="sd-muted">
                                📍 {{ $vendor->location ?? 'الأردن' }}
                            </div>
                        </div>
                    </div>

                    <p class="sd-rating">
                        <span class="sd-stars">★</span>
                        {{ $reviewsCount ? number_format($rating, 1) : 'جديد' }}
                        · {{ $reviewsCount }} تقييم
                        @if ($vendor->vendorProfile)
                            · {{ $vendor->vendorProfile->experience_years }} سنوات خبرة
                        @endif
                    </p>

                    <a class="sd-button sd-button-outline"
                       href="{{ route('provider.show', $vendor) }}">
                        عرض ملف الفني
                    </a>
                </section>

                @if ($isCustomer)
                    <section class="sd-card sd-aside-card" id="booking">
                        <h2 class="sd-heading">المواعيد المتاحة</h2>
                        <p class="sd-booking-intro">
                            اختر موعدًا لإرسال طلب الحجز إلى مقدّم الخدمة.
                        </p>

                        @if ($hasSlots)
                            <form method="POST"
                                  action="{{ route('booking.store', $vendorService) }}">
                                @csrf

                                @foreach ($days as $day)
                                    @continue(count($day['slots']) === 0)

                                    <h3 class="sd-day">
                                        {{ $day['date']->locale('ar')->translatedFormat('l، d F') }}
                                    </h3>

                                    <div class="sd-slots">
                                        @foreach ($day['slots'] as $slot)
                                            <label class="sd-slot">
                                                <input type="radio"
                                                       name="slot"
                                                       value="{{ $slot['value'] }}"
                                                       required>
                                                <span>{{ $slot['start'] }} – {{ $slot['end'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endforeach

                                <p class="sd-booking-intro" style="margin-top:15px">
                                    يظهر خيار الدفع في حجوزاتك بعد موافقة الفني على الطلب.
                                </p>

                                <button class="sd-button" type="submit">
                                    إرسال طلب الحجز
                                </button>
                            </form>
                        @else
                            <p class="sd-muted">
                                لا توجد مواعيد متاحة خلال الأيام السبعة القادمة.
                            </p>
                        @endif
                    </section>
                @endif
            </aside>
        </div>
    </div>
</main>

@include('components.footer')
@include('components.mobile-nav-public')
@endsection