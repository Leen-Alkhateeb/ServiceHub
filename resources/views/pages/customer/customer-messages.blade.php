@extends('layouts.app')
@section('title', 'رسائل الدعم')
@section('page_id', 'customer-messages')
@section('content')
@include('components.navbar')
<main class="container layout">
    @include('components.sidebars.customer')
    <div class="dashboard-content">
        <h1>رسائل الدعم</h1>
        <p><a class="btn btn-yellow" href="{{ route('contact') }}">رسالة جديدة</a></p>
        <section class="panel">
            @forelse ($messages as $message)
                <article class="item-row"><div><h2>{{ $message->subject }}</h2><p>{{ $message->message }}</p></div><span class="pill">{{ $message->status === 'reviewed' ? 'تمت المراجعة' : 'قيد المراجعة' }}</span></article>
            @empty
                <p>ما عندك رسائل دعم.</p>
            @endforelse
            {{ $messages->onEachSide(1)->links('components.pager') }}
        </section>
    </div>
</main>
@include('components.footer')
@include('components.mobile-nav-customer')
@endsection
