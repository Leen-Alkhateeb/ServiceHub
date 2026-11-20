@extends('layouts.app')

@push('styles')
    @if (is_file(public_path('hot')) || is_file(public_path('build/manifest.json')))
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @endif
@endpush

@section('content')
    @include('components.navbar')
    @yield('auth-content')
    @include('components.footer')
    @include('components.mobile-nav-public')
@endsection
