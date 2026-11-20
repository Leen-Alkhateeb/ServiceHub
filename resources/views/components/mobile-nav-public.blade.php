<nav class="mobile-nav" aria-label="روابط المنصة">
    <a href="{{ route('home') }}">الرئيسية</a>
    <a href="{{ route('categories.index') }}">أقسام الصيانة</a>
    <a href="{{ route('vendors.index') }}">الفنيون</a>
    @guest
        <a href="{{ route('login') }}">تسجيل الدخول</a>
    @else
        @if (auth()->user()->role === 'customer' && auth()->user()->status === 'active')
            <a href="{{ route('vendor.apply') }}">انضم كفني</a>
        @endif
    @endguest
</nav>
