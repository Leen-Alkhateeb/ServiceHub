@extends('layouts.app')
@section('title', isset($selectedCategory) && $selectedCategory ? $selectedCategory->name.' | الخدمات | ServiceHub' : 'الخدمات | ServiceHub')
@section('page_id', 'services')

@section('content')
@include('components.navbar')

<style>
    .services-page {
        --green: #176b5b;
        --green-dark: #105346;
        --ink: #18342e;
        --muted: #71817c;
        --line: #e4ece8;
        direction: rtl;
        min-height: 70vh;
        padding-bottom: 60px;
        color: var(--ink);
        background: #f7faf8;
    }

    .services-page *,
    .services-page *::before,
    .services-page *::after {
        box-sizing: border-box;
    }

    .services-wrap {
        width: min(1180px, calc(100% - 32px));
        margin-inline: auto;
    }

    .services-hero {
        padding: 42px 0 32px;
        background: linear-gradient(125deg, #eaf5ef, #fbfcfb);
        border-bottom: 1px solid var(--line);
    }

    .services-hero h1 {
        margin: 0 0 8px;
        font-size: clamp(28px, 4vw, 40px);
        font-weight: 800;
    }

    .services-hero p {
        margin: 0;
        color: var(--muted);
    }

    .services-search {
        display: flex;
        gap: 10px;
        max-width: 760px;
        margin-top: 22px;
        padding: 8px;
        border: 1px solid var(--line);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 8px 24px #18342e0c;
    }

    .services-search input {
        flex: 1;
        min-width: 0;
        padding: 12px;
        border: 0;
        outline: 0;
        background: transparent;
        color: var(--ink);
        font: inherit;
    }

    .services-search button,
    .filter-submit,
    .service-details-link {
        border: 0;
        border-radius: 9px;
        color: #fff;
        background: var(--green);
        text-decoration: none;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    .services-search button {
        padding: 10px 22px;
    }

    .services-search button:hover,
    .filter-submit:hover,
    .service-details-link:hover {
        color: #fff;
        background: var(--green-dark);
    }

    .services-layout {
        display: grid;
        grid-template-columns: 250px minmax(0, 1fr);
        align-items: start;
        gap: 22px;
        padding-top: 26px;
    }

    .filter-panel,
    .service-card {
        border: 1px solid var(--line);
        border-radius: 15px;
        background: #fff;
        box-shadow: 0 6px 22px #18342e08;
    }

    .filter-panel {
        position: sticky;
        top: 18px;
        padding: 19px;
    }

    .filter-panel h2 {
        margin: 0 0 17px;
        font-size: 18px;
    }

    .filter-field {
        margin-bottom: 14px;
    }

    .filter-field label {
        display: block;
        margin-bottom: 6px;
        color: #40564f;
        font-size: 13px;
        font-weight: 700;
    }

    .filter-field input,
    .filter-field select,
    .sort-select {
        width: 100%;
        min-height: 42px;
        padding: 8px 10px;
        border: 1px solid var(--line);
        border-radius: 9px;
        outline: none;
        background: #fff;
        color: var(--ink);
        font: inherit;
    }

    .filter-field input:focus,
    .filter-field select:focus,
    .sort-select:focus,
    .services-search:focus-within {
        border-color: #78aa9b;
        box-shadow: 0 0 0 3px #176b5b15;
    }

    .price-range {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 7px;
    }

    .filter-submit {
        width: 100%;
        padding: 11px;
    }

    .clear-filters {
        display: block;
        margin-top: 12px;
        color: var(--green);
        text-align: center;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
    }

    .results-heading {
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 12px;
        margin-bottom: 15px;
    }

    .results-heading h2 {
        margin: 0 0 4px;
        font-size: 22px;
    }

    .results-heading p {
        margin: 0;
        color: var(--muted);
        font-size: 14px;
    }

    .sort-select {
        width: 200px;
    }

    .services-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .service-card {
        overflow: hidden;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .service-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px #18342e12;
    }

    .service-image {
        display: block;
        position: relative;
        height: 180px;
        overflow: hidden;
        background: linear-gradient(135deg, #e3eee9, #f2f6f4);
        text-decoration: none;
    }

    .service-image img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .25s ease;
    }

    .service-card:hover .service-image img {
        transform: scale(1.04);
    }

    .service-category {
        position: absolute;
        top: 11px;
        right: 11px;
        padding: 6px 10px;
        border-radius: 999px;
        color: var(--ink);
        background: #fffffff0;
        font-size: 12px;
        font-weight: 700;
    }

    .service-card-body {
        padding: 15px;
    }

    .service-name {
        margin: 0 0 12px;
        font-size: 18px;
        line-height: 1.45;
    }

    .service-name a {
        color: var(--ink);
        text-decoration: none;
    }

    .vendor-link {
        display: flex;
        align-items: center;
        gap: 9px;
        color: var(--ink);
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
    }

    .vendor-avatar {
        display: grid;
        flex: 0 0 38px;
        width: 38px;
        height: 38px;
        place-items: center;
        overflow: hidden;
        border-radius: 50%;
        color: var(--green);
        background: #e8f3ee;
        object-fit: cover;
    }

    .service-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px 12px;
        margin: 12px 0;
        color: var(--muted);
        font-size: 13px;
    }

    .service-rating {
        color: #98700e;
        font-weight: 700;
    }

    .service-description {
        min-height: 40px;
        margin: 0 0 13px;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.6;
    }

    .service-card-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding-top: 12px;
        border-top: 1px solid var(--line);
    }

    .service-price {
        color: var(--green);
        font-size: 16px;
        font-weight: 800;
        white-space: nowrap;
    }

    .service-details-link {
        display: inline-block;
        padding: 9px 11px;
        font-size: 13px;
        white-space: nowrap;
    }

    .favorite-button {
        width: 100%;
        margin-top: 9px;
        padding: 8px;
        border: 1px solid var(--line);
        border-radius: 9px;
        color: var(--green);
        background: #fff;
        font: inherit;
        cursor: pointer;
    }

    .empty-results {
        grid-column: 1 / -1;
        padding: 38px 18px;
        color: var(--muted);
        text-align: center;
    }

    .empty-results h3 {
        margin-top: 0;
        color: var(--ink);
    }

    .services-pagination {
        margin-top: 22px;
    }

    @media (max-width: 980px) {
        .services-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        .services-layout {
            grid-template-columns: 1fr;
        }

        .filter-panel {
            position: static;
        }
    }

    @media (max-width: 520px) {
        .services-wrap {
            width: calc(100% - 24px);
        }

        .services-hero {
            padding: 30px 0 24px;
        }

        .services-search {
            flex-direction: column;
        }

        .services-search button {
            padding: 11px;
        }

        .services-grid {
            grid-template-columns: 1fr;
        }

        .service-image {
            height: 205px;
        }

        .results-heading {
            align-items: stretch;
            flex-direction: column;
        }

        .sort-select {
            width: 100%;
        }
    }
