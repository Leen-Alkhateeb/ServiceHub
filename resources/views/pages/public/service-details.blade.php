@extends('layouts.app')
@section('title', $vendorService->service->name)

@section('content')
@include('pages.partials.market-assets')

@php
    $cover      = $vendorService->image ? asset('storage/' . $vendorService->image) : null;
    $hasSlots   = collect($days)->contains(fn ($day) => count($day['slots']) > 0);
    $isCustomer = auth()->check() && auth()->user()->role === 'customer';
@endphp

<p class="muted"><a href="{{ route('market.index') }}">← الرجوع لسوق الخدمات</a></p>

@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="detail">

    <div class="stack">
        <div class="card">
            <div class="detail-cover" @if ($cover) style="background-image:url('{{ $cover }}')" @endif></div>
            <span class="badge badge-muted">{{ $vendorService->service->category->name }}</span>
            <h1>{{ $vendorService->service->name }}</h1>
            <p>{{ $vendorService->description ?? $vendorService->service->description }}</p>
        </div>

        <div class="card">
            <h3>التقييمات</h3>
            @forelse ($reviews as $review)
                <div class="review">
                    <strong>{{ $review->reviewer->first_name }} {{ $review->reviewer->last_name }}</strong>
                    <span class="stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                    @if ($review->comment) <p class="muted">{{ $review->comment }}</p> @endif
                </div>
            @empty
                <p class="muted">ما في تقييمات لسا.</p>
            @endforelse
        </div>
    </div>

    <aside class="stack">
        <div class="card">
            <p class="muted">السعر</p>
            <p class="price">{{ number_format($vendorService->price, 2) }} د.أ</p>

            @guest
                <a href="{{ url('/login') }}" class="btn btn-primary btn-block">سجّل دخول للحجز</a>
            @elseif ($isCustomer)
                <button type="button" class="btn btn-primary btn-block" data-open="booking-modal">احجز الآن</button>

                <form method="POST" action="{{ route('favorites.toggle', $vendorService) }}" class="mt">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-block">{{ $isFavorite ? '♥ إزالة من المفضلة' : '♡ إضافة للمفضلة' }}</button>
                </form>
            @else
                <p class="muted">الحجز متاح للعملاء فقط.</p>
            @endguest
        </div>

        <div class="card">
            <h3>مقدّم الخدمة</h3>
            <div class="vendor-line">
                @if ($vendor->profile_image)
                    <img class="avatar lg" src="{{ asset('storage/' . $vendor->profile_image) }}" alt="">
                @else
                    <span class="avatar lg">{{ mb_substr($vendor->first_name, 0, 1) }}</span>
                @endif
                <div>
                    <strong>{{ $vendor->first_name }} {{ $vendor->last_name }}</strong>
                    <div class="muted">{{ $vendor->location ?? 'الأردن' }}</div>
                </div>
            </div>

            <p class="meta muted">
                ★ {{ $reviewsCount ? number_format($rating, 1) : 'جديد' }} ({{ $reviewsCount }} تقييم)
                @if ($vendor->vendorProfile) · {{ $vendor->vendorProfile->experience_years }} سنوات خبرة @endif
            </p>

            <a href="{{ route('provider.show', $vendor) }}" class="btn btn-outline btn-block">عرض ملف مقدّم الخدمة</a>
        </div>
    </aside>
</div>

{{-- نافذة الحجز --}}
@if ($isCustomer)
    <div class="modal" id="booking-modal" role="dialog" aria-modal="true">
        <div class="modal-backdrop" data-close></div>
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>حجز الخدمة</h3>
                <button type="button" class="modal-close" data-close aria-label="إغلاق">✕</button>
            </div>

            <form method="POST" action="{{ route('booking.store', $vendorService) }}" class="modal-body">
                @csrf

                <div class="summary">
                    <div><span class="muted">الخدمة</span><strong>{{ $vendorService->service->name }}</strong></div>
                    <div><span class="muted">مقدّم الخدمة</span><strong>{{ $vendor->first_name }} {{ $vendor->last_name }}</strong></div>
                    <div><span class="muted">المجموع</span><strong>{{ number_format($vendorService->price, 2) }} د.أ</strong></div>
                </div>

                @error('slot') <p class="field-error">{{ $message }}</p> @enderror

                @if ($hasSlots)
                    @foreach ($days as $day)
                        @continue(count($day['slots']) === 0)

                        <p class="day-title">{{ $day['date']->locale('ar')->translatedFormat('l، d F') }}</p>
                        <div class="slots">
                            @foreach ($day['slots'] as $slot)
                                <label class="slot">
                                    <input type="radio" name="slot" value="{{ $slot['value'] }}" required>
                                    <span dir="ltr">{{ $slot['start'] }} – {{ $slot['end'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endforeach

                    <p class="muted">يتأكد الحجز بعد موافقة مقدّم الخدمة.</p>
                    <button type="submit" class="btn btn-primary btn-block">إرسال طلب الحجز</button>
                @else
                    <p class="muted">ما في مواعيد متاحة عند مقدّم الخدمة خلال الأيام السبعة القادمة.</p>
                @endif
            </form>
        </div>
    </div>
@endif
@endsection