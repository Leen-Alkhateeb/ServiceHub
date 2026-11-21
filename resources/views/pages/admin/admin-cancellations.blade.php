@extends('layouts.app')
@section('title', 'الإلغاءات | ServiceHub')
@section('page_id', 'admin-cancellations')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <div>
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>الإلغاءات</h1>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success" style="background: #e6f9e6; color: #1e7e34; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    {{ session('success') }}
                </div>
            @endif

            <div class="panel" style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; margin-bottom: 6px;">سجل الحجوزات الملغاة</h3>
                <p class="muted" style="margin-top: 0; margin-bottom: 20px;">مراجعة الطرف الملغي والغرامة 15% بعد أول ساعتين من الموافقة.</p>

                <div class="table-wrap">
                    <table style="width: 100%; border-collapse: collapse; text-align: right;">
                        <thead>
                            <tr style="border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 0.9rem;">
                                <th style="padding: 14px 10px;">الرقم</th>
                                <th style="padding: 14px 10px;">الخدمة</th>
                                <th style="padding: 14px 10px;">العميل</th>
                                <th style="padding: 14px 10px;">تاريخ الحجز</th>
                                <th style="padding: 14px 10px;">الملغي</th>
                                <th style="padding: 14px 10px;">تاريخ الإلغاء</th>
                                <th style="padding: 14px 10px;">الغرامة</th>
                                <th style="padding: 14px 10px;">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cancellations as $cancellation)
                                <tr style="border-bottom: 1px solid #f8fafc; font-size: 0.92rem;">
                                    <td style="padding: 16px 10px; color: #64748b;">
                                        {{ $loop->iteration + ($cancellations->currentPage() - 1) * $cancellations->perPage() }}
                                    </td>
                                    
                                    <td style="padding: 16px 10px; font-weight: bold; color: #1e293b;">
                                        {{ $cancellation->service_name ?? 'خدمة غير محددة' }}
                                        <div style="font-size: 0.8rem; color: #64748b; font-weight: normal;">
                                            {{ number_format($cancellation->original_price, 2) }} د.أ
                                        </div>
                                    </td>

                                    <td style="padding: 16px 10px; color: #334155;">
                                        {{ $cancellation->customer->name ?? trim(($cancellation->customer->first_name ?? '') . ' ' . ($cancellation->customer->last_name ?? '')) ?: 'عميل غير مسجل' }}
                                    </td>

                                    <td style="padding: 16px 10px; color: #475569;">
                                        {{ optional($cancellation->booking_date)->format('Y-m-d') ?? '—' }}
                                    </td>

                                    <td style="padding: 16px 10px;">
    @if($cancellation->cancelled_by == $cancellation->customer_id)
        <span style="background: #e0f2fe; color: #0369a1; padding: 3px 10px; border-radius: 6px; font-size: 0.82rem; font-weight: bold;">
            العميل
        </span>
    @elseif($cancellation->cancelledByUser)
        <span style="background: #fef3c7; color: #b45309; padding: 3px 10px; border-radius: 6px; font-size: 0.82rem; font-weight: bold;">
        الفني
        </span>
    @else
        <span style="color: #94a3b8;">—</span>
    @endif
</td>

                                    <td style="padding: 16px 10px; color: #475569; font-size: 0.85rem;">
                                        {{ optional($cancellation->cancelled_at)->format('Y-m-d H:i') ?? '—' }}
                                    </td>

                                    <td style="padding: 16px 10px; font-weight: bold; color: {{ $cancellation->cancellation_penalty > 0 ? '#dc2626' : '#64748b' }};">
                                        {{ $cancellation->cancellation_penalty ? number_format($cancellation->cancellation_penalty, 2) . ' د.أ ' : '0.00 ' }}
                                    </td>

                                    <td style="padding: 16px 10px;">
                                        <form action="{{ route('admin.cancellations.destroy', $cancellation->id) }}" method="POST" style="margin: 0;"
                                              onsubmit="if (!window.Swal) return confirm('هل أنت متأكد من حذف هذا السجل نهائياً؟'); event.preventDefault(); Swal.fire({
                                                  title: 'تأكيد الحذف',
                                                  text: 'هل أنت متأكد من حذف هذا السجل نهائياً؟',
                                                  icon: 'warning',
                                                  showCancelButton: true,
                                                  confirmButtonColor: '#dc2626',
                                                  cancelButtonColor: '#64748b',
                                                  confirmButtonText: 'نعم، احذف',
                                                  cancelButtonText: 'إلغاء'
                                              }).then((result) => { if (result.isConfirmed) this.submit(); });">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="حذف"
                                                style="background: #fee2e2; color: #dc2626; border: none; width: 34px; height: 34px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 35px; color: #94a3b8;">
                                        لا توجد أي حجوزات ملغاة حتى الآن.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($cancellations->hasPages())
                    <div style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 25px;">
                        @if ($cancellations->onFirstPage())
                            <span style="padding: 6px 14px; border: 1px solid #e2e8f0; border-radius: 6px; color: #cbd5e1; cursor: not-allowed; font-size: 0.85rem;">
                                &laquo; السابق
                            </span>
                        @else
                            <a href="{{ $cancellations->previousPageUrl() }}" style="padding: 6px 14px; border: 1px solid #cbd5e1; border-radius: 6px; color: #1e293b; text-decoration: none; font-size: 0.85rem; background: #fff;">
                                &laquo; السابق
                            </a>
                        @endif

                        <span style="font-size: 0.85rem; color: #64748b; font-weight: bold;">
                            صفحة {{ $cancellations->currentPage() }} من {{ $cancellations->lastPage() }}
                        </span>

                        @if ($cancellations->hasMorePages())
                            <a href="{{ $cancellations->nextPageUrl() }}" style="padding: 6px 14px; border: 1px solid #cbd5e1; border-radius: 6px; color: #1e293b; text-decoration: none; font-size: 0.85rem; background: #fff;">
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
