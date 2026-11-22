<!doctype html>
<html lang="ar" dir="rtl" data-turbo="false">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ServiceHub منصة لحجز خدمات الصيانة">
    <title>@yield('title', 'ServiceHub')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @foreach (['app.js', 'servicehub-pages.js'] as $script)
        <script type="module" src="{{ asset('assets/'.$script) }}?v={{ filemtime(public_path('assets/'.$script)) }}"></script>
    @endforeach
    <script src="{{ asset('assets/theme.js') }}?v={{ filemtime(public_path('assets/theme.js')) }}" defer></script>

    @stack('styles')

    @foreach (['style.css', 'servicehub-pages.css', 'theme.css'] as $stylesheet)
        <x-public-stylesheet :file="$stylesheet" />
    @endforeach
</head>

<body data-page="@yield('page_id')">
    @yield('content')

    @auth
        @include('components.archive-dialog')
    @endauth

</body>

</html>
