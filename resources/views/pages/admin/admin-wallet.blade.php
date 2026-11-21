@extends('layouts.app')
@section('title', 'حركات المحفظة | ServiceHub')
@section('page_id', 'admin-wallet')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <div>
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>حركات المحفظة</h1>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success" style="background: #e6f9e6; color: #1e7e34; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    {{ session('success') }}
                </div>
            @endif

            <div class="panel" style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin-top: 0; margin-bottom: 6px;">سجل الحركات المالية والمحفظة</h3>
                <p class="muted" style="margin-top: 0; margin-bottom: 20px;">عرض حركات مستحقات الفنيين، الإيداعات، والخصومات التابعة للحجوزات.</p>

                <div class="table-wrap">
                    <table style="width: 100%; border-collapse: collapse; text-align: right;">
                        <thead>
                            <tr style="border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 0.9rem;">
                                <th style="padding: 14px 10px;">الرقم</th>
                                <th style="padding: 14px 10px;">الفني</th>
                                <th style="padding: 14px 10px;">الحجز المرتبط</th>
                                <th style="padding: 14px 10px;">نوع الحركة</th>
                                <th style="padding: 14px 10px;">المبلغ</th>
                                <th style="padding: 14px 10px;">البيان / الوصف</th>
                                <th style="padding: 14px 10px;">التاريخ والوقت</th>
                                <th style="padding: 14px 10px;">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                                <tr style="border-bottom: 1px solid #f8fafc; font-size: 0.92rem;">
                                    <td style="padding: 16px 10px; color: #64748b;">
                                        {{ $loop->iteration + ($transactions->currentPage() - 1) * $transactions->perPage() }}
                                    </td>

                                    <td style="padding: 16px 10px; font-weight: bold; color: #1e293b;">
                                        {{ $transaction->vendor->name ?? trim(($transaction->vendor->first_name ?? '') . ' ' . ($transaction->vendor->last_name ?? '')) ?: 'فني غير محدد' }}
                                    </td>

                                    <td style="padding: 16px 10px; color: #334155;">
                                        @if($transaction->booking)
                                            <div style="font-size: 0.78rem; color: #64748b;">
                                                {{ $transaction->booking->service_name ?? 'خدمة' }}
                                            </div>
                                        @else
                                            <span style="color: #94a3b8;">—</span>
                                        @endif
                                    </td>

                                    <td style="padding: 16px 10px;">
                                        @php
                                            $type = strtolower($transaction->transaction_type);
                                            $isCredit = in_array($type, ['credit', 'deposit', 'earning', 'إيداع', 'مستحق']);
                                        @endphp

                                        @if($isCredit)
                                            <span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: bold;">
                                                إيداع
                                            </span>
                                        @else
                                            <span style="background: #fee2e2; color: #b91c1c; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: bold;">
                                                خصم
                                            </span>
                                        @endif
                                    </td>

                                    <td style="padding: 16px 10px; font-weight: bold; font-size: 0.90rem; color: {{ $isCredit ? '#16a34a' : '#dc2626' }};">
                                        {{ number_format($transaction->amount, 2) . ' د.أ '}} 
                                    </td>

                                    <td style="padding: 16px 10px; color: #475569; max-width: 260px;">
                                        {{ $transaction->description ?: '—' }}
                                    </td>

                                    <td style="padding: 16px 10px; color: #64748b; font-size: 0.85rem;">
                                        {{ optional($transaction->created_at)->format('Y-m-d H:i') ?? '—' }}
                                    </td>

                                    <td style="padding: 16px 10px;">
                                        <form action="{{ route('admin.wallet.destroy', $transaction->id) }}" method="POST" style="margin: 0;"
                                              onsubmit="event.preventDefault(); Swal.fire({
                                                  title: 'تأكيد الحذف',
                                                  text: 'هل أنت متأكد من حذف حركة المحفظة هذه؟',
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
                                    <td colspan="8" style="text-align: center; padding: 35px; color: #94a3b8;">
                                        لا توجد حركات محفظة مسجلة حتى الآن.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 25px;">
                        @if ($transactions->onFirstPage())
                            <span style="padding: 6px 14px; border: 1px solid #e2e8f0; border-radius: 6px; color: #cbd5e1; cursor: not-allowed; font-size: 0.85rem;">
                                &laquo; السابق
                            </span>
                        @else
                            <a href="{{ $transactions->previousPageUrl() }}" style="padding: 6px 14px; border: 1px solid #cbd5e1; border-radius: 6px; color: #1e293b; text-decoration: none; font-size: 0.85rem; background: #fff;">
                                &laquo; السابق
                            </a>
                        @endif

                        <span style="font-size: 0.85rem; color: #64748b; font-weight: bold;">
                            صفحة {{ $transactions->currentPage() }} من {{ $transactions->lastPage() }}
                        </span>

                        @if ($transactions->hasMorePages())
                            <a href="{{ $transactions->nextPageUrl() }}" style="padding: 6px 14px; border: 1px solid #cbd5e1; border-radius: 6px; color: #1e293b; text-decoration: none; font-size: 0.85rem; background: #fff;">
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