</style>

<main class="services-page">
    <section class="services-hero">
        <div class="services-wrap">
            <h1>{{ $selectedCategory ? 'خدمات قسم '.$selectedCategory->name : 'خدمات الصيانة' }}</h1>
            <p>{{ $selectedCategory ? 'تصفح خدمات هذا القسم، واستخدم الفلاتر لتحديد الموقع والسعر والتقييم المناسب.' : 'اختار الخدمة المناسبة وقارن بين الفنيين والأسعار والتقييمات.' }}</p>

            <form class="services-search" method="GET" action="{{ route('market.index') }}">
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="ابحث باسم الخدمة أو الفني"
                    aria-label="ابحث باسم الخدمة أو الفني"
                >
                @if ($selectedCategory)
                    <input type="hidden" name="category" value="{{ $selectedCategory->id }}">
                @endif
                <button type="submit">بحث</button>
            </form>
        </div>
    </section>

    <div class="services-wrap services-layout">
        <aside class="filter-panel">
            <h2>فلترة الخدمات</h2>

            <form method="GET" action="{{ route('market.index') }}">
                <div class="filter-field">
                    <label for="filter-q">الخدمة أو الفني</label>
                    <input id="filter-q"
                           type="search"
                           name="q"
                           value="{{ request('q') }}"
                           placeholder="اكتب كلمة البحث">
                </div>

                <div class="filter-field">
                    <label for="category">القسم</label>
                    <select id="category" name="category">
                        <option value="">كل الأقسام</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}"
                                @selected(request('category') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-field">
                    <label for="location">الموقع</label>
                    <select id="location" name="location">
                        <option value="">كل المواقع</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location }}"
                                @selected(request('location') === $location)>
                                {{ $location }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-field">
                    <label>السعر بالدينار</label>
                    <div class="price-range">
                        <input type="number"
                               min="0"
                               step="0.5"
                               name="min_price"
                               value="{{ request('min_price') }}"
                               placeholder="من">
                        <input type="number"
                               min="0"
                               step="0.5"
                               name="max_price"
                               value="{{ request('max_price') }}"
                               placeholder="إلى">
                    </div>
                </div>

                <div class="filter-field">
                    <label for="rating">أقل تقييم</label>
                    <select id="rating" name="rating">
                        <option value="">كل التقييمات</option>
                        @foreach (['3' => '3 نجوم فأعلى', '4' => '4 نجوم فأعلى', '4.5' => '4.5 نجوم فأعلى'] as $value => $label)
                            <option value="{{ $value }}"
                                @selected(request('rating') === (string) $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <input type="hidden" name="sort" value="{{ request('sort', 'latest') }}">

                <button class="filter-submit" type="submit">تطبيق الفلاتر</button>
                <a class="clear-filters" href="{{ route('market.index', $selectedCategory ? ['category' => $selectedCategory->id] : []) }}">مسح الفلاتر</a>
            </form>
        </aside>

        <section>
            <div class="results-heading">
                <div>
                    <h2>{{ $selectedCategory ? 'خدمات '.$selectedCategory->name : 'الخدمات المتاحة' }}</h2>
                    <p>{{ $services->total() }} خدمة متاحة</p>
                </div>

                <form method="GET" action="{{ route('market.index') }}">
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <input type="hidden" name="category" value="{{ request('category') }}">
                    <input type="hidden" name="location" value="{{ request('location') }}">
                    <input type="hidden" name="min_price" value="{{ request('min_price') }}">
                    <input type="hidden" name="max_price" value="{{ request('max_price') }}">
                    <input type="hidden" name="rating" value="{{ request('rating') }}">

                    <select class="sort-select"
                            name="sort"
                            aria-label="ترتيب النتائج"
                            onchange="this.form.submit()">
                        <option value="latest"
                            @selected(request('sort', 'latest') === 'latest')>
                            الأحدث
                        </option>
                        <option value="price_asc"
                            @selected(request('sort') === 'price_asc')>
                            السعر من الأقل للأعلى
                        </option>
                        <option value="price_desc"
                            @selected(request('sort') === 'price_desc')>
                            السعر من الأعلى للأقل
                        </option>
                    </select>
                </form>
            </div>

            <div class="services-grid">
                @forelse ($services as $item)
                    @php
                        $vendor = $item->vendor;
                        $categoryName = $item->service->category->name ?? '';
                        $imagePath = $item->image ?: $item->service->image;

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
                            $image = $imagePath;
                        } elseif ($imagePath && str_starts_with($imagePath, 'assets/')) {
                            $image = asset($imagePath);
                        } elseif ($imagePath) {
                            $imagePath = preg_replace(
                                '#^storage/#',
                                '',
                                str_replace('\\', '/', $imagePath)
                            );
                            $image = asset('storage/' . ltrim($imagePath, '/'));
                        } else {
                            $image = asset('assets/demo/directory/' . $fallbackImage);
                        }

                        $rating = $vendor->reviews_received_avg_rating;
                        $isFavorite = in_array($item->id, $favoriteIds ?? []);
                        $description = $item->description
                            ?? $item->service->description
                            ?? 'خدمة متاحة للحجز';
                    @endphp

                    <article class="service-card">
                        <a class="service-image"
                           href="{{ route('market.show', $item) }}"
                           aria-label="تفاصيل {{ $item->service->name }}">
                            <img src="{{ $image }}"
                                 alt="{{ $item->service->name }}"
                                 loading="lazy">
                            <span class="service-category">{{ $categoryName }}</span>
                        </a>

                        <div class="service-card-body">
                            <h3 class="service-name">
                                <a href="{{ route('market.show', $item) }}">
                                    {{ $item->service->name }}
                                </a>
                            </h3>

                            <a class="vendor-link" href="{{ route('provider.show', $vendor) }}">
                                @if ($vendor->profile_image)
                                    <img class="vendor-avatar"
                                         src="{{ asset('storage/' . $vendor->profile_image) }}"
                                         alt="صورة {{ $vendor->first_name }}">
                                @else
                                    <span class="vendor-avatar">
                                        {{ mb_substr($vendor->first_name, 0, 1) }}
                                    </span>
                                @endif

                                <span>
                                    {{ $vendor->first_name }} {{ $vendor->last_name }}
                                </span>
                            </a>

                            <div class="service-meta">
                                <span class="service-rating">
                                    ★ {{ $rating ? number_format($rating, 1) : 'جديد' }}
                                    @if ($vendor->reviews_received_count)
                                        ({{ $vendor->reviews_received_count }} تقييم)
                                    @endif
                                </span>
                                <span>📍 {{ $vendor->location ?? 'الأردن' }}</span>
                            </div>

                            <p class="service-description">
                                {{ \Illuminate\Support\Str::limit($description, 90) }}
                            </p>

                            <div class="service-card-bottom">
                                <span class="service-price">
                                    {{ number_format($item->price, 2) }} د.أ
                                </span>
                                <a class="service-details-link"
                                   href="{{ route('market.show', $item) }}">
                                    تفاصيل الخدمة
                                </a>
                            </div>

                            @auth
                                @if (auth()->user()->role === 'customer')
                                    <form method="POST"
                                          action="{{ route('favorites.toggle', $item) }}">
                                        @csrf
                                        <button class="favorite-button" type="submit">
                                            {{ $isFavorite
                                                ? '♥ إزالة من المفضلة'
                                                : '♡ إضافة للمفضلة' }}
                                        </button>
                                    </form>
                                @endif
                            @endauth
                        </div>
                    </article>
                @empty
                    <div class="service-card empty-results">
                        <h3>ما لقينا خدمات مطابقة</h3>
                        <p>غيّر كلمات البحث أو الفلاتر وحاول مرة ثانية.</p>
                    </div>
                @endforelse
            </div>

            @if ($services->lastPage() > 1)
                <div class="services-pagination">
                    {{ $services->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
</main>

@include('components.footer')
@include('components.mobile-nav-public')
@endsection
