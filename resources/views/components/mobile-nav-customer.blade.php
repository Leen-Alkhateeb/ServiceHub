<nav class="mobile-nav" aria-label="روابط العميل">
    <a href="{{ route('market.index') }}">اكتشف الخدمات</a>
    <a href="{{ route('customer.favorites') }}">المفضلة</a>
    <a href="{{ route('account.profile', ['tab' => 'bookings']) }}">طلباتي</a>
    <a href="{{ route('account.profile') }}">حسابي</a>
</nav>
