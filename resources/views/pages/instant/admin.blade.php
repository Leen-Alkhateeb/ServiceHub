@extends('layouts.app')
@section('title', 'إدارة الطلبات الفورية')
@section('page_id', 'admin-instant')
@section('content')
@include('components.navbar')
<div class="container layout">
    @include('components.sidebars.admin')
    <main class="dashboard-content stack">
        <div class="dash-title"><h1>إدارة صلّحها الآن</h1></div>
        @include('pages.instant.feedback')
        <section class="panel">
            <h2>المستحقات بانتظار المراجعة</h2>
            <p>عمولات مسجّلة من الدفع التجريبي: {{ number_format($simulatedCommission, 3) }} د.أ · ديون قائمة: {{ number_format($debts, 3) }} د.أ</p>
            <p>الرصيد التجريبي المتاح {{ number_format($availableFils / 1000, 3) }} د.أ</p>
            @forelse($pending as $item)
                <form class="booking-tabs" method="POST" action="{{ route('admin.instant.fund', $item) }}">
                    @csrf
                    <a href="{{ route('instant.booking', $item) }}">حجز #{{ $item->id }} · {{ $item->service_name }}</a>
                    <button class="btn btn-outline btn-sm">تمويل مستحق الفني تجريبيًا</button>
                </form>
            @empty<p>ما في مستحقات معلقة.</p>@endforelse
            {{ $pending->links('components.pager') }}
        </section>
        <section class="panel">
            <h2>المراجعات والتعويضات المعلّقة</h2>
            @forelse($cases as $item)
                <p><a href="{{ route('instant.booking', $item->booking) }}">حجز #{{ $item->booking_id }} · {{ $item->booking->service_name }}</a> · حالة #{{ $item->id }} · {{ $item->status === 'pending' ? 'قيد المراجعة' : 'تعويض ينتظر السداد أو التمويل' }}</p>
            @empty<p>ما في مراجعات أو تعويضات معلّقة.</p>@endforelse
            {{ $cases->links('components.pager') }}
        </section>
        <section class="panel stack">
            <h2>أهلية الخدمات</h2>
            @forelse($services as $service)
                <form method="POST" action="{{ route('admin.instant.eligibility', $service) }}" class="booking-tabs">
                    @csrf<input type="hidden" name="supports_instant" value="{{ $service->supports_instant ? 0 : 1 }}">
                    <span>{{ $service->name }}</span><button class="btn btn-outline btn-sm">{{ $service->supports_instant ? 'إيقاف الفوري' : 'تفعيل الفوري' }}</button>
                </form>
            @empty<p>ما في خدمات معتمدة.</p>@endforelse
            {{ $services->links('components.pager') }}
        </section>
        <section class="panel"><h2>الطلبات الفورية</h2>
            @forelse($requests as $item)<p>#{{ $item->id }} · {{ ['searching' => 'قيد البحث', 'assigned' => 'تم التعيين', 'expired' => 'انتهت المهلة', 'cancelled' => 'ملغى'][$item->status] }}</p>@empty<p>ما في طلبات.</p>@endforelse
            {{ $requests->links('components.pager') }}
        </section>
        <section class="panel stack">
            <h2>تصحيح العمولات التجريبية</h2>
            @forelse($commissions as $entry)
                <details>
                    <summary>عمولة المنصة #{{ $entry->id }} · {{ $entry->amount }} د.أ</summary>
                    @foreach($entry->corrections as $correction)
                        <p>#{{ $correction->id }} · خصم {{ $correction->amount }} د.أ · {{ $correction->description }} · {{ $correction->created_at }}</p>
                    @endforeach
                    <form method="POST" action="{{ route('admin.instant.correct-commission', $entry->id) }}" class="stack">
                        @csrf<input type="hidden" name="request_id" value="{{ \Illuminate\Support\Str::uuid() }}">
                        <div class="field"><label for="correction-amount-{{ $entry->id }}">المبلغ (د.أ)</label><input id="correction-amount-{{ $entry->id }}" name="amount" type="number" min="0.001" max="{{ $entry->amount }}" step="0.001" required></div>
                        <div class="field"><label for="correction-reason-{{ $entry->id }}">السبب</label><textarea id="correction-reason-{{ $entry->id }}" name="reason" maxlength="2000" required></textarea></div>
                        <label><input type="checkbox" name="confirm" value="1" required> أوافق على خصم المبلغ من الرصيد التجريبي وتسجيل قيد مستقل.</label>
                        <div><button class="btn btn-outline">تسجيل التصحيح</button></div>
                    </form>
                </details>
            @empty<p>ما في عمولات قابلة للتصحيح.</p>@endforelse
            {{ $commissions->links('components.pager') }}
        </section>
        @include('pages.instant.history')
    </main>
</div>
@endsection
