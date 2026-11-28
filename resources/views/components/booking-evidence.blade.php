@if($booking->vendor_completed_at)
    <section>
        <h3>إفادة الفني عن الإنجاز</h3>
        <p>{{ $booking->vendor_completed_at->copy()->timezone(\App\Models\Booking::SCHEDULE_TIMEZONE)->format('Y-m-d H:i') }} بتوقيت عمّان</p>
        @if($booking->completion_description)<p>{{ $booking->completion_description }}</p>@endif
        @foreach($booking->evidence->where('kind', 'completion') as $image)
            <a href="{{ route('booking.evidence', [$booking, $image]) }}">تحميل صورة الإنجاز {{ $loop->iteration }}</a>
        @endforeach
        @if($booking->objection_deadline)
            <p>مهلة الاعتراض: {{ $booking->objection_deadline->copy()->timezone(\App\Models\Booking::SCHEDULE_TIMEZONE)->format('Y-m-d H:i') }} بتوقيت عمّان.</p>
        @endif
    </section>
@endif
@if(($showObjection ?? false) && $booking->refundRequest)
    <section>
        <h3>اعتراض العميل</h3>
        <p>{{ ['pending' => 'قيد المراجعة', 'accepted' => 'مقبول', 'rejected' => 'مرفوض'][$booking->refundRequest->status] ?? 'تم تسجيله' }}</p>
        <p>{{ $booking->refundRequest->reason }}</p>
        @foreach($booking->evidence->where('kind', 'objection') as $image)
            <a href="{{ route('booking.evidence', [$booking, $image]) }}">تحميل صورة الاعتراض {{ $loop->iteration }}</a>
        @endforeach
    </section>
@endif
