@extends('layouts.app')
@section('title', 'طلبات إضافة الخدمات | ServiceHub')
@section('page_id', 'admin-service-requests')
@section('content')
@include('components.navbar')
<main class="container page-head">
    <h1>طلبات إضافة الخدمات</h1>
    @if (session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
    @if ($errors->any())
        <ul class="notice" role="alert">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <div class="review-queue">
        @forelse ($requests as $offer)
            <article class="panel">
                <div class="section-head">
                    <div><h2>{{ $offer->service->name }}</h2><p>{{ $offer->service->category->name }} · {{ $offer->vendor->first_name }} {{ $offer->vendor->last_name }}</p></div>
                    <span class="hub-status" data-status="{{ match ($offer->status) { 'pending' => 'held', 'rejected' => 'issue', default => 'released' } }}">{{ match ($offer->status) { 'pending' => 'قيد المراجعة', 'rejected' => 'مرفوض', default => 'نشط' } }}</span>
                </div>
                <p>{{ $offer->description }}</p>
                <p><strong class="price">{{ number_format($offer->price, 2) }} د.أ</strong>@if ($offer->duration_minutes) · {{ $offer->duration_minutes }} دقيقة@endif</p>
                @if ($offer->status === 'pending')
                    <div class="moderation-actions">
                        <form method="POST" action="{{ route('admin.service-requests.decide', $offer) }}">
                            @csrf
                            <button class="btn btn-yellow" type="submit" name="decision" value="active">موافقة</button>
                        </form>
                        <form method="POST" action="{{ route('admin.service-requests.decide', $offer) }}">
                            @csrf
                            <label class="sr-only" for="reason-{{ $offer->id }}">سبب الرفض</label>
                            <input id="reason-{{ $offer->id }}" name="rejection_reason" maxlength="500" placeholder="سبب الرفض" required>
                            <button class="btn btn-danger" type="submit" name="decision" value="rejected">رفض</button>
                        </form>
                    </div>
                @elseif ($offer->rejection_reason)
                    <p>{{ $offer->rejection_reason }}</p>
                @endif
            </article>
        @empty
            <p>لا توجد طلبات.</p>
        @endforelse
    </div>
    {{ $requests->onEachSide(1)->links('components.pager') }}
</main>
@include('components.footer')
@endsection
