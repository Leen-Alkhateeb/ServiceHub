<aside class="sidebar vendor-sidebar" aria-label="أقسام لوحة الفني">
    <div class="vendor-sidebar-menu">
        <div class="brand">مساحة الفني</div>
        @unless ($hideOverview ?? false)
            <a href="{{ route('vendor.dashboard') }}">نظرة عامة</a>
        @endunless
        <a href="{{ route('vendor.vendor-bookings.index') }}">طلبات الحجز</a>
        @if(config('instant.enabled') && auth()->user()?->vendorProfile?->application_status === 'approved')
            <a href="{{ route('instant.vendor') }}" @if(request()->routeIs('instant.vendor')) aria-current="page" @endif>الطلبات الفورية</a>
        @endif
        <a href="{{ route('vendor.availability.index') }}">الجدول الأسبوعي</a>
        <a href="{{ route('vendor.profile') }}#services">خدماتي</a>
        <a href="{{ route('vendor.profile', ['tab' => 'portfolio']) }}#portfolio">أعمالي</a>
        <a href="{{ route('vendor.wallet') }}">السجل المالي</a>
        <a href="{{ route('vendor.reviews') }}">التقييمات</a>
        <a href="{{ route('vendor.profile') }}">الملف الشخصي</a>
    </div>
</aside>
