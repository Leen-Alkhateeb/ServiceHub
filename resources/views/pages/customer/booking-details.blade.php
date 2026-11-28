@extends('layouts.app')
@section('title', 'تفاصيل الحجز')
@section('content')
@include('components.navbar')
<main class="container page-head stack">
    <a href="{{ route('customer.bookings') }}">العودة إلى حجوزاتي</a>
    <h1>حجز #{{ $booking->id }} · {{ $booking->service_name }}</h1>
    @if(session('success'))<p class="notice" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<p class="notice" role="alert">{{ $errors->first() }}</p>@endif
    <section class="panel stack">
        <h2>الموعد والخدمة</h2>
        <p class="muted">الحالة: {{ match($booking->status) {
            'pending' => 'بانتظار موافقة الفني', 'approved', 'awaiting_payment' => 'بانتظار الدفع',
            'confirmed', 'paid' => 'مؤكّد', 'in_progress' => 'قيد التنفيذ', 'completed' => 'مكتمل',
            'cancelled' => 'ملغى', 'rejected' => 'مرفوض', 'refunded' => 'مسترد',
            'expired' => 'انتهت المهلة', 'closed' => 'مغلق', 'objection_pending' => 'اعتراض قيد المراجعة',
            default => 'غير محددة',
        } }}</p>
        <p>{{ $booking->booking_date->format('Y-m-d') }} · <bdi dir="ltr">{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</bdi> بتوقيت عمّان</p>
        <p>{{ $booking->service_description }}</p>
        @if($vendor = $booking->vendorService?->vendor)
            <p>الفني: {{ $vendor->first_name }} {{ $vendor->last_name }}
                @if($vendor->vendorProfile?->application_status === 'approved')<span class="pill yellow">طلب الانضمام معتمد</span>@endif
            </p>
        @endif
        <p>المبلغ: {{ number_format($booking->original_price, 2) }} د.أ</p>
        @if($booking->payment)
            <p>الدفعة المسجلة: {{ number_format($booking->payment->amount, 2) }} د.أ · <a href="{{ route('payment.invoice', $booking) }}">الفاتورة</a></p>
        @elseif(in_array($booking->status, ['approved', 'awaiting_payment'], true) && $booking->payment_deadline?->isFuture() && $booking->scheduledStart()?->isFuture())
            <p><a class="btn btn-yellow" href="{{ route('payment.checkout', $booking) }}">إتمام الدفع</a></p>
            <p>ادفع قبل {{ $booking->payment_deadline->copy()->timezone(\App\Models\Booking::SCHEDULE_TIMEZONE)->format('Y-m-d H:i') }} بتوقيت عمّان.</p>
        @endif
        @if($booking->cancelled_at)
            <p>أُلغي الحجز في {{ $booking->cancelled_at->format('Y-m-d H:i') }}.</p>
            @if($booking->cancellation_refund_amount !== null)<p>مبلغ الاسترداد: {{ number_format($booking->cancellation_refund_amount, 2) }} د.أ</p>@endif
        @endif
        @include('components.booking-evidence', ['showObjection' => true])
        @include('components.booking-cancellation-action')
        @include('components.booking-completion-actions')
    </section>
    @include('components.booking-follow-up-dialogs')
    <section class="panel">
        <h2>السلامة أثناء الموعد</h2>
        <p>تأكد من اسم الفني قبل السماح له بالدخول. إذا في تصرف مقلق، تقدر تبلغ الإدارة. عند وجود خطر مباشر، تواصل مع الطوارئ من مكان آمن.</p>
        <a href="{{ route('contact', ['safety' => 1]) }}">الإبلاغ عن مشكلة سلامة</a>
    </section>
</main>
@include('components.footer')
@endsection
