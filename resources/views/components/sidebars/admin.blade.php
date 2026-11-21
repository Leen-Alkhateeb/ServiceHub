@php
    // [group title, [[link, label, icon path], ...]] — same links as before, grouped with icons.
    $adminMenu = [
        ['', [
            ['/admin/dashboard', 'نظرة عامة', 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
        ]],
        ['المستخدمون', [
            ['/admin/Users', 'المستخدمون', 'M17 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM21 20v-1a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8'],
            ['/admin/admin-applications', 'طلبات الانضمام', 'M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9zM14 3v6h6M9 15l2 2 4-4'],
        ]],
        ['الكتالوج', [
            [route('admin.categories'), 'الأقسام', 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z'],
            ['/admin/admin-services', 'كتالوج الخدمات', 'M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01'],
            ['/admin/vendor-service-requests', 'اعتماد الخدمات', 'M12 3l7 4v5c0 4.5-3 8-7 9-4-1-7-4.5-7-9V7zM9 12l2 2 4-4'],
        ]],
        ['العمليات', [
            [route('admin.bookings.index'), 'الحجوزات', 'M8 3v4M16 3v4M4 9h16M5 5h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z'],
            ['/admin/admin-cancellations', 'الإلغاءات', 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM15 9l-6 6M9 9l6 6'],
            ['/admin/admin-refunds', 'طلبات الاسترداد', 'M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5'],
        ]],
        ['المالية', [
            [route('admin.payments.index'), 'المدفوعات', 'M3 6h18v12H3zM3 10h18M7 15h4'],
            ['/admin/admin-wallet', 'حركات المحفظة', 'M20 7H5a2 2 0 0 1 0-4h13v4M4 5v13a2 2 0 0 0 2 2h14V7M16 13.5h.01'],
        ]],
        ['التواصل', [
            ['/admin/admin-reviews', 'التقييمات', 'M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.6l-5.4 2.9 1.2-6-4.5-4.2 6.1-.7z'],
            [route('admin.messages.index'), 'رسائل التواصل', 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'],
        ]],
    ];
@endphp
<aside class="sidebar admin-sidebar">
    <div class="brand">
        <span class="brandmark">✓</span>ServiceHub
    </div>

    @foreach ($adminMenu as [$groupTitle, $links])
        @if ($groupTitle)
            <span class="sidebar-group">{{ $groupTitle }}</span>
        @endif

        @foreach ($links as [$href, $label, $icon])
            <a href="{{ $href }}">
                <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="{{ $icon }}" />
                </svg>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    @endforeach
</aside>
