
@extends('layouts.app')

@section('title', 'طلبات إضافة الخدمات | ServiceHub')
@section('page_id', 'admin-service-requests')

@push('styles')
<meta name="turbo-visit-control" content="reload">

<style>
    #admin-service-requests {
        padding-bottom: 60px;
    }

    #admin-service-requests .requests-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
    }

    #admin-service-requests .requests-heading h1 {
        margin: 0 0 8px;
        font-size: 29px;
    }

    #admin-service-requests .requests-filters {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 12px;
        margin-bottom: 24px;
    }

    #admin-service-requests .filter-field {
        flex: 1 1 180px;
    }

    #admin-service-requests .filter-search {
        flex: 2 1 260px;
    }

    #admin-service-requests .filter-field label {
        display: block;
        margin-bottom: 7px;
        font-weight: 700;
    }

    #admin-service-requests .filter-field input,
    #admin-service-requests .filter-field select {
        width: 100%;
        min-height: 44px;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #fff;
    }

    #admin-service-requests .filter-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    #admin-service-requests .requests-list {
        display: grid;
        gap: 16px;
    }

    #admin-service-requests .request-card {
        padding: 22px;
    }

    #admin-service-requests .request-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 16px;
    }

    #admin-service-requests .request-top h2 {
        font-size: 20px;
        margin: 0 0 7px;
    }

    #admin-service-requests .request-description {
        margin-bottom: 14px;
        overflow-wrap: anywhere;
    }

    #admin-service-requests .request-details {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 20px;
        margin-bottom: 18px;
        font-weight: 700;
    }

    #admin-service-requests .request-status {
        display: inline-flex;
        align-items: center;
        border-radius: 30px;
        padding: 6px 13px;
        font-size: 13px;
        font-weight: 800;
    }

    #admin-service-requests .status-pending {
        background: #fff3bd;
        color: #705600;
    }

    #admin-service-requests .status-active {
        background: #dcfce7;
        color: #166534;
    }

    #admin-service-requests .status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    #admin-service-requests .request-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        padding-top: 15px;
        border-top: 1px solid #e5e7eb;
    }

    #admin-service-requests .request-approve {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }

    #admin-service-requests .request-reject {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    #admin-service-requests .request-approve:hover {
        background: #bbf7d0;
    }

    #admin-service-requests .request-reject:hover {
        background: #fecaca;
    }

    #admin-service-requests .request-reason {
        padding: 12px;
        border-radius: 9px;
        background: #fef2f2;
        color: #991b1b;
    }

    #admin-service-requests .requests-empty {
        text-align: center;
        padding: 45px 20px;
    }

    #admin-service-requests .requests-pagination {
        margin-top: 24px;
    }

    .request-modal-backdrop[hidden] {
        display: none !important;
    }

    .request-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
        background: rgba(0, 0, 0, .55);
    }

    .request-modal {
        width: min(100%, 480px);
        max-height: 90vh;
        overflow-y: auto;
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        direction: rtl;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
    }

    .request-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
    }

    .request-modal-header h2 {
        font-size: 21px;
        margin: 0;
    }

    .request-modal-close {
        width: 36px;
        height: 36px;
        border: 0;
        border-radius: 8px;
        background: #f3f4f6;
        font-size: 24px;
    }

    .request-modal label {
        display: block;
        margin: 18px 0 8px;
        font-weight: 700;
    }

    .request-modal textarea {
        width: 100%;
        min-height: 110px;
        resize: vertical;
        padding: 12px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
    }

    .request-modal-actions {
        display: flex;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
    }

    @media (max-width: 650px) {
        #admin-service-requests .requests-filters {
            flex-direction: column;
            align-items: stretch;
        }

        #admin-service-requests .filter-field,
        #admin-service-requests .filter-search {
            flex: auto;
        }

        #admin-service-requests .filter-actions .btn {
            flex: 1;
        }

        #admin-service-requests .request-card {
            padding: 17px;
        }

        .request-modal {
            padding: 18px;
        }
    }
