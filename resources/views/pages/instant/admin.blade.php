@extends('layouts.app')
@section('title', 'إدارة الخدمات الفورية')
@section('page_id', 'admin-instant')
@include('pages.instant.styles')
@section('content')
@php
    $tabs = [
        'activity' => 'الطلبات والحجوزات',
        'reviews' => 'المراجعات والمستحقات',
        'finance' => 'المالية',
    ];
    $section = request()->string('section')->toString();
    $section = isset($tabs[$section]) ? $section : 'activity';
    $reviewCount = $cases->total() + $pending->total();
@endphp
@include('components.navbar')
<div class="container layout">
    @include('components.sidebars.admin')
    <main class="dashboard-content stack instant-page instant-admin-page">
        <header class="instant-admin-title">
            <h1>إدارة الخدمات الفورية</h1>
            <p class="muted">تابع طلبات «صلّحها الآن» والحجوزات والمستحقات.</p>
        </header>
        @include('pages.instant.feedback')

        <div class="instant-stats instant-admin-stats">
            <div class="panel"><span class="muted">الرصيد التجريبي المتاح</span><strong>{{ number_format($availableFils / 1000, 3) }} د.أ</strong></div>
            <div class="panel"><span class="muted">العمولات التجريبية</span><strong>{{ number_format($simulatedCommission, 3) }} د.أ</strong></div>
            <div class="panel"><span class="muted">الديون القائمة</span><strong>{{ number_format($debts, 3) }} د.أ</strong></div>
        </div>

        <nav class="instant-admin-tabs" aria-label="أقسام إدارة الخدمات الفورية">
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.instant.index', $key === 'activity' ? [] : ['section' => $key]) }}" @if($section === $key) aria-current="page" @endif>
                    {{ $label }}
                    @if($key === 'reviews' && $reviewCount > 0)<span>{{ $reviewCount }}</span>@endif
                </a>
            @endforeach
        </nav>

        @if($section === 'activity')
            <div class="instant-admin-grid">
                <section class="panel instant-admin-panel">
                    <h2>الطلبات الفورية</h2>
                    @forelse($requests as $item)
                        <div class="instant-admin-row">
                            <strong>طلب #{{ $item->id }}</strong>
                            <span class="instant-admin-status">{{ ['searching' => 'قيد البحث', 'assigned' => 'تم التعيين', 'expired' => 'انتهت المهلة', 'cancelled' => 'ملغى'][$item->status] }}</span>
                        </div>
                    @empty<p class="instant-admin-empty muted">ما في طلبات فورية.</p>@endforelse
                    {{ $requests->appends(['section' => 'activity'])->links('components.pager') }}
                </section>
                <section class="panel instant-admin-panel">
                    <h2>الحجوزات الفورية</h2>
                    @forelse($bookings as $item)
                        <div class="instant-admin-row">
                            <a href="{{ route('instant.booking', $item) }}">#{{ $item->id }} · {{ $item->service_name }}</a>
                        </div>
                    @empty<p class="instant-admin-empty muted">ما في حجوزات فورية.</p>@endforelse
                    {{ $bookings->appends(['section' => 'activity'])->links('components.pager') }}
                </section>
            </div>
        @elseif($section === 'reviews')
            <div class="instant-admin-grid">
                <section class="panel instant-admin-panel">
                    <h2>المراجعات والتعويضات</h2>
                    @forelse($cases as $item)
                        <div class="instant-admin-row">
                            <div class="instant-admin-item">
                                <a href="{{ route('instant.booking', $item->booking) }}">حجز #{{ $item->booking_id }} · {{ $item->booking->service_name }}</a>
                                <small class="muted">حالة #{{ $item->id }}</small>
                            </div>
                            <span class="instant-admin-status">{{ $item->status === 'pending' ? 'قيد المراجعة' : 'بانتظار السداد أو التمويل' }}</span>
                        </div>
                    @empty<p class="instant-admin-empty muted">ما في مراجعات أو تعويضات معلّقة.</p>@endforelse
                    {{ $cases->appends(['section' => 'reviews'])->links('components.pager') }}
                </section>
                <section class="panel instant-admin-panel">
                    <h2>مستحقات الفنيين</h2>
                    @forelse($pending as $item)
                        <form class="instant-admin-row" method="POST" action="{{ route('admin.instant.fund', $item) }}">
                            @csrf
                            <a href="{{ route('instant.booking', $item) }}">حجز #{{ $item->id }} · {{ $item->service_name }}</a>
                            <button class="btn btn-outline btn-sm">تمويل المستحق تجريبيًا</button>
                        </form>
                    @empty<p class="instant-admin-empty muted">ما في مستحقات بانتظار المراجعة.</p>@endforelse
                    {{ $pending->appends(['section' => 'reviews'])->links('components.pager') }}
                </section>
            </div>
        @else
            <section class="panel instant-admin-panel">
                <h2>تصحيح العمولات التجريبية</h2>
                @forelse($commissions as $entry)
                    <details class="instant-admin-commission">
                        <summary>عمولة المنصة #{{ $entry->id }} · {{ $entry->amount }} د.أ</summary>
                        @foreach($entry->corrections as $correction)
                            <p>#{{ $correction->id }} · خصم {{ $correction->amount }} د.أ · {{ $correction->description }} · {{ $correction->created_at }}</p>
                        @endforeach
                        <form method="POST" action="{{ route('admin.instant.correct-commission', $entry->id) }}" class="stack">
                            @csrf
                            <input type="hidden" name="request_id" value="{{ \Illuminate\Support\Str::uuid() }}">
                            <div class="field"><label for="correction-amount-{{ $entry->id }}">المبلغ (د.أ)</label><input id="correction-amount-{{ $entry->id }}" name="amount" type="number" min="0.001" max="{{ $entry->amount }}" step="0.001" required></div>
                            <div class="field"><label for="correction-reason-{{ $entry->id }}">السبب</label><textarea id="correction-reason-{{ $entry->id }}" name="reason" maxlength="2000" required></textarea></div>
                            <label><input type="checkbox" name="confirm" value="1" required> أوافق على خصم المبلغ من الرصيد التجريبي وتسجيل قيد مستقل.</label>
                            <div><button class="btn btn-outline">تسجيل التصحيح</button></div>
                        </form>
                    </details>
                @empty<p class="instant-admin-empty muted">ما في عمولات قابلة للتصحيح.</p>@endforelse
                {{ $commissions->appends(['section' => 'finance'])->links('components.pager') }}
            </section>
        @endif
    </main>
</div>
@endsection
