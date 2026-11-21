@extends('layouts.app')
@section('title', 'ملفي الشخصي | ServiceHub')
@section('content')
@include('components.navbar')
<main class="container page-head">
    <header class="technician-hero">
        <x-account-picture :account="$account" class="account-profile-avatar" />
        <div class="technician-heading">
            <h1>{{ $account->first_name }} {{ $account->last_name }}</h1>
            <p>{{ $account->location }}</p>
            @include('components.account-picture-editor')
            @include('components.account-details-editor')
        </div>
    </header>
    <x-account-tabs :tab="$tab" :tabs="['overview' => 'معلوماتي', 'payments' => 'مدفوعاتي']" route-name="account.profile" />
    @if ($entries)
        <x-account-history :tab="$tab" :entries="$entries" :filters="$filters" :categories="$historyCategories" route-name="account.profile" />
    @else
    <section class="section">
        <div class="panel">
            <h2>معلومات الحساب</h2>
            <dl class="booking-details">
                <dt>البريد الإلكتروني</dt><dd><bdi>{{ $account->email }}</bdi></dd>
                <dt>رقم الهاتف</dt><dd><bdi>{{ $account->phone }}</bdi></dd>
            </dl>
        </div>
    </section>
    @endif
</main>
@include('components.footer')
@include('components.mobile-nav-public')
@endsection