</style>
@endpush

@section('content')

@include('components.navbar')

<div class="container layout">

    @include('components.sidebars.admin')

    <main id="admin-service-requests">

        <div class="requests-heading">
            <div>
                <span class="small muted">لوحة الإدارة</span>
                <h1>طلبات إضافة الخدمات</h1>
                <p class="muted">
                    مراجعة خدمات مقدمي الخدمات والموافقة عليها أو رفضها.
                </p>
            </div>
        </div>

        @if (session('success'))
            <div class="admin-alert admin-alert-success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="admin-alert admin-alert-error" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="admin-alert admin-alert-error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- البحث والفلترة -->
        <section class="panel">

            <form
                method="GET"
                action="{{ route('admin.service-requests.index') }}"
                class="requests-filters"
                data-turbo="false"
            >

                <div class="filter-field filter-search">
                    <label for="request-search">البحث</label>

                    <input
                        type="search"
                        id="request-search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="اسم الخدمة أو مقدم الخدمة"
                    >
                </div>

                <div class="filter-field">
                    <label for="request-status">حالة الطلب</label>

                    <select id="request-status" name="status">
                        <option value="">جميع الحالات</option>

                        <option value="pending"
                            @selected(request('status') === 'pending')>
                            قيد المراجعة
                        </option>

                        <option value="active"
                            @selected(request('status') === 'active')>
                            مقبول
                        </option>

                        <option value="rejected"
                            @selected(request('status') === 'rejected')>
                            مرفوض
                        </option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-yellow">
                        بحث
                    </button>

                    <a
                        href="{{ route('admin.service-requests.index') }}"
                        class="btn btn-outline"
                    >
                        إعادة تعيين
                    </a>
                </div>

            </form>

            <p class="muted">
                عدد الطلبات: {{ $requests->total() }}
            </p>

        </section>

        <!-- قائمة الطلبات -->
        <div class="requests-list" style="margin-top: 20px;">

            @forelse ($requests as $offer)

                <article class="panel request-card">

                    <div class="request-top">

                        <div>
                            <h2>
                                {{ $offer->service->name ?? 'خدمة غير معروفة' }}
                            </h2>

                            <p class="muted">
                                {{ $offer->service->category->name ?? 'بدون قسم' }}
                                ·
                                {{ $offer->vendor->first_name ?? '' }}
                                {{ $offer->vendor->last_name ?? '' }}
                            </p>
                        </div>

                        <span class="request-status
                            {{ match($offer->status) {
                                'pending' => 'status-pending',
                                'active' => 'status-active',
                                'rejected' => 'status-rejected',
                                default => ''
                            } }}
                        ">
                            {{ match($offer->status) {
                                'pending' => 'قيد المراجعة',
                                'active' => 'مقبول',
                                'rejected' => 'مرفوض',
                                default => $offer->status
                            } }}
                        </span>

                    </div>

                    <p class="request-description">
                        {{ $offer->description }}
                    </p>

                    <div class="request-details">

                        <span>
                            السعر:
                            {{ number_format($offer->price, 2) }} د.أ
                        </span>

                        @if ($offer->duration_minutes)
                            <span>
                                المدة:
                                {{ $offer->duration_minutes }} دقيقة
                            </span>
                        @endif

                    </div>

                    @if ($offer->status === 'pending')

                        <div class="request-actions">

                            <button
                                type="button"
                                class="btn request-approve"
                                data-request-action="active"
                                data-id="{{ $offer->id }}"
                                data-name="{{ $offer->service->name ?? '' }}"
                            >
                                موافقة
                            </button>

                            <button
                                type="button"
                                class="btn request-reject"
                                data-request-action="rejected"
                                data-id="{{ $offer->id }}"
                                data-name="{{ $offer->service->name ?? '' }}"
                            >
                                رفض
                            </button>

                        </div>

                    @elseif ($offer->rejection_reason)

                        <div class="request-reason">
                            <strong>سبب الرفض:</strong>
                            {{ $offer->rejection_reason }}
                        </div>

                    @endif

                </article>

            @empty

                <div class="panel requests-empty">
                    <h3>لا توجد طلبات</h3>
                    <p class="muted">
                        لا توجد طلبات مطابقة للبحث أو الفلترة.
                    </p>
                </div>

            @endforelse

        </div>

        <!-- Pagination -->
        @if ($requests->hasPages())

            <div class="requests-pagination" data-turbo="false">
                {{ $requests->onEachSide(1)->links('components.pager') }}
            </div>

        @endif

    </main>

