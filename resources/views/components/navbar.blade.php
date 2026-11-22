
<header class="topbar">
    <div class="container nav">
        <a class="brand" href="{{ route('home') }}">
            <span class="brandmark">✓</span>
            <span class="word">{{ config('app.site_name') }}</span>
        </a>

        <nav class="navlinks" id="navlinks">
            <a href="{{ route('home') }}">الرئيسية</a>
            <a href="{{ route('categories.index') }}">أقسام الصيانة</a>
            <a href="{{ route('market.index') }}">خدمات</a>
            <a href="{{ route('home') }}#how-it-works">كيف تعمل المنصة</a>
            @auth
                @if (auth()->user()->status === 'active' && auth()->user()->role === 'admin')
                    @include('components.sidebars.admin')
                @elseif (auth()->user()->status === 'active' && auth()->user()->role === 'vendor')
                    @include('components.sidebars.vendor', ['hideOverview' => true])
                @elseif (auth()->user()->role === 'customer')
                    @include('components.sidebars.customer')
                @endif
            @endauth
        </nav>

        <div class="nav-actions account-actions">
            @auth
                @php
                    $account = auth()->user();
                    $dashboard = match ($account->role) {
                        'vendor' => 'vendor.dashboard',
                        'admin' => 'admin.dashboard',
                        default => null,
                    };

                    $favoriteCount = $account->role === 'customer'
                        ? $account->favorites()->count()
                        : 0;

                    $unread = $account->notifications()
                        ->where('is_read', false)
                        ->count();

                    $recentNotifications = $account->notifications()
                        ->latest('id')
                        ->limit(5)
                        ->get();
                @endphp

                @if ($dashboard && $account->status === 'active')
                    <a class="btn btn-yellow btn-sm" href="{{ route($dashboard) }}">{{ $account->role === 'vendor' ? 'لوحة الفني' : 'لوحة التحكم' }}</a>
                @endif
                <details class="account-dropdown">
                    <summary class="account-picture-link" aria-label="قائمة الحساب"><x-account-picture :account="$account" /></summary>
                    <div class="account-menu">
                        <a href="{{ route('account.profile') }}">ملفي الشخصي</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit">تسجيل الخروج</button>
                        </form>
                    </div>
                </details>

                <details class="notification-dropdown">
                    <summary
                        class="notification-link"
                        aria-label="الإشعارات، {{ $unread }} غير مقروءة"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            width="23"
                            height="23"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path
                                d="M18 8a6 6 0 0 0-12 0c0 6-3 7-3 9h18c0-2-3-3-3-9M10 21h4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
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

                        <a
                            class="btn btn-outline btn-sm"
                            href="{{ route('notifications.index') }}"
                        >
                            عرض المزيد
                        </a>
                    </div>
                </details>

                @if ($account->role === 'customer')
                    <a
                        class="btn btn-outline btn-sm favorite-nav-link"
                        style="position: relative; display: inline-grid; place-items: center; width: 42px; min-width: 42px; padding: 8px;"
                        href="{{ route('customer.favorites') }}"
                        title="المفضلة"
                        aria-label="المفضلة، {{ $favoriteCount }} خدمة محفوظة"
                    >
                        <svg
                            aria-hidden="true"
                            viewBox="0 0 24 24"
                            width="22"
                            height="22"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>

                        @if ($favoriteCount > 0)
                            <span class="notification-count">
                                {{ min($favoriteCount, 99) }}{{ $favoriteCount > 99 ? '+' : '' }}
                            </span>
                        @endif
                    </a>
                @endif

                @if ($account->role === 'customer' && $account->status === 'active')
                    <a class="btn btn-yellow btn-sm" href="{{ route('vendor.apply') }}">
                        انضم كفني
                    </a>
                @endif

            @else
                <a class="btn btn-outline btn-sm" href="{{ route('login') }}">
                    تسجيل الدخول
                </a>

                <a class="btn btn-yellow btn-sm" href="{{ route('register') }}">
                    إنشاء حساب
                </a>
            @endauth

            <button
                aria-label="القائمة"
                aria-controls="navlinks"
                aria-expanded="false"
                class="btn btn-outline btn-sm menu-toggle"
                id="menu-toggle"
                type="button"
            >
                ☰
            </button>
        </div>
    </div>
</header>
