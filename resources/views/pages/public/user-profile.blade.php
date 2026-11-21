@extends('layouts.app')

@section('title', 'الملف الشخصي | ServiceHub')

@section('content')
    @include('components.navbar')

    <main class="container page-head">
        <header class="technician-hero">
            <x-account-picture :account="$user" class="account-profile-avatar" />
            <div class="technician-heading">
                <h1>{{ $user->first_name }} {{ $user->last_name }}</h1>
                <p>{{ $user->location }}</p>
            </div>
        </header>

        <section class="section">
            <div class="panel">
                <h2>معلومات الحساب</h2>
                <dl class="booking-details">
                    <dt>نوع الحساب</dt>
                    <dd>{{ match ($user->role) { 'admin' => 'أدمن', 'vendor' => 'فني', 'customer' => 'عميل', default => $user->role } }}</dd>
                    <dt>الحالة</dt>
                    <dd>{{ $user->status === 'active' ? 'فعال' : 'غير فعال' }}</dd>
                    <dt>الموقع</dt>
                    <dd>{{ $user->location ?: 'غير محدد' }}</dd>
                    @if (auth()->user()->role === 'admin')
                        <dt>البريد الإلكتروني</dt>
                        <dd><bdi>{{ $user->email }}</bdi></dd>
                        <dt>رقم الهاتف</dt>
                        <dd><bdi>{{ $user->phone ?: 'غير محدد' }}</bdi></dd>
                    @endif
                </dl>
                @if ($user->role === 'vendor' && $user->status === 'active' && $user->vendorProfile?->application_status === 'approved')
                    <a class="btn btn-outline" href="{{ route('provider.show', $user) }}">عرض ملف الفني وخدماته</a>
                @endif
            </div>
        </section>
    </main>

    @include('components.footer')
    @include('components.mobile-nav-public')
@endsection
