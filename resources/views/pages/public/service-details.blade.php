@extends('layouts.app')

@section('title', $vendorService->service->name . ' | ServiceHub')
@section('page_id', 'service-details')

@section('content')
@include('components.navbar')

@php

    $imagePath = $vendorService->image ?: $vendorService->service->image;
    $categoryName = $vendorService->service->category->name ?? '';

    $fallbackImage = match ($categoryName) {
        'السباكة' => 'category-plumbing.jpg',
        'الكهرباء', 'الطاقة الشمسية', 'الإنارة' => 'category-electrical.jpg',
        'النجارة' => 'category-carpentry.jpg',
        'صيانة الحدائق' => 'category-garden.jpg',
        'التنظيف المتخصص' => 'category-cleaning.jpg',
        'الأقفال', 'الأرضيات', 'الشبكات المنزلية' => 'category-tools.jpg',
        default => 'category-maintenance.jpg',
    };

    if ($imagePath && filter_var($imagePath, FILTER_VALIDATE_URL)) {
        $cover = $imagePath;
    } elseif ($imagePath && str_starts_with($imagePath, 'assets/')) {
        $cover = asset($imagePath);
    } elseif ($imagePath) {
        $imagePath = preg_replace(
            '#^storage/#',
            '',
            str_replace('\\', '/', $imagePath)
        );

        $cover = asset('storage/' . ltrim($imagePath, '/'));
    } else {
        $cover = asset('assets/demo/directory/' . $fallbackImage);
    }

    $isCustomer = auth()->check()
        && auth()->user()->role === 'customer';

    // Days that have slots (free or booked), with how many are still free.
    $bookableDays = collect($days)
        ->filter(fn ($day) => count($day['slots']) > 0)
        ->map(fn ($day) => $day + [
            'free' => collect($day['slots'])->where('taken', false)->count(),
        ])
        ->values();

    $hasSlots = $bookableDays->contains(fn ($day) => $day['free'] > 0);
    $firstFreeDay = $bookableDays->search(fn ($day) => $day['free'] > 0);

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
        height: 340px;
        background-color: #e4eee9;
        background-position: center;
        background-size: cover;
        background-repeat: no-repeat;
    }

    .sd-card-content,
    .sd-aside-card {
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

    .sd-review-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
        font-size: 14px;
    }

    .sd-review-pagination a,
    .sd-review-pagination span {
        padding: 8px 12px;
        border: 1px solid var(--sd-line);
        border-radius: 8px;
        color: var(--sd-green);
        background: #fff;
        text-decoration: none;
    }

    .sd-review-pagination .sd-page-disabled {
        color: var(--sd-muted);
        background: #f6f8f7;
    }

    @media (max-width: 800px) {
        .sd-layout {
            grid-template-columns: 1fr;
        }

        .sd-cover {
            height: 270px;
        }
    }

    @media (max-width: 520px) {
        .sd-container {
            width: min(100% - 24px, 1120px);
        }

        .sd-cover {
            height: 220px;
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

        <div class="sd-layout">
            <div class="sd-main">
                <article class="sd-card">
                    <div class="sd-cover"
                         role="img"
                         aria-label="{{ $vendorService->service->name }}"
                         style="background-image: url('{{ $cover }}')">
                    </div>

                    <div class="sd-card-content">
                        <span class="sd-category">{{ $categoryName }}</span>

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

                <section class="sd-card sd-card-content">
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

                    @if (method_exists($reviews, 'hasPages') && $reviews->hasPages())
                        <nav class="sd-review-pagination" aria-label="صفحات التقييمات">
                            @if ($reviews->onFirstPage())
                                <span class="sd-page-disabled">السابق</span>
                            @else
                                <a href="{{ $reviews->previousPageUrl() }}">السابق</a>
                            @endif

                            <span>
                                صفحة {{ $reviews->currentPage() }}
                                من {{ $reviews->lastPage() }}
                            </span>

                            @if ($reviews->hasMorePages())
                                <a href="{{ $reviews->nextPageUrl() }}">التالي</a>
                            @else
                                <span class="sd-page-disabled">التالي</span>
                            @endif
                        </nav>
                    @endif
                </section>
            </div>

            <aside class="sd-aside">
                <section class="sd-card sd-aside-card">
                    <p class="sd-price-label">سعر الخدمة</p>
                    <p class="sd-price">
                        {{ number_format($vendorService->price, 2) }} د.أ
                    </p>

                    @guest
                        <a class="sd-button" href="{{ route('login') }}">
                            سجّل الدخول للحجز
                        </a>
                    @elseif ($isCustomer)
                        <a class="sd-button" href="#booking">اختر موعدًا</a>


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
                                 src="{{ str_starts_with($vendor->profile_image, 'profile-photos/')
                                     ? route('account.picture.show', $vendor)
                                     : asset('storage/' . ltrim($vendor->profile_image, '/')) }}"
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
                            اختر موعدًا مناسبًا وأرسل طلب الحجز. الدفع بيصير بعد موافقة مقدّم الخدمة.
                        </p>

                        @if ($hasSlots)
                            <form method="POST"
                                  action="{{ route('booking.store', $vendorService) }}">
                                @csrf

                                <div class="sd-picker" data-slot-picker>
                                    <div>
                                        <label for="slot-day">اليوم</label>
                                        <select id="slot-day" data-slot-day>
                                            @foreach ($bookableDays as $index => $day)
                                                <option value="{{ $index }}"
                                                        @selected($index === $firstFreeDay)
                                                        @disabled($day['free'] === 0)>
                                                    {{ $day['date']->locale('ar')->translatedFormat('l، d F') }}{{ $day['free'] === 0 ? ' — مكتمل' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="slot-time-{{ $firstFreeDay }}">الوقت</label>
                                        @foreach ($bookableDays as $index => $day)
                                            <select id="slot-time-{{ $index }}"
                                                    name="slot"
                                                    data-slot-time="{{ $index }}"
                                                    required
                                                    @if ($index !== $firstFreeDay) hidden disabled @endif>
                                                <option value="">اختر الوقت</option>
                                                @foreach ($day['slots'] as $slot)
                                                    <option value="{{ $slot['value'] }}" @disabled($slot['taken'])>
                                                        {{ $slot['start'] }} – {{ $slot['end'] }}{{ $slot['taken'] ? ' — محجوز' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endforeach
                                    </div>                                </div>

                                <button class="sd-button" type="submit" style="margin-top:18px">
                                    أرسل طلب الحجز
                                </button>
                            </form>
                        @else
                            <p class="sd-muted">
                                لا توجد مواعيد متاحة خلال الثلاثين يومًا القادمة.
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