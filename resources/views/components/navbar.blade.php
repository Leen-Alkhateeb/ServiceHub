<header class="topbar">
    <div class="container nav">
        <a class="brand" href="{{ route('home') }}">
            <span class="brandmark">✓</span>
            <span class="word">ServiceHub</span>
        </a>

        <nav class="navlinks" id="navlinks">
            <a href="{{ route('home') }}">الرئيسية</a>
            <a href="{{ route('categories.index') }}">أقسام الصيانة</a>
            <a href="{{ route('market.index') }}">خدمات</a>
            <a href="{{ route('home') }}#how-it-works">كيف تعمل المنصة</a>
        </nav>

        <div class="nav-actions account-actions">
            @auth
                @php
                    $account = auth()->user();
                    $unread = $account->notifications()
                        ->where('is_read', false)
                        ->count();
                    $recentNotifications = $account->notifications()
                        ->latest('id')
                        ->limit(5)
                        ->get();
                @endphp

                <a class="account-picture-link"
                   href="{{ route('account.profile') }}"
                   aria-label="فتح ملفي الشخصي">
                    <x-account-picture :account="$account" />
                </a>

                <details class="notification-dropdown">
                    <summary class="notification-link"
                             aria-label="الإشعارات، {{ $unread }} غير مقروءة">
                        <svg viewBox="0 0 24 24"
                             width="23"
                             height="23"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="1.8"
                             aria-hidden="true">
                            <path d="M18 8a6 6 0 0 0-12 0c0 6-3 7-3 9h18c0-2-3-3-3-9M10 21h4"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>

                        @if ($unread > 0)
                            <span class="notification-count">
                                {{ min($unread, 99) }}{{ $unread > 99 ? '+' : '' }}
                            </span>
                        @endif
                    </summary>

                    <div class="notification-menu">
                        <h2>الإشعارات</h2>

                        <ul>
                            @forelse ($recentNotifications as $alert)
                                <li @class(['unread' => ! $alert->is_read])>
                                    <strong>{{ $alert->title }}</strong>
                                    <p>{{ \Illuminate\Support\Str::limit($alert->message, 120) }}</p>
                                    <x-dialog-date-time :value="$alert->created_at" />
                                </li>
                            @empty
                                <li>لا توجد إشعارات.</li>
                            @endforelse
                        </ul>

                        <a class="btn btn-outline btn-sm"
                           href="{{ route('notifications.index') }}">
                            عرض المزيد
                        </a>
                    </div>
                </details>

                @if ($account->role === 'customer' && $account->status === 'active')
                    <a class="btn btn-yellow btn-sm" href="{{ route('vendor.apply') }}">
                        انضم كفني
                    </a>
                @elseif ($account->role === 'admin' && $account->status === 'active')
                    <a class="btn btn-yellow btn-sm"
                       href="{{ route('admin.service-requests.index') }}">
                        طلبات الخدمات
                    </a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline btn-sm" type="submit">
                        تسجيل الخروج
                    </button>
                </form>
            @else
                <a class="btn btn-outline btn-sm" href="{{ route('login') }}">
                    تسجيل الدخول
                </a>

                <a class="btn btn-yellow btn-sm" href="{{ route('register') }}">
                    إنشاء حساب
                </a>
            @endauth

            <button aria-label="القائمة"
                    class="btn btn-outline btn-sm menu-toggle"
                    id="menu-toggle"
                    type="button">
                ☰
            </button>
        </div>
    </div>
</header>