@extends('layouts.app')
@section('title', 'صلّحها الآن')
@section('content')
@include('components.navbar')
<main class="container page-head stack">
    <h1>صلّحها الآن</h1>
    @if(auth()->user()->role === 'vendor')<a href="{{ route('instant.vendor') }}">الطلبات الفورية للفني</a>@endif
    @if(auth()->user()->role === 'admin')<a href="{{ route('admin.instant.index') }}">إدارة الخدمات الفورية</a>@endif
    @include('pages.instant.feedback')
    @if($customerDebts->isNotEmpty())
        <section class="panel">
            <h2>المبالغ المستحقة</h2>
            @foreach($customerDebts as $debt)
                <p><a href="{{ route('instant.booking', $debt->booking_id) }}">{{ $debt->service_name }} · {{ $debt->amount }} د.أ</a> · {{ $debt->reason ?? $debt->description ?? 'دفعة حجز مستحقة' }}</p>
                @if(str_contains((string) $debt->idempotency_key, ':debt:overpayment-'))
                    <form method="POST" action="{{ route('instant.debts.repay', $debt->id) }}">
                        @csrf<button class="btn btn-outline">سداد الدين كاملًا تجريبيًا</button>
                    </form>
                @endif
            @endforeach
            {{ $customerDebts->links('components.pager') }}
        </section>
    @endif
    <section class="panel">
        <h2>اطلب خدمة فورية</h2>
        @if($services->isEmpty())
            <p>ما في خدمات فورية مفعّلة حاليًا.</p>
        @else
            <form method="POST" action="{{ route('instant.create') }}" enctype="multipart/form-data" data-instant-location data-instant-ai="{{ route('instant.suggest') }}" class="stack">
                @csrf
                <div class="field"><label for="now-service">الخدمة</label><select id="now-service" name="service_id" required>
                    <option value="">اختار الخدمة</option>
                    @foreach($services->groupBy('category.name') as $category => $group)
                        <optgroup label="{{ $category }}">@foreach($group as $service)<option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>{{ $service->name }}</option>@endforeach</optgroup>
                    @endforeach
                </select></div>
                <div class="field"><label for="now-description">وصف المشكلة</label><textarea id="now-description" name="description" rows="3" maxlength="2000" required>{{ old('description') }}</textarea></div>
                <div><button class="btn btn-outline" type="button" data-instant-suggest>اقترح خدمة من الوصف</button><p data-instant-suggestion role="status"></p></div>
                <div class="field"><label for="now-address">العنوان</label><input id="now-address" name="address" maxlength="255" value="{{ old('address') }}" required><small>العنوان يظهر للفني بعد قبول عرضه.</small></div>
                <div class="field"><label for="now-images">صور المشكلة (اختيارية)</label><x-file-picker><input id="now-images" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp"></x-file-picker><small>حتى 5 صور، 5 ميغابايت للصورة.</small></div>
                @include('pages.instant.location')
                <div><button class="btn btn-yellow" type="submit">ابحث عن فني</button></div>
            </form>
        @endif
    </section>
    <section class="panel">
        <h2>طلباتي الفورية</h2>
        @forelse($requests as $instantRequest)
            <p><a href="{{ route('instant.search', $instantRequest) }}">طلب #{{ $instantRequest->id }} · {{ $instantRequest->created_at->format('Y-m-d H:i') }}</a></p>
        @empty<p class="muted">لسه ما عندك طلبات فورية.</p>@endforelse
        {{ $requests->links('components.pager') }}
    </section>
    @include('pages.instant.history')
</main>
@include('components.footer')
<script type="module" src="{{ asset('assets/instant.js') }}?v={{ filemtime(public_path('assets/instant.js')) }}"></script>
@endsection
