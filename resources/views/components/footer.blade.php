@php
     
    $footerCategories = \App\Models\Category::where('is_deleted', false)->orderBy('id')->take(3)->get();
@endphp
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="brand"><span class="brandmark">✓</span>{{ config('app.site_name') }}</div>
                <p class="muted">منصة لحجز خدمات الصيانة والإصلاح ومتابعة الطلبات بكل وضوح.</p>
            </div>
            <div>
                <b>الأقسام</b>
                @foreach ($footerCategories as $footerCategory)
                    <a href="{{ route('categories.show', $footerCategory) }}">{{ $footerCategory->name }}</a>
                @endforeach
                <a href="{{ route('categories.index') }}">كل الأقسام</a>
            </div>
            <div>
                <b>المنصة</b>
                <a href="{{ route('about') }}">من نحن</a>
                <a href="{{ route('home') }}#how-it-works">كيف تعمل المنصة</a>
                <a href="{{ route('vendor.apply') }}">انضم كفني</a>
            </div>
            <div>
                <b>المساعدة</b>
                <a href="{{ route('faq') }}">الأسئلة الشائعة</a>
                <a href="{{ route('contact') }}">تواصل معنا</a>
                <a href="{{ route('account.profile') }}">حسابي</a>
            </div>
        </div>
    </div>
</footer>
