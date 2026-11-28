@props(['status' => null, 'text' => null])
@php
    $text ??= match ($status) {
        'pending' => 'الطلب بانتظار قبول الفني أو رفضه.',
        'approved', 'awaiting_payment' => 'وافق الفني، وباقي على العميل يدفع ضمن المهلة.',
        'confirmed', 'paid' => 'الموعد مؤكد والحجز بالمرحلة المدفوعة أو المؤكدة.',
        'in_progress' => 'الحجز قيد التنفيذ.',
        'completed' => 'تم تسجيل اكتمال الخدمة.',
        'objection_pending' => 'الإدارة تراجع الاعتراض والمبلغ قيد المراجعة.',
        'refunded' => 'تم تسجيل استرداد مرتبط بالحجز.',
        default => null,
    };
@endphp
@if($text)
    @php($tooltipId = 'booking-info-'.\Illuminate\Support\Str::uuid())
    <span class="booking-info">
        <button class="booking-info-trigger" type="button" popovertarget="{{ $tooltipId }}" aria-label="شرح الحالة أو المبلغ" aria-describedby="{{ $tooltipId }}">ⓘ</button>
        <span class="booking-info-popup" id="{{ $tooltipId }}" role="tooltip" popover>{{ $text }}</span>
    </span>
@endif
