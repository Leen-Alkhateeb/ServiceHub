@extends('layouts.app')
@section('title', 'تفاصيل الحجز الفوري')
@section('content')
@include('components.navbar')
<main class="container page-head stack">
    @php($readOnly = $admin && (int) auth()->id() !== (int) $booking->customer_id)
    <h1>{{ $booking->service_name }} · #{{ $booking->id }}</h1>
    @include('pages.instant.feedback')
    <div class="stack" data-instant-live>
    <section class="panel stack">
        <h2>{{ ['confirmed' => 'تم اختيار الفني', 'on_the_way' => 'الفني بالطريق', 'arrived' => 'الفني وصل', 'in_progress' => 'جاري تنفيذ الخدمة', 'completed' => 'تم تنفيذ الخدمة', 'cancelled' => 'ملغي'][$booking->status] ?? $booking->status }}</h2>
        <p>العرض الأصلي {{ $offer->offered_price }} د.أ · السعر المتفق عليه {{ $booking->agreed_price }} د.أ</p>
        @if($admin)
            <p>العميل {{ $booking->customer->first_name }} {{ $booking->customer->last_name }} · الفني {{ $booking->vendorService->vendor->first_name }} {{ $booking->vendorService->vendor->last_name }}</p>
            @if($booking->walletTransactions->isNotEmpty())
                <details><summary>القيود المالية للحجز</summary>
                    @foreach($booking->walletTransactions as $entry)
                        <p>#{{ $entry->id }} · {{ $entry->transaction_type }} · {{ $entry->amount }} د.أ · {{ $entry->status ?? '—' }}</p>
                        @if($entry->status === 'posted' && $entry->direction === 'credit' && $entry->user_id && in_array($entry->transaction_type, ['payout', 'compensation'], true))
                            <form method="POST" action="{{ route('admin.instant.correct-overpayment', [$booking, $entry->id]) }}" class="stack">
                                @csrf
                                <div class="field"><label for="corrected-{{ $entry->id }}">الاستحقاق المصحّح (د.أ)</label><input id="corrected-{{ $entry->id }}" type="number" name="corrected_entitlement" min="0" max="{{ $entry->amount }}" step="0.001" required></div>
                                <div class="field"><label for="correction-reason-{{ $entry->id }}">سبب التصحيح</label><textarea id="correction-reason-{{ $entry->id }}" name="reason" required maxlength="2000"></textarea></div>
                                <label><input type="checkbox" name="confirm" value="1" required> أوافق على تسجيل الفرق كدين مستقل باسم المستفيد.</label>
                                <button class="btn btn-outline">توثيق الدفعة الزائدة</button>
                            </form>
                        @endif
                    @endforeach
                </details>
            @endif
        @endif
        @unless($readOnly)<p>عنوان الخدمة: {{ $offer->instantRequest->address }}</p>@endunless
        @if($booking->expected_arrival_at)<p>الوصول المتوقع: {{ $booking->expected_arrival_at->copy()->timezone(\App\Models\Booking::SCHEDULE_TIMEZONE)->format('Y-m-d H:i') }} بتوقيت عمّان</p>@endif
        @if($booking->arrival_verified_at)<p>تم التحقق من الوصول.</p>@endif
        @foreach($operationalCases as $case)
            <p>{{ $case->case_type === 'customer_no_show' ? 'مراجعة عدم حضور العميل' : ($reportTypes[$case->case_type] ?? 'مراجعة تأخر وصول الفني') }} #{{ $case->id }} · {{ ['pending' => 'قيد المراجعة', 'accepted' => 'مقبول', 'rejected' => 'مرفوض'][$case->status] }}</p>
            @if($case->admin_response)<p>{{ $case->admin_response }}</p>@endif
            @if($case->approved_service_amount !== null)<p>قيمة العمل المعتمدة {{ $case->approved_service_amount }} د.أ · الاسترداد {{ $case->refund_amount ?? '0.000' }} د.أ</p>@endif
            @if($admin || (int) $case->submitted_by === (int) auth()->id())
                @foreach($caseImages->get($case->id, []) as $image)<a href="{{ route('instant.booking-image', [$booking, $image]) }}">تحميل دليل الحالة {{ $loop->iteration }}</a>@endforeach
            @endif
            @if($admin && $case->status === 'pending')
                <p>{{ $case->reason }}</p>
                @if($arrival)<p>الوصول المسجّل {{ $arrival->created_at }} · الموقع {{ $arrival->latitude }}، {{ $arrival->longitude }}</p>@endif
                <form method="POST" action="{{ route('admin.instant.operational-decision', $booking) }}" class="stack">
                    @csrf<input type="hidden" name="case_id" value="{{ $case->id }}">
                    <div class="field"><label for="operational-reason-{{ $case->id }}">سبب القرار</label><textarea id="operational-reason-{{ $case->id }}" name="reason" maxlength="2000" required></textarea></div>
                    @php($financialCase = in_array($case->case_type, ['partial_service', 'non_execution', 'exceptional_cancellation'], true))
                    @if($financialCase)
                        <div class="field"><label for="service-amount-{{ $case->id }}">قيمة العمل المعتمدة (د.أ)</label>
                            <input id="service-amount-{{ $case->id }}" name="service_amount" type="number" min="0" max="{{ $booking->agreed_price }}" step="0.001" value="{{ $case->case_type === 'non_execution' ? '0.000' : '' }}" @readonly($case->case_type === 'non_execution')>
                        </div>
                        <p>حصة الفني 85٪ من القيمة المعتمدة. يُرد أي مبلغ محصّل يزيد عليها، أو تُسجّل القيمة المعتمدة كدين عند عدم الدفع.</p>
                        <p data-resolution-preview data-collected="{{ $booking->payment?->paid_amount ?? '0.000' }}" data-refunded="{{ $booking->payment?->refunded_amount ?? '0.000' }}" role="status"></p>
                        <label><input type="checkbox" name="confirm_financial_resolution" value="1"> أوافق على تنفيذ القرار المالي بعد مراجعة الأدلة والحالات المرتبطة.</label>
                        <div class="field"><label for="responsibility-{{ $case->id }}">المسؤولية عند الإغلاق</label>
                            <select id="responsibility-{{ $case->id }}" name="responsibility"><option value="">غير محددة</option><option value="customer">العميل</option><option value="vendor">الفني</option></select>
                        </div>
                    @endif
                    <div class="booking-tabs"><button class="btn btn-yellow" name="decision" value="accepted">{{ $financialCase ? 'اعتماد القرار المالي' : (in_array($case->case_type, ['customer_no_show','vendor_late'], true) ? 'اعتماد المسؤولية' : 'اعتماد البلاغ دون تغيير مالي') }}</button><button class="btn btn-outline" name="decision" value="rejected">رفض طلب المراجعة</button></div>
                </form>
            @endif
        @endforeach
        @if($booking->status === 'cancelled' && $compensationCase !== null)
            <p>قيمة التعويض {{ $compensationCase->compensation_amount }} د.أ</p>
            @if(!$vendor && !$readOnly && (int) $compensationCase->responsible_user_id === (int) auth()->id()
                && $booking->walletTransactions()->where('transaction_type', 'debt')->where('status', 'outstanding')->exists())
                <form method="POST" action="{{ route('instant.pay', $booking) }}">@csrf<button class="btn btn-yellow">سداد التعويض تجريبيًا</button></form>
            @endif
            @if($admin && $compensationCase->compensation_status === 'approved' && now()->gte($compensationCase->compensation_due_at))
                <form method="POST" action="{{ route('admin.instant.fund-compensation', $booking) }}">@csrf<button class="btn btn-outline">تمويل التعويض تجريبيًا</button></form>
            @endif
        @endif
        @if($booking->completed_at)
            <p>{{ $booking->completion_description }}</p>
            <p>مهلة الدفع والاعتراض: {{ $booking->objection_deadline->copy()->timezone(\App\Models\Booking::SCHEDULE_TIMEZONE)->format('Y-m-d H:i') }} بتوقيت عمّان.</p>
            @foreach($booking->evidence as $image)
                @if(in_array($image->kind, ['before','after']))<a href="{{ route('instant.booking-image', [$booking, $image]) }}">تحميل صورة {{ $image->kind === 'before' ? 'قبل العمل' : 'بعد العمل' }} {{ $loop->iteration }}</a>@endif
            @endforeach
            @php($serviceDebt = $booking->walletTransactions()->where('account_type', 'customer')->where('transaction_type', 'debt')->where('status', 'outstanding')->where('idempotency_key', 'not like', '%:debt:overpayment-%')->first())
            @if($serviceDebt && !$vendor && !$readOnly && !$hasBlockingCases)
                <form method="POST" action="{{ route('instant.pay', $booking) }}">@csrf<button class="btn btn-yellow">سداد المتبقي {{ $serviceDebt->amount }} د.أ</button></form>
            @elseif(in_array($booking->payment?->status, ['paid', 'partially_refunded'], true))<p>تم الدفع.</p>
            @elseif($booking->refundRequest?->approved_service_amount !== null && (float) $booking->refundRequest->approved_service_amount === 0.0)
                <p>لا يوجد مبلغ مستحق.</p>
            @elseif(!$vendor && !$readOnly && !$hasBlockingCases && ! $booking->settled_at)
                <form method="POST" action="{{ route('instant.pay', $booking) }}">@csrf<button class="btn btn-yellow">دفع تجريبي</button></form>
            @endif
            @if($booking->refundRequest)
                <p>الاعتراض: {{ ['pending' => 'قيد المراجعة', 'accepted' => 'مقبول', 'rejected' => 'مرفوض'][$booking->refundRequest->status] }}</p>
                @foreach($objectionImages as $image)<a href="{{ route('instant.booking-image', [$booking, $image]) }}">تحميل صورة الاعتراض {{ $loop->iteration }}</a>@endforeach
                @if($booking->refundRequest->approved_service_amount !== null)<p>المبلغ المعتمد: {{ $booking->refundRequest->approved_service_amount }} د.أ</p>@endif
                @if($admin && $booking->refundRequest->status === 'pending')
                    <p>{{ $booking->refundRequest->reason }}</p>
                    <form method="POST" action="{{ route('admin.instant.decide', $booking) }}" class="stack">
                        @csrf<div class="field"><label for="decision-reason">سبب القرار</label><textarea id="decision-reason" name="reason" maxlength="2000" required></textarea></div>
                        <p>القبول يعتمد 30٪ من السعر المتفق عليه. الرفض يعتمد كامل السعر. التسوية بعد انتهاء المهلة.</p>
                        <div class="booking-tabs"><button class="btn btn-yellow" name="decision" value="accepted">اعتماد قرار القبول</button><button class="btn btn-outline" name="decision" value="rejected">اعتماد قرار الرفض</button></div>
                    </form>
                @endif
            @elseif(!$vendor && !$readOnly && $booking->objection_deadline?->isFuture() && !$operationalCases->contains(fn ($case) => $case->status === 'accepted' && $case->approved_service_amount !== null))
                <details><summary>إرسال اعتراض</summary>
                    <form method="POST" action="{{ route('instant.object', $booking) }}" enctype="multipart/form-data" class="stack">
                        @csrf<div class="field"><label for="now-objection">سبب الاعتراض</label><textarea id="now-objection" name="reason" maxlength="2000" required></textarea></div>
                        <div class="field"><label for="now-objection-images">صور الاعتراض (اختيارية)</label><x-file-picker><input id="now-objection-images" name="images[]" type="file" multiple accept="image/jpeg,image/png,image/webp"></x-file-picker></div>
                        <div><button class="btn btn-outline">إرسال الاعتراض</button></div>
                    </form>
                </details>
            @endif
        @endif
        @unless($readOnly || $hasBlockingCases)
            @php($next = $vendor ? match($booking->status) { 'confirmed' => 'depart', 'on_the_way' => 'arrive', 'arrived' => $booking->arrival_verified_at ? 'start' : 'reissue', 'in_progress' => !$adjustment ? 'complete' : null, default => null } : ($booking->status === 'arrived' && !$booking->arrival_verified_at ? 'verify' : null))
            @if(in_array($booking->status, ['confirmed','on_the_way','arrived'], true))
                @php($freeCancellation = !$vendor && ($booking->status === 'confirmed' || ($booking->status === 'on_the_way' && $booking->expected_arrival_at?->copy()->addMinutes(10)->lte(now()))))
                <form method="POST" action="{{ route('instant.transition', $booking) }}" class="stack">
                    @csrf<input name="action" type="hidden" value="cancel">
                    @unless($freeCancellation)<p>غرامة الإلغاء 15٪ من السعر المتفق عليه.</p>@endunless
                    <div><button class="btn btn-outline">{{ $freeCancellation ? 'إلغاء الحجز مجانًا' : 'إلغاء الحجز مع الغرامة' }}</button></div>
                </form>
            @endif
            @if($code)<p>رمز الوصول لإعطائه للعميل <strong><bdi>{{ $code }}</bdi></strong></p>@endif
            @if($next)
                <form method="POST" action="{{ route('instant.transition', $booking) }}" @if($next === 'arrive') data-instant-location @endif @if($next === 'complete') enctype="multipart/form-data" @endif class="stack">
                    @csrf<input type="hidden" name="action" value="{{ $next }}">
                    @if($next === 'arrive')@include('pages.instant.location')@endif
                    @if($next === 'verify')<div class="field"><label for="arrival-code">الرمز من الفني</label><input id="arrival-code" name="arrival_code" inputmode="numeric" autocomplete="off" pattern="[0-9]{6}" maxlength="6" required></div>@endif
                    @if($next === 'complete')
                        <div class="field"><label for="now-completion">وصف العمل المنجَز</label><textarea id="now-completion" name="completion_description" maxlength="2000" required>{{ old('completion_description') }}</textarea></div>
                        @foreach(['before' => 'صور قبل العمل', 'after' => 'صور بعد العمل'] as $kind => $label)
                            <div class="field"><label for="now-{{ $kind }}">{{ $label }} (اختيارية)</label><x-file-picker><input id="now-{{ $kind }}" type="file" name="{{ $kind }}_images[]" multiple accept="image/jpeg,image/png,image/webp"></x-file-picker><small>حتى 5 صور، 5 ميغابايت للصورة.</small></div>
                        @endforeach
                    @endif
                    <div><button class="btn btn-yellow">{{ ['depart' => 'تأكيد المغادرة', 'arrive' => 'تسجيل الوصول', 'start' => 'بدء الخدمة', 'complete' => 'تأكيد الإنجاز', 'verify' => 'تحقق من الرمز', 'reissue' => 'إصدار رمز جديد', 'cancel' => 'إلغاء الحجز مجانًا'][$next] }}</button></div>
                </form>
            @endif
            @if(in_array($booking->status, ['arrived', 'in_progress']))
                <a href="{{ route('contact', ['incomplete_booking' => $booking->id]) }}">تواصل مع الإدارة إذا تعذّر إكمال الإجراء</a>
            @endif
            @if($vendor && $booking->status === 'arrived' && !$booking->arrival_verified_at && !$operationalCases->whereIn('status', ['pending', 'accepted'])->contains('case_type', 'customer_no_show')
                && $booking->arrived_at?->copy()->addMinutes(10)->lte(now()))
                <details><summary>طلب مراجعة عدم الحضور</summary>
                    <form class="stack" method="POST" action="{{ route('instant.no-show', $booking) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="field"><label for="no-show-reason">وصف ما حدث</label><textarea id="no-show-reason" name="reason" maxlength="2000" required></textarea></div>
                        <div class="field"><label for="no-show-images">صور داعمة (اختيارية)</label><x-file-picker><input id="no-show-images" name="images[]" type="file" multiple accept="image/jpeg,image/png,image/webp"></x-file-picker></div>
                        <div><button class="btn btn-outline">إرسال للمراجعة</button></div>
                    </form>
                </details>
            @endif
        @endunless
        @unless($readOnly)
            <details><summary>رفع حالة للإدارة</summary>
                <form method="POST" action="{{ route('instant.cases', $booking) }}" enctype="multipart/form-data" class="stack">
                    @csrf
                    <div class="field"><label for="case-type">نوع الحالة</label><select id="case-type" name="case_type" required>
                        @foreach($reportTypes as $type => $label)<option value="{{ $type }}">{{ $label }}</option>@endforeach
                    </select></div>
                    <div class="field"><label for="case-reason">وصف الحالة</label><textarea id="case-reason" name="reason" maxlength="2000" required></textarea></div>
                    <div class="field"><label for="case-evidence">صور داعمة</label><x-file-picker><input id="case-evidence" name="images[]" type="file" multiple accept="image/jpeg,image/png,image/webp"></x-file-picker></div>
                    <div><button class="btn btn-outline">إرسال الحالة</button></div>
                </form>
            </details>
        @endunless
    </section>
    @if(!$readOnly && !$hasBlockingCases && in_array($booking->status, ['arrived','in_progress']))
        <section class="panel stack">
            <h2>تعديل السعر</h2>
            @if($adjustment)
                <p>{{ $adjustment->previous_price }} ← {{ $adjustment->proposed_price }} د.أ</p><p>{{ $adjustment->description }}</p>
                <form method="POST" action="{{ route('instant.adjust', $booking) }}" class="booking-tabs">
                    @csrf<input type="hidden" name="adjustment_id" value="{{ $adjustment->id }}">
                    @if($vendor)<button class="btn btn-outline" name="decision" value="withdrawn">سحب التعديل</button>@else<button class="btn btn-yellow" name="decision" value="accepted">قبول السعر</button><button class="btn btn-outline" name="decision" value="rejected">رفض السعر</button>@endif
                </form>
            @elseif($vendor)
                <form method="POST" action="{{ route('instant.adjust', $booking) }}" class="stack">
                    @csrf<input type="hidden" name="decision" value="propose">
                    <div class="field"><label for="new-price">السعر المقترح بالدينار</label><input name="proposed_price" id="new-price" type="number" step="0.001" min="0.001" max="9999999.999" required></div>
                    <div class="field"><label for="price-reason">السبب</label><textarea name="description" id="price-reason" maxlength="2000" required></textarea></div>
                    <div><button class="btn btn-yellow">طلب تعديل السعر</button></div>
                </form>
            @else<p>ما في تعديل سعر معلّق.</p>@endif
        </section>
    @endif
    <section class="panel"><h2>سجل الحجز</h2>
        @foreach($events as $event)
            <p>{{ $event->created_at }} · {{ ['status_change' => 'تحديث الحالة', 'arrival_recorded' => 'تسجيل الوصول', 'arrival_verified' => 'التحقق من الوصول', 'price_adjustment' => 'تعديل السعر', 'cancellation' => 'إلغاء'][$event->event_type] ?? 'تحديث' }}
                @if($event->proposed_price) · {{ $event->proposed_price }} د.أ · {{ ['pending' => 'معلّق', 'accepted' => 'مقبول', 'rejected' => 'مرفوض', 'withdrawn' => 'مسحوب'][$event->adjustment_status] ?? '' }} @endif
                @if($event->new_status === 'duration_overrun' && ($vendor || $admin)) · {{ $event->description }} @endif
            </p>
        @endforeach
        {{ $events->links('components.pager') }}
    </section>
    </div>
    <p class="muted" data-instant-live-status role="status"></p>
</main>
<script type="module" src="{{ asset('assets/instant.js') }}?v={{ filemtime(public_path('assets/instant.js')) }}"></script>
@endsection
