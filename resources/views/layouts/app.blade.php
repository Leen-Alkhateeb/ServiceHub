<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="ServiceHub منصة لحجز خدمات الصيانة">
<title>@yield('title', 'ServiceHub')</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
@stack('styles')
<link rel="stylesheet" href="/assets/style.css">
<link rel="stylesheet" href="/assets/servicehub-pages.css">
</head>
<body data-page="@yield('page_id')">
@yield('content')
@auth
    @include('components.archive-dialog')
@endauth
<script src="/assets/app.js" defer></script>
<script src="/assets/servicehub-pages.js" defer></script>
</body></html>
