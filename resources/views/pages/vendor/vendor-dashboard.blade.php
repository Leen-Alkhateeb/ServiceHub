@extends('layouts.app')
@section('title', 'لوحة الفني | ServiceHub')
@section('page_id', 'vendor-dashboard')
@section('content')
@include('components.navbar')
<main class="container layout">
    @include('components.sidebars.vendor')
    <div class="dashboard-content">
        <h1>لوحة الفني</h1>
        <p>أهلًا {{ $vendor->first_name }}.</p>
        <div class="balance-cards">
            @foreach ($stats as $name => $value)
                <article class="balance-card"><span class="balance-label">{{ $name }}</span><strong>{{ $value }}</strong></article>
            @endforeach
        </div>
        <div class="hero-actions">
            @if ($vendor->vendorProfile)
                <a class="btn btn-yellow" href="{{ route('vendor.profile') }}">ملفي الشخصي</a>
                @if ($vendor->vendorProfile->application_status !== 'approved')
                    <a class="btn btn-outline" href="{{ route('vendor.apply') }}">حالة طلب الانضمام</a>
                @endif
            @endif
            <a class="btn btn-outline" href="{{ route('vendor.wallet') }}">السجل المالي</a>
        </div>
    </div>
</main>
@include('components.footer')
@include('components.mobile-nav-vendor')
@endsection
