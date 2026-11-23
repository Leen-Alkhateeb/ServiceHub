
@extends('layouts.app')

@section('title', 'طلبات الاسترداد')
@section('page_id', 'admin-refunds')

@section('content')
    @include('components.navbar')

    @php
        $statusLabels = [
            'pending' => 'قيد المراجعة',
            'accepted' => 'مقبول',
            'rejected' => 'مرفوض',
        ];
    @endphp


    <div class="container layout admin-refunds">
        @include('components.sidebars.admin')

        <div class="refunds-page">
            @if (session('status'))
                <p class="notice" role="status">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <div class="notice" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>طلبات الاسترداد</h1>
                    <p class="muted">
                        مراجعة اعتراضات العملاء المقدمة خلال
                        24 ساعة من إكمال الخدمة.
                    </p>
                </div>
            </div>

            
            <div class="stats-grid">
                <div class="panel">
                    <span class="small muted">إجمالي الطلبات</span>
                    <h2>{{ $stats['total'] ?? 0 }}</h2>
                </div>

                <div class="panel">
                    <span class="small muted">قيد المراجعة</span>
                    <h2>{{ $stats['pending'] ?? 0 }}</h2>
                </div>

                <div class="panel">
                    <span class="small muted">الطلبات المقبولة</span>
                    <h2>{{ $stats['accepted'] ?? 0 }}</h2>
                </div>

                <div class="panel">
                    <span class="small muted">الطلبات المرفوضة</span>
                    <h2>{{ $stats['rejected'] ?? 0 }}</h2>
                </div>
            </div>

            
            <div class="panel">
                <div class="section-head">
                    <div>
                        <h2>إدارة طلبات الاسترداد</h2>
                        <p class="muted">
                            ابحث عن الطلبات وراجع تفاصيل الاعتراضات.
                        </p>
                    </div>
                </div>

                <form method="GET"
                      action="{{ url()->current() }}"
                      class="filter-form">

                    <div class="form-grid">
                        <div>
                            <label for="refund-search">البحث</label>

                            <input
                                type="search"
                                id="refund-search"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="رقم الحجز، الخدمة، العميل أو مقدم الخدمة"
                            >
                        </div>

                        <div>
                            <label for="refund-status">حالة الطلب</label>

                            <select id="refund-status" name="status">
                                <option value="">جميع الحالات</option>

                                @foreach ($statusLabels as $value => $label)
                                    <option
                                        value="{{ $value }}"
                                        @selected(request('status') === $value)
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="moderation-actions">
                        <button type="submit" class="btn btn-yellow">
                            بحث وتصفية
                        </button>

                        <a href="{{ url()->current() }}"
                           class="btn btn-outline">
                            إعادة تعيين
                        </a>
                    </div>
                </form>
            </div>

            
            <div class="panel">
                <div class="section-head">
                    <div>
                        <h2>قائمة طلبات الاسترداد</h2>
                        <p class="muted">
                            اختر عرض التفاصيل لمراجعة الاعتراض واتخاذ القرار.
                        </p>
                    </div>
                </div>

                @if ($refunds->isNotEmpty())
                    <p class="refunds-scroll-hint" id="refunds-scroll-hint">↔ اسحب الجدول يمين أو شمال لعرض باقي التفاصيل.</p>
                @endif
                <div class="table-wrap refunds-table-scroll" @if ($refunds->isNotEmpty()) tabindex="0" role="region" aria-label="جدول طلبات الاسترداد" aria-describedby="refunds-scroll-hint" @endif>
                    <table>
                        <thead>
                            <tr>
                                <th scope="col" class="refunds-customer-column">العميل</th>
                                <th>رقم الطلب</th>
                                <th>الخدمة</th>
                                <th>مقدم الخدمة</th>
                                <th>مبلغ الحجز</th>
                                <th>تاريخ الطلب</th>
                                <th>الحالة</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($refunds as $refund)
                                @php
                                    $booking = $refund->booking;
                                    $customer = $refund->customer;
                                    $vendor = $booking?->vendorService?->vendor;

                                    $status = $refund->status;

                                    $statusClass = match ($status) {
                                        'pending' => 'yellow',
                                        'accepted' => 'green',
                                        'rejected' => 'red',
                                        default => 'yellow',
                                    };

                                    $amount = (float) ($booking?->original_price ?? 0);
                                @endphp

                                <tr>
                                    <td class="refunds-customer-column">
                                        {{ $customer?->first_name ?? '' }}
                                        {{ $customer?->last_name ?? '' }}
                                    </td>
                                    <td>{{ $refunds->firstItem() + $loop->index }}</td>
                                    <td>
                                        {{ $booking?->service_name ?? $booking?->vendorService?->service?->name ?? '—' }}
                                    </td>

                                    <td>
                                        {{ $vendor?->first_name ?? '' }}
                                        {{ $vendor?->last_name ?? '' }}
                                    </td>

                                    <td>
                                        <strong class="price">
                                            {{ number_format($amount, 2) }} د.أ
                                        </strong>
                                    </td>

                                    <td>
                                        {{ $refund->created_at?->format('Y/m/d') ?? '—' }}
                                    </td>

                                    <td>
                                        <span class="pill {{ $statusClass }}">
                                            {{ $statusLabels[$status] ?? $status }}
                                        </span>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-outline"
                                            data-refund-details="{{ $refund->id }}"
                                        >
                                            عرض التفاصيل
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <h3>لا توجد طلبات استرداد</h3>
                                            <p class="muted">
                                                لا توجد طلبات مطابقة للبحث أو الفلترة الحالية.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($refunds && method_exists($refunds, 'links'))
                    <div class="pagination-wrap">
                        {{ $refunds->onEachSide(1)->links('components.pager') }}
                    </div>
                @endif
            </div>

            
            @foreach ($refunds ?? [] as $refund)
                @php
                    $booking = $refund->booking;
                    $customer = $refund->customer;
                    $vendor = $booking?->vendorService?->vendor;

                    $amount = (float) ($booking?->original_price ?? 0);

                    $customerRefund = $amount * 0.70;
                    $vendorAcceptedShare = $amount * 0.15;
                    $platformShare = $amount * 0.15;
                    $vendorNormalShare = $amount * 0.85;
                @endphp

                <dialog
                    id="refund-details-{{ $refund->id }}"
                    class="panel refund-dialog"
                    aria-labelledby="refund-title-{{ $refund->id }}"
                >
                    <div class="section-head">
                        <div>
                            <span class="small muted">
                                تفاصيل طلب الاسترداد
                            </span>

                            <h2 id="refund-title-{{ $refund->id }}">
                                الطلب #{{ $refund->id }}
                            </h2>
                        </div>

                        <button
                            type="button"
                            class="btn btn-outline"
                            data-close-refund
                        >
                            إغلاق
                        </button>
                    </div>

                    <div class="refund-details-content">

                        
                        <section>
                            <h3>معلومات الحجز</h3>

                            <div class="refund-info-grid">
                                <p>
                                    <span class="muted">رقم الحجز</span>
                                    <strong>#{{ $booking?->id ?? '—' }}</strong>
                                </p>

                                <p>
                                    <span class="muted">الخدمة</span>
                                    <strong>
                                        {{ $booking?->service_name ?? $booking?->vendorService?->service?->name ?? '—' }}
                                    </strong>
                                </p>

                                <p>
                                    <span class="muted">العميل</span>
                                    <strong>
                                        {{ $customer?->first_name }}
                                        {{ $customer?->last_name }}
                                    </strong>
                                </p>

                                <p>
                                    <span class="muted">مقدم الخدمة</span>
                                    <strong>
                                        {{ $vendor?->first_name }}
                                        {{ $vendor?->last_name }}
                                    </strong>
                                </p>

                                <p>
                                    <span class="muted">مبلغ الحجز</span>
                                    <strong>
                                        {{ number_format($amount, 2) }} د.أ
                                    </strong>
                                </p>

                                <p>
                                    <span class="muted">تاريخ إكمال الخدمة</span>
                                    <strong>
                                        {{ $booking?->completed_at?->format('Y/m/d H:i') ?? '—' }}
                                    </strong>
                                </p>

                                <p>
                                    <span class="muted">تاريخ تقديم الاعتراض</span>
                                    <strong>
                                        {{ $refund->created_at?->format('Y/m/d H:i') ?? '—' }}
                                    </strong>
                                </p>

                                <p>
                                    <span class="muted">حالة الطلب</span>
                                    <strong>
                                        {{ $statusLabels[$refund->status] ?? $refund->status }}
                                    </strong>
                                </p>
                            </div>
                        </section>

                        <hr>

                        
                        <section>
                            <h3>سبب الاعتراض</h3>

                            <p>
                                {{ $refund->reason ?: 'لم يتم تقديم سبب.' }}
                            </p>
                        </section>

                        
                        <section>
                            <h3>الإثباتات المرفقة</h3>

                            @if ($refund->evidence_path)
                                <a
                                    href="{{ asset('storage/' . $refund->evidence_path) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-outline"
                                >
                                    عرض الإثبات المرفق
                                </a>
                            @else
                                <p class="muted">
                                    لا توجد إثباتات مرفقة بهذا الطلب.
                                </p>
                            @endif
                        </section>

                        <hr>

                        
                        <section>
                            <h3>التوزيع المالي عند اتخاذ القرار</h3>

                            <div class="refund-financial-grid">
                                <div class="panel">
                                    <h4>في حال قبول الاعتراض</h4>

                                    <p>
                                        استرداد العميل (70%):
                                        <strong>
                                            {{ number_format($customerRefund, 2) }} د.أ
                                        </strong>
                                    </p>

                                    <p>
                                        حصة مقدم الخدمة (15%):
                                        <strong>
                                            {{ number_format($vendorAcceptedShare, 2) }} د.أ
                                        </strong>
                                    </p>

                                    <p>
                                        حصة المنصة (15%):
                                        <strong>
                                            {{ number_format($platformShare, 2) }} د.أ
                                        </strong>
                                    </p>
                                </div>

                                <div class="panel">
                                    <h4>في حال رفض الاعتراض</h4>

                                    <p>
                                        حصة مقدم الخدمة (85%):
                                        <strong>
                                            {{ number_format($vendorNormalShare, 2) }} د.أ
                                        </strong>
                                    </p>

                                    <p>
                                        حصة المنصة (15%):
                                        <strong>
                                            {{ number_format($platformShare, 2) }} د.أ
                                        </strong>
                                    </p>
                                </div>
                            </div>

                            <p class="small muted">
                                يتم احتساب النسب من مبلغ الحجز الأصلي.
                                تبقى مستحقات مقدم الخدمة معلقة حتى اتخاذ
                                القرار بشأن الاعتراض.
                            </p>
                        </section>

                        
                        @if ($refund->status === 'pending')
                            <div class="moderation-actions">
                                <button
                                    type="button"
                                    class="btn btn-yellow"
                                    data-refund-confirm="{{ $refund->id }}"
                                    data-decision="accepted"
                                >
                                    قبول الاعتراض
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-danger"
                                    data-refund-confirm="{{ $refund->id }}"
                                    data-decision="rejected"
                                >
                                    رفض الاعتراض
                                </button>
                            </div>
                        @else
                            <div class="notice">
                                تمت مراجعة هذا الاعتراض واتخاذ القرار.
                            </div>

                            @if ($refund->reviewed_at)
                                <p class="small muted">
                                    تاريخ القرار:
                                    {{ $refund->reviewed_at->format('Y/m/d H:i') }}
                                </p>
                            @endif
                            @if ($refund->refund_amount !== null)
                                <p class="small muted">مبلغ الاسترداد: <bdi>{{ number_format($refund->refund_amount, 2) }}</bdi> د.أ</p>
                            @endif
                        @endif
                    </div>
                </dialog>

                
                @if ($refund->status === 'pending')
                    <dialog
                        id="refund-confirm-{{ $refund->id }}"
                        class="panel refund-dialog refund-confirm-dialog"
                        aria-labelledby="refund-confirm-title-{{ $refund->id }}"
                    >
                        <h2 id="refund-confirm-title-{{ $refund->id }}">
                            تأكيد القرار
                        </h2>

                        <p data-confirm-message>
                            هل أنت متأكد من اتخاذ هذا القرار؟
                        </p>

                        <p class="small muted">
                            رقم طلب الاسترداد: #{{ $refund->id }}
                        </p>

                        <div class="moderation-actions">
                            <button
                                type="button"
                                class="btn btn-outline"
                                data-close-refund
                            >
                                رجوع
                            </button>

                            <form method="POST"
                                  action="{{ route('admin.refunds.decide', $refund) }}"
                                  data-refund-decision-form>
                                @csrf
                                <input type="hidden" name="decision" value="" data-refund-decision-input>
                                <button type="submit" class="btn btn-yellow" data-confirm-final>
                                    تأكيد
                                </button>
                            </form>
                        </div>
                    </dialog>
                @endif
            @endforeach
        </div>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')

    

