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
        @unless (View::hasSection('hide-auth-footer'))
            @include('components.footer')
        @endunless
    </div>
    @unless (View::hasSection('hide-auth-footer'))
        @include('components.mobile-nav-public')
    @endunless
@endsection
