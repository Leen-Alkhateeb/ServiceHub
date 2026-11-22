@extends('layouts.app')

@section('title', 'رسائل التواصل')
@section('page_id', 'admin-messages')

@push('styles')
    <x-public-stylesheet file="admin-management.css" />
@endpush

@section('content')
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <main class="dashboard-content admin-manage">
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>رسائل التواصل</h1>
                    <p class="page-intro">رسائل العملاء والمزوّدين الواردة لإدارة المنصة. الرد يتم خارج المنصة.</p>
                </div>
            </div>

            @include('pages.admin.partials.flash-messages')

            <div class="stats">
                <div class="stat">
                    <strong>{{ $totalCount }}</strong>
                    <span>إجمالي الرسائل</span>
                </div>
                <div class="stat">
                    <strong>{{ $pendingCount }}</strong>
                    <span>بانتظار المراجعة</span>
                </div>
                <div class="stat">
                    <strong>{{ $reviewedCount }}</strong>
                    <span>تمت مراجعتها</span>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.messages.index') }}" class="panel hub-filters">
                <div class="field">
                    <label for="message-search">بحث</label>
                    <input id="message-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="الموضوع، اسم المرسل أو إيميله">
                </div>
                <div class="field">
                    <label for="message-status">الحالة</label>
                    <select id="message-status" name="status">
                        <option value="">جميع الحالات</option>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-yellow" type="submit">تصفية</button>
                    <a class="btn btn-outline" href="{{ route('admin.messages.index') }}">مسح</a>
                </div>
            </form>

            <div class="panel">
                <div class="panel-head">
                    <h3>قائمة الرسائل</h3>
                    <span class="results-count">{{ $messages->total() }} رسالة</span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>الرقم</th>
                                <th>الموضوع</th>
                                <th>المرسل</th>
                                <th>تاريخ الإرسال</th>
                                <th>الحالة</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($messages as $message)
                                <tr>
                                    <td>#{{ $message->id }}</td>
                                    <td>
                                        <span class="cell-title">{{ $message->subject }}</span>
                                        <span class="cell-sub">{{ \Illuminate\Support\Str::limit($message->message, 70) }}</span>
                                    </td>
                                    <td>
                                        <span class="cell-title">{{ trim($message->sender?->first_name . ' ' . $message->sender?->last_name) ?: '—' }}</span>
                                        <span class="cell-sub">{{ $roles[$message->sender?->role] ?? '' }}</span>
                                    </td>
                                    <td class="nowrap">{{ $message->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>
                                        <span class="pill {{ $message->status === 'pending' ? 'yellow' : 'green' }}">{{ $statuses[$message->status] ?? $message->status }}</span>
                                    </td>
                                    <td>
                                        <a class="btn btn-outline btn-sm" href="{{ route('admin.messages.show', $message) }}">عرض</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <strong>لا توجد رسائل مطابقة</strong>
                                            جرّب تغيير البحث أو الفلتر، أو اضغط "مسح".
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $messages->onEachSide(1)->links('components.pager') }}
        </main>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
