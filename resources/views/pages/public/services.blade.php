@extends('layouts.app')
@section('title', 'سوق الخدمات')

@section('content')

@include('pages.partials.market-assets')

<div class="page-head">
    <h1>سوق الخدمات</h1>
    <p class="muted">{{ $services->total() }} خدمة متاحة</p>
</div>

<div class="market">

    {{-- الفلاتر --}}
    <aside class="card filters">
        <form method="GET" action="{{ route('market.index') }}">
            <div class="field">
                <label for="q">بحث</label>
                <input class="input" id="q" type="text" name="q" value="{{ request('q') }}" placeholder="خدمة أو فني">
            </div>

            <div class="field">
                <label for="category">القسم</label>
                <select class="input" id="category" name="category">
                    <option value="">كل الأقسام</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="location">المحافظة</label>
                <select class="input" id="location" name="location">
                    <option value="">كل المحافظات</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location }}" @selected(request('location') === $location)>{{ $location }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label>السعر (د.أ)</label>
                <div class="row">
                    <input class="input half" type="number" min="0" step="0.5" name="min_price" value="{{ request('min_price') }}" placeholder="الأدنى">
                    <input class="input half" type="number" min="0" step="0.5" name="max_price" value="{{ request('max_price') }}" placeholder="الأعلى">
                </div>
            </div>

            <div class="field">
                <label for="rating">التقييم</label>
                <select class="input" id="rating" name="rating">
                    <option value="">أي تقييم</option>
                    @foreach (['4' => '4 نجوم فأعلى', '4.5' => '4.5 نجوم فأعلى', '4.8' => '4.8 نجوم فأعلى'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('rating') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="sort">الترتيب</label>
                <select class="input" id="sort" name="sort">
                    <option value="">الأحدث</option>
                    <option value="price_asc"  @selected(request('sort') === 'price_asc')>السعر: من الأقل للأعلى</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>السعر: من الأعلى للأقل</option>
                </select>
            </div>

            <button type="submit" class="btn btn-dark btn-block">تطبيق الفلاتر</button>
            <a href="{{ route('market.index') }}" class="btn btn-outline btn-block mt">مسح الكل</a>
        </form>
    </aside>

    {{-- الكروت --}}
    <section>
        <div class="grid">
            @forelse ($services as $item)
                @php
                    $vendor = $item->vendor;
                    $cover  = $item->image ? asset('storage/' . $item->image) : null;
                    $isFav  = in_array($item->id, $favoriteIds);
                    $rating = $vendor->reviews_received_avg_rating;
                @endphp

                <article class="card service-card">
                    <div class="service-cover" @if ($cover) style="background-image:url('{{ $cover }}')" @endif>
                        <span class="badge badge-muted tag">{{ $item->service->category->name }}</span>

                        @auth
                            @if (auth()->user()->role === 'customer')
                                <form method="POST" action="{{ route('favorites.toggle', $item) }}">
                                    @csrf
                                    <button type="submit" class="fav {{ $isFav ? 'on' : '' }}" aria-label="المفضلة">{{ $isFav ? '♥' : '♡' }}</button>
                                </form>
                            @endif
                        @endauth
                    </div>

                    <div class="service-body">
                        <h3>{{ $item->service->name }}</h3>

                        {{-- صورة الفني من users.profile_image --}}
                        <a href="{{ route('provider.show', $vendor) }}" class="vendor-line">
                            @if ($vendor->profile_image)
                                <img class="avatar" src="{{ asset('storage/' . $vendor->profile_image) }}" alt="">
                            @else
                                <span class="avatar">{{ mb_substr($vendor->first_name, 0, 1) }}</span>
                            @endif
                            <span>{{ $vendor->first_name }} {{ $vendor->last_name }}</span>
                        </a>

                        <p class="meta muted">
                            ★ {{ $rating ? number_format($rating, 1) : 'جديد' }}
                            @if ($vendor->reviews_received_count) ({{ $vendor->reviews_received_count }}) @endif
                            · {{ $vendor->location ?? 'الأردن' }}
                        </p>

                        <div class="service-foot">
                            <strong>{{ number_format($item->price, 2) }} د.أ</strong>
                            <a href="{{ route('market.show', $item) }}" class="btn btn-primary btn-sm">تفاصيل الخدمة</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card empty">
                    <h3>ما في خدمات مطابقة</h3>
                    <p class="muted">جرّب تغيير الفلاتر أو مسحها.</p>
                </div>
            @endforelse
        </div>

        @if ($services->lastPage() > 1)
            <div class="pager">
                @if ($services->onFirstPage())
                    <span class="btn btn-outline btn-sm disabled">السابق</span>
                @else
                    <a href="{{ $services->previousPageUrl() }}" class="btn btn-outline btn-sm">السابق</a>
                @endif

                <span class="muted">صفحة {{ $services->currentPage() }} من {{ $services->lastPage() }}</span>

                @if ($services->hasMorePages())
                    <a href="{{ $services->nextPageUrl() }}" class="btn btn-outline btn-sm">التالي</a>
                @else
                    <span class="btn btn-outline btn-sm disabled">التالي</span>
                @endif
            </div>
        @endif
    </section>
</div>
