<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $code }} · {{ $heading }} | ServiceHub</title>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/servicehub-pages.css') }}">
</head>
<body>
    <main class="container hub-error-page">
        <section class="panel hub-error-panel" aria-labelledby="error-title">
            <a class="brand" href="{{ route('home') }}"><span class="brandmark">✓</span>ServiceHub</a>
            <p class="hub-error-code"><bdi>{{ $code }}</bdi></p>
            <h1 id="error-title">{{ $heading }}</h1>
            <p class="muted">{{ $description }}</p>
            <div class="hero-actions">
                <a class="btn btn-yellow" href="{{ route('home') }}">العودة للرئيسية</a>
                <a class="btn btn-outline" href="{{ route('market.index') }}">تصفّح الخدمات</a>
            </div>
        </section>
    </main>
</body>
</html>
