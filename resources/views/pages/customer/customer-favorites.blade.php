@extends('layouts.app')
@section('title', 'المفضلة')

@section('content')

@include('pages.partials.market-assets')

<div class="profile-layout">
    @include('pages.customer.menu')

    <section class="stack">
        <h2>المفضلة</h2>

        <div class="grid">
            @forelse ($favorites as $favorite)
                @php $item = $favorite->vendorService; @endphp
                <article class="card">
                    <h3>{{ $item->service->name }}</h3>
                    <p class="muted">{{ $item->vendor->first_name }} {{ $item->vendor->last_name }}</p>
                    <div class="service-foot">
                        <strong>{{ number_format($item->price, 2) }} د.أ</strong>
                        <div class="row">
                            <form method="POST" action="{{ route('favorites.toggle', $item) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm">إزالة</button>
                            </form>
                            <a href="{{ route('market.show', $item) }}" class="btn btn-primary btn-sm">تفاصيل الخدمة</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card empty">
                    <h3>ما في خدمات بالمفضلة لسا</h3>
                    <p class="muted">اضغط على القلب بأي خدمة لتحفظها هون.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>

@endsection