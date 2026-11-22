@extends('layouts.app')
@section('title', 'تقييماتي')
@section('page_id', 'customer-reviews')
@section('content')
@include('components.navbar')
<main class="container layout">
    @include('components.sidebars.customer')
    <div class="dashboard-content">
        <h1>تقييماتي</h1>
        <section class="panel">
            @forelse ($reviews as $review)
                <article class="item-row"><div><h2>{{ $review->booking?->service_name ?? 'تقييم خدمة' }}</h2><p>{{ $review->comment }}</p></div><bdi aria-label="{{ $review->rating }} من 5">{{ str_repeat('★', min(5, max(0, (int) $review->rating))) }}</bdi></article>
            @empty
                <p>ما عندك تقييمات سابقة.</p>
            @endforelse
            {{ $reviews->onEachSide(1)->links('components.pager') }}
        </section>
    </div>
</main>
@include('components.footer')
@include('components.mobile-nav-customer')
@endsection
