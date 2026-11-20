@extends('layouts.app')
@section('title', $title.' | ServiceHub')
@section('content')
@include('components.navbar')
<main class="container page-head directory-page">
    <h1>{{ $title }}</h1>
    <div class="grid">
        @isset($categories)
            @forelse ($categories as $category)
                <x-category-card :category="$category" />
            @empty
                <p>لا توجد أقسام متاحة حاليًا.</p>
            @endforelse
        @else
            @forelse ($vendors as $vendor)
                <x-vendor-card :vendor="$vendor" />
            @empty
                <p>لا يوجد فنيون متاحون حاليًا.</p>
            @endforelse
        @endisset
    </div>
    {{ ($categories ?? $vendors)->onEachSide(1)->links('components.pager') }}
</main>
@include('components.footer')
@include('components.mobile-nav-public')
@endsection
