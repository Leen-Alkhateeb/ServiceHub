@extends('layouts.app')
@section('title', 'التقييمات | ServiceHub')
@section('page_id', 'admin-reviews')

@section('content')
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <div>
            
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>التقييمات</h1>
                </div>
            </div>

            <!-- رسائل النجاح -->
            @if(session('success'))
                <div class="alert alert-success" style="background: #e6f9e6; color: #1e7e34; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    {{ session('success') }}
                </div>
            @endif

            <!-- جدول التقييمات مباشرة بدون فلتر -->
            <div class="panel" style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; margin-bottom: 6px;">التقييمات</h3>
                <p class="muted" style="margin-top: 0; margin-bottom: 20px;">مراجعة التقييمات وحذف المخالف منها أو تغيير حالة ظهوره.</p>

                <div class="table-wrap">
                    <table style="width: 100%; border-collapse: collapse; text-align: right;">
                        <thead>
                            <tr style="border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 0.9rem;">
                                <th style="padding: 14px 10px;">الرقم</th>
                                <th style="padding: 14px 10px;">العميل</th>
                                <th style="padding: 14px 10px;">الفني</th>
                                <th style="padding: 14px 10px;">التقييم</th>
                                <th style="padding: 14px 10px;">التعليق</th>
                                <th style="padding: 14px 10px;">الحالة</th>
                                <th style="padding: 14px 10px;">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reviews as $review)
                                <tr style="border-bottom: 1px solid #f8fafc; font-size: 0.92rem;">
                                    <td style="padding: 16px 10px; color: #64748b;">
                                        {{ $loop->iteration + ($reviews->currentPage() - 1) * $reviews->perPage() }}
                                    </td>
                                    <td style="padding: 16px 10px; font-weight: 500;">
                                        {{ $review->reviewer->first_name  ?? 'مستخدم محذوف' }}
                                        {{ $review->reviewer->last_name  ?? 'مستخدم محذوف' }}
                                    </td>
                                    <td style="padding: 16px 10px; color: #334155;">
                                        {{ $review->reviewedUser->first_name  ?? 'فني محذوف' }}
                                        {{ $review->reviewedUser->last_name  ?? 'فني محذوف' }}
                                    </td>
                                    <td style="padding: 16px 10px;">
                                        <span style="color: #f59e0b; font-weight: bold; letter-spacing: 1px;">
                                            {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                                        </span>
                                    </td>
                                    <td style="padding: 16px 10px; color: #475569; max-width: 260px;">
                                        <div>{{ $review->comment ?? 'لا يوجد تعليق' }}</div>
                                        @if($review->image)
                                            <small><a href="{{ asset('storage/' . $review->image) }}" target="_blank" style="color: #0284c7; text-decoration: underline;">عرض الصورة المرفقة</a></small>
                                        @endif
                                    </td>
                                    <td style="padding: 16px 10px;">
                                        @if($review->is_active)
                                            <span style="background: #dcfce7; color: #15803d; padding: 4px 16px; border-radius: 9999px; font-size: 0.82rem; font-weight: bold; display: inline-block;">
                                                فعال
                                            </span>
                                        @else
                                            <span style="background: #fee2e2; color: #b91c1c; padding: 4px 16px; border-radius: 9999px; font-size: 0.82rem; font-weight: bold; display: inline-block;">
                                                معطل
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 16px 10px;">
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                                 
<form action="{{ route('admin.reviews.update', $review->id) }}" method="POST" style="margin: 0;">
    @csrf
    @method('PUT')
    <button type="submit" 
        title="{{ $review->is_active ? 'إخفاء التقييم' : 'تفعيل وإظهار التقييم' }}"
        style="background: #f8fafc; border: 1px solid #cbd5e1; width: 34px; height: 34px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: {{ $review->is_active ? '#64748b' : '#16a34a' }};">
        
        @if($review->is_active)
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
            </svg>
        @else
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        @endif

    </button>
</form>

                                            
                                            <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" 
                                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا التقييم نهائياً؟');" style="margin: 0;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="حذف"
                                                    style="background: #fee2e2; color: #dc2626; border: none; width: 34px; height: 34px; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 35px; color: #94a3b8;">
                                        لا توجد تقييمات مسجلة في النظام حتى الآن.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

               @if ($reviews->hasPages())
    <div style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 25px;">
        
        
        @if ($reviews->onFirstPage())
            <span style="padding: 6px 14px; border: 1px solid #e2e8f0; border-radius: 6px; color: #cbd5e1; cursor: not-allowed; font-size: 0.85rem;">
                &laquo; السابق
            </span>
        @else
            <a href="{{ $reviews->previousPageUrl() }}" style="padding: 6px 14px; border: 1px solid #cbd5e1; border-radius: 6px; color: #1e293b; text-decoration: none; font-size: 0.85rem; background: #fff;">
                &laquo; السابق
            </a>
        @endif

        <span style="font-size: 0.85rem; color: #64748b; font-weight: bold;">
            صفحة {{ $reviews->currentPage() }} من {{ $reviews->lastPage() }}
        </span>

        
        @if ($reviews->hasMorePages())
            <a href="{{ $reviews->nextPageUrl() }}" style="padding: 6px 14px; border: 1px solid #cbd5e1; border-radius: 6px; color: #1e293b; text-decoration: none; font-size: 0.85rem; background: #fff;">
                التالي &raquo;
            </a>
        @else
            <span style="padding: 6px 14px; border: 1px solid #e2e8f0; border-radius: 6px; color: #cbd5e1; cursor: not-allowed; font-size: 0.85rem;">
                التالي &raquo;
            </span>
        @endif

    </div>
@endif
            </div>
        </div>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection