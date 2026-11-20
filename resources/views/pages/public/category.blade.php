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
    
</main>
@include('components.footer')
@include('components.mobile-nav-public')
@endsection
