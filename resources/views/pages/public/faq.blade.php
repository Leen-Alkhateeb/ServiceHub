@extends('layouts.app')
@section('title', 'الأسئلة الشائعة')
@section('page_id', 'faq')
@section('content')
@include('components.navbar')
<main class="container page-head">
    <h1>الأسئلة الأكثر تكرارًا</h1>
    @include('components.home-faq')
</main>
@include('components.footer')
@include('components.mobile-nav-public')
@endsection
