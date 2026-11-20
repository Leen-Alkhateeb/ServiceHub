@extends('layouts.app')
@section('title', 'المفضلة | ServiceHub')
@section('page_id', 'customer-favorites')

@section('content')
@include('components.navbar')

<style>
    .favorites-page {
        padding-block: 30px 64px;
    }

    .favorites-heading {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
    }

    .favorites-heading h1 {
        margin: 0 0 6px;
        font-size: clamp(26px, 4vw, 36px);
    }

    .favorites-heading p {
        margin: 0;
        color: #71817c;
    }

    .favorites-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    .favorite-product {
        overflow: hidden;
        padding: 0;
        border: 1px solid #e4ece8;
        border-radius: 16px;
        background: #fff;
    }

    .favorite-product .directory-photo {
        display: block;
        width: 100%;
        height: 210px;
        object-fit: cover;
    }

    .favorite-product-body {
        display: grid;
        gap: 9px;
        padding: 16px;
    }

    .favorite-product-category {
        color: #176b5b;
        font-size: 13px;
        font-weight: 700;
    }

    .favorite-product h2 {
        margin: 0;
        font-size: 20px;
    }

    .favorite-product-vendor,
    .favorite-product-location {
        margin: 0;
        color: #71817c;
    }

    .favorite-product-footer,
    .favorite-product-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .favorite-product-footer {
        padding-top: 10px;
        border-top: 1px solid #edf1ef;
    }

    .favorite-product-price {
        color: #176b5b;
        font-size: 19px;
        font-weight: 800;
    }

    .favorite-remove {
        border: 0;
        color: #a33131;
        background: transparent;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    .favorites-empty {
        grid-column: 1 / -1;
        padding: 38px 22px;
        border: 1px dashed #cbd9d3;
        border-radius: 16px;
        background: #fff;
        text-align: center;
    }

    @media (max-width: 900px) {
        .favorites-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 620px) {
        .favorites-grid {
            grid-template-columns: 1fr;
        }

        .favorites-heading {
            align-items: start;
            flex-direction: column;
        }

        .favorite-product .directory-photo {
            height: 190px;
        }
    }
</style>

<main class="container favorites-page">
    <section class="favorites-content">
        <header class="favorites-heading">
            <div>
                <h1>خدماتك المحفوظة</h1>
                <p>كل الخدمات التي أضفتها إلى المفضلة في مكان واحد.</p>
            </div>
            <a class="btn btn-outline" href="{{ route('market.index') }}">تصفح الخدمات</a>
        </header>

        <div class="favorites-grid">
            @forelse ($favorites as $favorite)
                @php
                    $item = $favorite->vendorService;
                    $categoryName = $item->service->category->name ?? $item->service->name;
                @endphp

                <article class="favorite-product">
                    <a href="{{ route('market.show', $item) }}" aria-label="تفاصيل {{ $item->service->name }}">
                        <x-directory-photo
                            :picture="$item->image ?: $item->service->image"
                            :trade="$categoryName"
                            kind="category"
                        />
                    </a>

                    <div class="favorite-product-body">
                        <span class="favorite-product-category">{{ $categoryName }}</span>
                        <h2>{{ $item->service->name }}</h2>
                        <p class="favorite-product-vendor">
                            مقدّم الخدمة: {{ $item->vendor->first_name }} {{ $item->vendor->last_name }}
                        </p>
                        @if ($item->vendor->location)
                            <p class="favorite-product-location">الموقع: {{ $item->vendor->location }}</p>
                        @endif

                        <div class="favorite-product-footer">
                            <strong class="favorite-product-price">{{ number_format($item->price, 2) }} د.أ</strong>
                            <div class="favorite-product-actions">
                                <form method="POST" action="{{ route('favorites.toggle', $item) }}">
                                    @csrf
                                    <button class="favorite-remove" type="submit" aria-label="إزالة الخدمة من المفضلة">
                                        إزالة ♥
                                    </button>
                                </form>
                                <a class="btn btn-primary btn-sm" href="{{ route('market.show', $item) }}">
                                    التفاصيل
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="favorites-empty">
                    <h2>ما أضفت خدمات للمفضلة بعد</h2>
                    <p class="muted">اضغط على أيقونة القلب في بطاقة الخدمة لحفظها هنا.</p>
                    <a class="btn btn-primary" href="{{ route('market.index') }}">اكتشف الخدمات</a>
                </div>
            @endforelse
        </div>
    </section>
</main>

@include('components.footer')
@include('components.mobile-nav-customer')
@endsection
