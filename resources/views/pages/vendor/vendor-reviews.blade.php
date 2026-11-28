@extends('layouts.app')
@section('title', 'تقييم العملاء')
@section('page_id', 'vendor-reviews')
@section('content')
@include('components.navbar')
<main class="container layout vendor-workspace">
    @include('components.sidebars.vendor')
    <div class="dashboard-content vendor-workspace-content">
        <div class="dash-title vendor-page-header">
            <div><h1>تقييم العملاء</h1><p class="muted">يمكنك تقييم العميل مرة واحدة بعد إتمام الحجز.</p></div>
            <a class="btn btn-outline" href="{{ route('vendor.profile') }}">ملف الفني</a>
        </div>
        @include('components.booking-filters', ['action' => route('vendor.reviews')])
        @if (session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
        <section class="panel">
            @forelse ($bookings as $booking)
                @php
                    $review = $booking->reviews->first();
                    $ratedStars = $review ? max(0, min(5, (int) $review->rating)) : 0;
                @endphp
                <article class="review-booking">
                    <div>
                        <h2>{{ $booking->service_name }} <bdi>#{{ $booking->id }}</bdi></h2>
                        <p><a href="{{ route('users.profile.show', $booking->customer) }}">{{ $booking->customer->first_name }} {{ $booking->customer->last_name }}</a> · <bdi>{{ $booking->booking_date->format('Y-m-d') }}</bdi></p>
                        @if ($review)
                            <p><strong class="rating-display" aria-label="{{ $ratedStars }} من 5">{{ str_repeat('★', $ratedStars).str_repeat('☆', 5 - $ratedStars) }}</strong></p>
                            @if ($review->comment)<p>{{ $review->comment }}</p>@endif
                        @elseif ($booking->status !== 'completed' || ! $booking->completed_at)
                            <p class="muted">يتاح التقييم بعد إتمام الحجز.</p>
                        @endif
                    </div>
                    @if ($booking->status === 'completed' && $booking->completed_at)
                        <div class="moderation-actions">
                            <button class="btn btn-outline btn-sm" type="button" data-open-dialog="rate-{{ $booking->id }}">{{ $review ? 'تعديل التقييم' : 'تقييم العميل' }}</button>
                            @if ($review)
                                <x-archive-button :action="route('vendor.reviews.destroy', $booking)" message="هل تريد حذف تقييمك لهذا الحجز؟" />
                            @endif
                        </div>
                        @php
                            $failedRating = (int) old('review_booking_id') === (int) $booking->id && $errors->hasAny(['rating', 'comment']);
                        @endphp
                        <dialog class="hub-dialog rating-dialog" id="rate-{{ $booking->id }}" aria-labelledby="rate-title-{{ $booking->id }}" @if ($failedRating) data-auto-dialog @endif>
                            <div class="dialog-head">
                                <h2 id="rate-title-{{ $booking->id }}">{{ $review ? 'تعديل التقييم' : 'تقييم العميل' }}</h2>
                                <x-modal-close data-close-dialog />
                            </div>
                            <p><a href="{{ route('users.profile.show', $booking->customer) }}">{{ $booking->customer->first_name }} {{ $booking->customer->last_name }}</a> · {{ $booking->service_name }}</p>
                            @if ($failedRating)
                                <ul class="notice" role="alert">@foreach (array_merge($errors->get('rating'), $errors->get('comment')) as $message)<li>{{ $message }}</li>@endforeach</ul>
                            @endif
                            <form method="POST" action="{{ route($review ? 'vendor.reviews.update' : 'vendor.reviews.store', $booking) }}">
                                @csrf
                                @if ($review) @method('PATCH') @endif
                                <fieldset class="rating-stars">
                                    <legend>اختر عدد النجوم</legend>
                                    <div>
                                        @foreach (range(5, 1) as $star)
                                            <label>
                                                <input class="sr-only" type="radio" name="rating" value="{{ $star }}" @checked((int) ($failedRating ? old('rating') : $review?->rating) === $star) required>
                                                <span aria-hidden="true">★</span>
                                                <span class="sr-only">{{ $star }} من 5 نجوم</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                                <div class="field">
                                    <label for="review-{{ $booking->id }}">تعليق (اختياري)</label>
                                    <textarea id="review-{{ $booking->id }}" name="comment" maxlength="2000" rows="3">{{ $failedRating ? (is_string(old('comment')) ? old('comment') : '') : $review?->comment }}</textarea>
                                </div>
                                <button class="btn btn-yellow btn-sm" type="submit">حفظ التقييم</button>
                            </form>
                        </dialog>
                    @endif
                </article>
            @empty
                <p>لا توجد حجوزات مطابقة.</p>
            @endforelse
            {{ $bookings->onEachSide(1)->links('components.pager') }}
        </section>
    </div>
</main>
@include('components.footer')
@include('components.mobile-nav-vendor')
@endsection
