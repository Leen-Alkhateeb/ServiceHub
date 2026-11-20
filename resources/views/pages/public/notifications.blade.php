@extends('layouts.app')
@section('title', 'الإشعارات | ServiceHub')
@section('page_id', 'notifications')
@section('content')
@include('components.navbar')
<main class="container page-head">
    <h1>الإشعارات</h1>
    <section class="panel">
        @forelse ($notifications as $notification)
            <article class="item-row notification-item">
                <div>
                    <h2>{{ $notification->title }}</h2>
                    <p>{{ $notification->message }}</p>
                    <time>{{ $notification->created_at?->format('Y-m-d H:i') }}</time>
                </div>
                @if (! $notification->is_read)
                    <form action="{{ route('notifications.read', $notification) }}" method="POST">
                        @csrf
                        <button class="btn btn-outline btn-sm" type="submit">تحديد كمقروء</button>
                    </form>
                @endif
            </article>
        @empty
            <p>لا توجد إشعارات.</p>
        @endforelse
        {{ $notifications->onEachSide(1)->links('components.pager') }}
    </section>
</main>
@include('components.footer')
@include('components.mobile-nav-public')
@endsection