<script>
(function () {
    function initializeRefunds() {
        const page = document.querySelector('.refunds-page');
        if (!page || page.dataset.refundsInitialized === 'true') return;
        page.dataset.refundsInitialized = 'true';

        function closeDialogs() {
            page.querySelectorAll('dialog[open]').forEach(function (dialog) {
                dialog.close();
            });
        }

        page.addEventListener('click', function (event) {
            const target = event.target;
            if (!(target instanceof Element)) return;

            const detailsButton = target.closest('[data-refund-details]');
            if (detailsButton) {
                const dialog = document.getElementById('refund-details-' + detailsButton.dataset.refundDetails);
                if (!dialog) return;
                closeDialogs();
                dialog.showModal();
                return;
            }

            const closeButton = target.closest('[data-close-refund]');
            if (closeButton) {
                const dialog = closeButton.closest('dialog');
                if (dialog && dialog.open) dialog.close();
                return;
            }

            const decisionButton = target.closest('[data-refund-confirm]');
            if (!decisionButton) return;

            const id = decisionButton.dataset.refundConfirm;
            const decision = decisionButton.dataset.decision;
            if (!['accepted', 'rejected'].includes(decision)) return;

            const dialog = document.getElementById('refund-confirm-' + id);
            if (!dialog) return;
            const input = dialog.querySelector('[data-refund-decision-input]');
            const message = dialog.querySelector('[data-confirm-message]');
            if (!input || !message) return;

            input.value = decision;
            message.textContent = decision === 'accepted'
                ? 'هل أنت متأكد من قبول الاعتراض؟ سيتم اعتماد توزيع 70% للعميل، و15% لمقدم الخدمة، و15% للمنصة.'
                : 'هل أنت متأكد من رفض الاعتراض؟ سيتم اعتماد التوزيع الطبيعي: 85% لمقدم الخدمة و15% للمنصة.';

            closeDialogs();
            dialog.showModal();
        });

        page.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.matches('[data-refund-decision-form]')) return;

            const input = form.querySelector('[data-refund-decision-input]');
            if (!input || !['accepted', 'rejected'].includes(input.value)) {
                event.preventDefault();
                return;
            }
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = 'true';
            const button = form.querySelector('[data-confirm-final]');
            if (button) {
                button.disabled = true;
                button.textContent = 'جارٍ الحفظ...';
            }
        });

    }

    initializeRefunds();

     
    document.addEventListener('turbo:before-cache', function () {
        const page = document.querySelector('.refunds-page');
        if (!page) return;
        page.querySelectorAll('dialog[open]').forEach(function (dialog) { dialog.close(); });
        delete page.dataset.refundsInitialized;
        page.querySelectorAll('[data-refund-decision-form]').forEach(function (form) {
            delete form.dataset.submitting;
            const button = form.querySelector('[data-confirm-final]');
            if (button) {
                button.disabled = false;
                button.textContent = 'تأكيد';
            }
        });
    }, { once: true });
})();
</script>


@endsection