</div>

<!-- نافذة الموافقة والرفض -->
<div
    class="request-modal-backdrop"
    id="request-decision-modal"
    hidden
>
    <section
        class="request-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="request-modal-title"
    >

        <div class="request-modal-header">

            <h2 id="request-modal-title">
                مراجعة الطلب
            </h2>

            <button
                type="button"
                class="request-modal-close"
                data-request-close
                aria-label="إغلاق"
            >
                &times;
            </button>

        </div>

        <p id="request-modal-message"></p>

        <form
            id="request-decision-form"
            method="POST"
        >
            @csrf

            <input
                type="hidden"
                name="decision"
                id="request-decision-value"
            >

            <div id="request-reason-field" hidden>

                <label for="request-rejection-reason">
                    سبب الرفض
                </label>

                <textarea
                    name="rejection_reason"
                    id="request-rejection-reason"
                    maxlength="500"
                    placeholder="اكتب سبب رفض الخدمة"
                ></textarea>

            </div>

            <div class="request-modal-actions">

                <button
                    type="button"
                    class="btn btn-outline"
                    data-request-close
                >
                    إلغاء
                </button>

                <button
                    type="submit"
                    class="btn"
                    id="request-decision-confirm"
                >
                    تأكيد
                </button>

            </div>

        </form>

    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const modal = document.getElementById('request-decision-modal');
    const form = document.getElementById('request-decision-form');
    const title = document.getElementById('request-modal-title');
    const message = document.getElementById('request-modal-message');
    const decisionInput = document.getElementById('request-decision-value');
    const reasonField = document.getElementById('request-reason-field');
    const reasonInput = document.getElementById('request-rejection-reason');
    const confirmButton = document.getElementById('request-decision-confirm');

    let lastTrigger = null;

    const routeTemplate = @json(
        route('admin.service-requests.decide', ['offer' => '__OFFER_ID__'])
    );

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';

        if (lastTrigger) {
            lastTrigger.focus();
        }
    }

    document.querySelectorAll('[data-request-action]').forEach(button => {

        button.addEventListener('click', () => {

            lastTrigger = button;

            const decision = button.dataset.requestAction;
            const offerId = button.dataset.id;
            const serviceName = button.dataset.name || '';

            const approving = decision === 'active';

            form.action = routeTemplate.replace(
                '__OFFER_ID__',
                encodeURIComponent(offerId)
            );

            decisionInput.value = decision;

            title.textContent = approving
                ? 'تأكيد الموافقة'
                : 'رفض طلب الخدمة';

            message.textContent = approving
                ? `هل أنت متأكد من الموافقة على خدمة «${serviceName}»؟`
                : `يرجى كتابة سبب رفض خدمة «${serviceName}».`;

            reasonField.hidden = approving;

            reasonInput.required = !approving;
            reasonInput.value = '';

            confirmButton.textContent = approving
                ? 'تأكيد الموافقة'
                : 'تأكيد الرفض';

            confirmButton.className = approving
                ? 'btn request-approve'
                : 'btn request-reject';

            modal.hidden = false;
            document.body.style.overflow = 'hidden';

            if (approving) {
                confirmButton.focus();
            } else {
                reasonInput.focus();
            }

        });

    });

    document.querySelectorAll('[data-request-close]').forEach(button => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', event => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

});
</script>

@include('components.footer')

@endsection
