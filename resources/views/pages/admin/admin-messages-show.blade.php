@extends('layouts.app')

@section('title', 'تفاصيل الرسالة | ServiceHub')
@section('page_id', 'admin-messages')

@push('styles')
    <link rel="stylesheet" href="/assets/admin-management.css">
@endpush

@section('content')
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <main class="dashboard-content admin-manage">
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة / رسائل التواصل</span>
                    <h1>الرسالة #{{ $message->id }}</h1>
                    <p class="page-intro">{{ $message->subject }}</p>
                </div>
                <a class="btn btn-outline" href="{{ route('admin.messages.index') }}">رجوع للرسائل</a>
            </div>

            @include('pages.admin.partials.flash-messages')

            <div class="details-grid">
                <section class="panel">
                    <h3>نص الرسالة</h3>
                    <div class="message-body">{{ $message->message }}</div>
                </section>

                <section class="panel">
                    <h3>معلومات الرسالة</h3>
                    <dl class="details-list">
                        <dt>المرسل</dt>
                        <dd>{{ trim($message->sender?->first_name . ' ' . $message->sender?->last_name) ?: '—' }}</dd>
                        <dt>نوع الحساب</dt>
                        <dd>{{ $senderRole ?? '—' }}</dd>
                        <dt>الإيميل</dt>
                        <dd>
                            @if ($message->sender?->email)
                                <a href="mailto:{{ $message->sender->email }}">{{ $message->sender->email }}</a>
                            @else
                                —
                            @endif
                        </dd>
                        <dt>الهاتف</dt>
                        <dd>{{ $message->sender?->phone ?? '—' }}</dd>
                        <dt>تاريخ الإرسال</dt>
                        <dd>{{ $message->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                        <dt>الحالة</dt>
                        <dd><span class="pill {{ $message->status === 'pending' ? 'yellow' : 'green' }}">{{ $statusLabel }}</span></dd>
                        @if ($message->reviewed_at)
                            <dt>تاريخ المراجعة</dt>
                            <dd>{{ \Illuminate\Support\Carbon::parse($message->reviewed_at)->format('Y-m-d H:i') }}</dd>
                        @endif
                    </dl>
                </section>
            </div>

            @if ($message->status === 'pending')
                <div class="panel">
                    <div class="panel-head">
                        <div>
                            <h3>مراجعة الرسالة</h3>
                            <p class="page-intro">بعد التواصل مع المرسل عبر الإيميل أو الهاتف، علّم الرسالة كمراجَعة.</p>
                        </div>
                        <form method="POST" action="{{ route('admin.messages.update', $message) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-yellow">تعليم كمراجَعة</button>
                        </form>
                    </div>
                </div>
            @endif
        </main>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
