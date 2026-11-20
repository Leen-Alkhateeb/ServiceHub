@extends('layouts.app')

@push('styles')
    @if (is_file(public_path('hot')) || is_file(public_path('build/manifest.json')))
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @endif
@endpush

@section('content')
    <div class="auth-shell">
        @include('components.navbar')
        <div class="auth-content">@yield('auth-content')</div>
        @include('components.footer')
    </div>
    @include('components.mobile-nav-public')
@endsection
