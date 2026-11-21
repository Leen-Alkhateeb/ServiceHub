
@extends('layouts.app')

@section('title', 'لوحة الإدارة | ServiceHub')
@section('page_id', 'admin-dashboard')

@section('content')
@include('components.navbar')

<div class="container layout">
    @include('components.sidebars.admin')

    <main class="admin-overview">
        <div class="dash-title">
            <div>
                <span class="small muted">لوحة الإدارة</span>
                <h1>إحصائيات المنصة</h1>
                <p class="muted">متابعة نشاط ServiceHub وآخر التحديثات.</p>
            </div>
        </div>

        <!-- Statistics -->
        <section class="admin-stat-grid">
             <div class="admin-stat">
                <span>إجمالي الارباح المسجلة</span>
                <strong>{{ number_format($stats['paidAmount']*0.15, 2) }}</strong>
                <small>صافي ربح المنصة</small>
                <a href="{{ route('admin.payments.index') }}" class="admin-card-link">عرض المدفوعات <span aria-hidden="true">←</span></a>
            </div>

            <div class="admin-stat">
                <span>إجمالي المستخدمين</span>
                <strong>{{ number_format($stats['users']) }}</strong>
                <small>جميع الحسابات</small>
                <a href="{{ route('admin.admin.Users') }}" class="admin-card-link" data-page="users">عرض المستخدمين <span aria-hidden="true">←</span></a>
            </div>

            <div class="admin-stat">
                <span>الفنيون</span>
                <strong>{{ number_format($stats['vendors']) }}</strong>
                <small>حسابات الفنيين</small>
                <a href="{{ route('admin.admin.Users', ['role' => 'vendor']) }}" class="admin-card-link">عرض الفنيين <span aria-hidden="true">←</span></a>
            </div>

            <div class="admin-stat">
                <span>إجمالي الحجوزات</span>
                <strong>{{ number_format($stats['bookings']) }}</strong>
                <small>جميع حالات الحجز</small>
                <a href="{{ route('admin.bookings.index') }}" class="admin-card-link">عرض الحجوزات <span aria-hidden="true">←</span></a>
            </div>

            <div class="admin-stat">
                <span>طلبات الفنيين المعلقة</span>
                <strong>{{ number_format($stats['pendingVendors']) }}</strong>
                <small>بانتظار المراجعة</small>
                <a href="{{ route('admin.admin-applications') }}" class="admin-card-link">مراجعة الطلبات <span aria-hidden="true">←</span></a>
            </div>

            <div class="admin-stat">
                <span>طلبات الاسترجاع المعلقة</span>
                <strong>{{ number_format($stats['pendingRefunds']) }}</strong>
                <small>تحتاج قرار الإدارة</small>
                <a href="{{ route('admin.refunds.index', ['status' => 'pending']) }}" class="admin-card-link">عرض الاسترجاعات <span aria-hidden="true">←</span></a>
            </div>
        </section>

        <!-- Charts -->
        <section class="admin-chart-grid">
            <div class="panel">
                <div class="admin-section-heading">
                    <h3>الحجوزات خلال آخر 7 أيام</h3>
                </div>
                <p class="muted">عدد الحجوزات الجديدة يوميًا</p>
                <div class="chart-container">
                    <canvas id="bookingsChart"></canvas>
                </div>
            </div>

            <div class="panel">
                <div class="admin-section-heading">
                    <h3>توزيع حالات الحجوزات</h3>
                </div>
                <p class="muted">عدد الحجوزات حسب الحالة</p>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Latest Users -->
        <section class="panel admin-section">
            <div class="admin-section-heading">
                    <h3>أحدث المستخدمين</h3>
                    <a href="{{ route('admin.admin.Users') }}" class="admin-section-link" data-page="users">عرض المستخدمين <span aria-hidden="true">←</span></a>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>الدور</th>
                            <th>تاريخ التسجيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestUsers as $user)
                            <tr>
                                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role }}</td>
                                <td>{{ $user->created_at?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">لا يوجد مستخدمون بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Latest Reviews and Refunds -->
        <div class="admin-feed-grid">
            <section class="panel admin-section">
                <div class="admin-section-heading">
                    <h3>أحدث التقييمات</h3>
                    <a href="{{ route('admin.reviews.index') }}" class="admin-section-link">عرض التقييمات <span aria-hidden="true">←</span></a>
                </div>

                @forelse($latestReviews as $review)
                    <div class="admin-feed-item">
                        <div class="admin-feed-top">
                            <strong>
                                {{ $review->reviewer?->first_name ?? 'مستخدم' }}
                                {{ $review->reviewer?->last_name ?? '' }}
                            </strong>
                            <span class="admin-rating">
                                ★ {{ $review->rating }}/5
                            </span>
                        </div>

                        <p>{{ $review->comment ?: 'تقييم بدون تعليق' }}</p>

                        <small class="muted">
                            للمستخدم:
                            {{ $review->reviewedUser?->first_name ?? '—' }}
                            {{ $review->reviewedUser?->last_name ?? '' }}
                            ·
                            {{ $review->created_at?->format('Y-m-d') ?? '—' }}
                        </small>
                    </div>
                @empty
                    <p class="muted">لا توجد تقييمات بعد.</p>
                @endforelse
            </section>

            <section class="panel admin-section">
                <div class="admin-section-heading">
                    <h3>أحدث طلبات الاسترجاع</h3>
                    <a href="{{ route('admin.refunds.index') }}" class="admin-section-link">عرض الاسترجاعات <span aria-hidden="true">←</span></a>
                </div>

                @forelse($latestRefunds as $refund)
                    <div class="admin-feed-item">
                        <div class="admin-feed-top">
                            <strong>طلب #{{ $refund->id }}</strong>
                            <span class="admin-status">
                                {{ $refund->status }}
                            </span>
                        </div>

                        <p>{{ \Illuminate\Support\Str::limit($refund->reason, 90) }}</p>

                        <small class="muted">
                            {{ $refund->customer?->first_name ?? '—' }}
                            {{ $refund->customer?->last_name ?? '' }}
                            · الحجز #{{ $refund->booking_id }}
                            · {{ $refund->created_at?->format('Y-m-d') ?? '—' }}
                        </small>
                    </div>
                @empty
                    <p class="muted">لا توجد طلبات استرجاع بعد.</p>
                @endforelse
            </section>
        </div>

        <!-- Vendor Applications -->
        <section class="panel admin-section">
            <div class="admin-section-heading">
                    <h3>أحدث طلبات انضمام الفنيين</h3>
                    <a href="{{ route('admin.admin-applications') }}" class="admin-section-link">عرض الطلبات <span aria-hidden="true">←</span></a>
                </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>الفني</th>
                            <th>نوع العمل</th>
                            <th>الحالة</th>
                            <th>تاريخ التقديم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestVendorApplications as $application)
                            <tr>
                                <td>
                                    {{ $application->user?->first_name ?? '—' }}
                                    {{ $application->user?->last_name ?? '' }}
                                </td>
                                <td>{{ $application->work_type }}</td>
                                <td>{{ $application->application_status }}</td>
                                <td>
                                    {{ $application->applied_at
                                        ? \Carbon\Carbon::parse($application->applied_at)->format('Y-m-d')
                                        : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">لا توجد طلبات انضمام بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

@include('components.footer')
@include('components.mobile-nav-admin')

<script>
(async function () {
    const bookingsCanvas = document.getElementById('bookingsChart');
    const statusCanvas = document.getElementById('statusChart');
    const bookings = @json($bookingChart);
    const statuses = @json($statusChart);

    if (!window.Chart) {
        window.serviceHubChartLibrary ??= new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.addEventListener('load', resolve, { once: true });
            script.addEventListener('error', reject, { once: true });
            document.head.append(script);
        });

        try {
            await window.serviceHubChartLibrary;
        } catch {
            delete window.serviceHubChartLibrary;
            if (bookingsCanvas.isConnected) {
                for (const canvas of [bookingsCanvas, statusCanvas]) {
                    const message = document.createElement('p');
                    message.textContent = 'تعذّر تحميل الرسم البياني.';
                    canvas.replaceWith(message);
                }
            }
            return;
        }
    }

    if (!bookingsCanvas.isConnected || !statusCanvas.isConnected) return;
    const Chart = window.Chart;
    Chart.defaults.font.family = "'IBM Plex Sans Arabic', 'Cairo', sans-serif";

    const bookingsChart = new Chart(bookingsCanvas, {
        type: 'line',
        data: {
            labels: bookings.labels,
            datasets: [{
                label: 'الحجوزات',
                data: bookings.values,
                borderColor: '#202124',
                backgroundColor: 'rgba(255,210,31,.20)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#FFD21F'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    const statusChart = new Chart(statusCanvas, {
        type: 'doughnut',
        data: {
            labels: statuses.labels,
            datasets: [{
                data: statuses.values,
                backgroundColor: [
                    '#FFD21F',
                    '#202124',
                    '#0B6B52',
                    '#2457C5',
                    '#B42318',
                    '#7A4B00',
                    '#9CA3AF',
                    '#7C3AED'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 16
                    }
                }
            }
        }
    });
    const events = new AbortController();
    const dispose = () => {
        bookingsChart.destroy();
        statusChart.destroy();
        events.abort();
    };
    document.addEventListener('turbo:before-cache', dispose, { signal: events.signal });
    document.addEventListener('turbo:before-render', dispose, { signal: events.signal });
})();
</script>
@endsection
