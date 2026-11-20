@extends('layouts.app')
@section('title', 'الإشعارات')

@section('content')
@include('pages.partials.market-assets')

<div class="profile-layout">
    @include('pages.customer.menu')

    <section class="stack">
        <h2>الإشعارات</h2>

        @forelse ($notifications as $notification)
            <div class="card notice {{ $notification->is_read ? '' : 'unread' }}">
                <div>
                    <strong>{{ $notification->title }}</strong>
                    <p class="muted">{{ $notification->message }}</p>
                </div>
                <span class="muted">{{ $notification->created_at->locale('ar')->diffForHumans() }}</span>
            </div>
        @empty
            <div class="card empty"><p class="muted">ما عندك إشعارات.</p></div>
        @endforelse
    </section>
</div>
@endsection