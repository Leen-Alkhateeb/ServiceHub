@extends('layouts.app')
@section('title', $category->name.' | ServiceHub')
@section('content')
@include('components.navbar')
<main class="container page-head directory-page">
    <a class="project-back" href="{{ route('categories.index') }}">→ جميع أقسام الصيانة</a>
    <header class="panel category-heading">
        <h1>{{ $category->name }}</h1>
        <p>{{ $category->description }}</p>
    </header>
    <section class="section">
        <h2>الفنيون في قسم {{ $category->name }}</h2>
        <div class="grid">
            @forelse ($vendors as $vendor)
                <x-vendor-card :vendor="$vendor" />
            @empty
                <p>لا يوجد فنيون يقدمون خدمات في هذا القسم حاليًا.</p>
            @endforelse
        </div>
        {{ $vendors->onEachSide(1)->links('components.pager') }}
    </section>
</main>
@include('components.footer')
@include('components.mobile-nav-public')
@endsection
